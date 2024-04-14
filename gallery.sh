#!/bin/bash
VERSION=1

tmpdir=$(mktemp -d)

mininterval=15
maxframes=216
jobs=-1

info()
{
	echo -e "$0 [opts] file\n--jobs N\tParallel jobs\n--maxframes N\tMax number of generated frames\n--mininterval N\tMinimum frame interval, gets doubled until frames don't exceed max\n--interval N\tForced frame interval, ignores max frames\n--version\tPrint version"
	exit
}

inc=0
POSITIONAL_ARGS=()
optionString=""
while [[ $# -gt 0 ]]; do
	case $1 in
		--help)
			info
			;;
		--version)
			echo $VERSION
			exit
			;;
		--jobs)
			if [[ $inc != 0 && $inc != 1 ]]
			then
				echo "--maxframes and --interval are incompatible"
				exit
			fi
			jobs="$2"
			shift # past argument
			shift # past value
			;;
		--maxframes)
			if [[ $inc != 0 && $inc != 1 ]]
			then
				echo "--maxframes and --interval are incompatible"
				exit
			fi
			inc=1
			maxframes="$2"
			if [[ $maxframes -le 0 ]]
			then
				((maxframes=(2**31)-1))
			fi
			optionString="$optionString $1 $2"
			shift # past argument
			shift # past value
			;;
		--mininterval)
			if [[ $inc != 0 && $inc != 1 ]]
			then
				echo "--mininterval and --interval are incompatible"
				exit
			fi
			inc=1
			mininterval="$2"
			optionString="$optionString $1 $2"
			shift # past argument
			shift # past value
			;;
		--interval)
			if [[ $inc != 0 && $inc != 2 ]]
			then
				echo "--interval and --mininterval/--maxframes are incompatible"
				exit
			fi
			inc=2
			mininterval="$2"
			((maxframes=(2**31)-1))
			optionString="$optionString $1 $2"
			shift # past argument
			shift # past value
			;;
		-*|--*)
			echo "Unknown option $1"
			exit 1
			;;
		*)
			POSITIONAL_ARGS+=("$1") # save positional arg
			shift # past argument
			;;
	esac
done
if [[ -z $optionString ]]
then
  optionString=" "
fi
set -- "${POSITIONAL_ARGS[@]}" # restore positional parameters

if [[ -z $1 ]]
then
	info
fi
f=$(realpath "$1")
if [[ ! -f $f ]]
then
	echo -e "\e[1;31m$f doesn't exist\e[0m"
	exit
fi
outfile=${f%.*}_prev.jpg
if [[ -f $outfile || -f $outfile.tmp ]]
then
	echo -e "\e[1;33m$outfile already exists\e[0m"
	exit
fi
secs=$(($(mediainfo "$f" --Output="Video;%Duration%" | sed 's/\..*//')/1000))
estframes=$(($secs/$mininterval))
if (( $estframes <= 1 && $secs >= 3 ))
then
	mininterval=$(($secs/3))
	estframes=$(($secs/$mininterval))
fi
mult=1
while [ $(($estframes+1)) -gt $maxframes ]
do
	mult=$(($mult*2))
	estframes=$(($estframes/2))
done
echo -e Dumping frame every $(($mult*$mininterval)) seconds, estimated $(($estframes+1)) frames"\t\e[2m"$1"\e[0m"
cmds=()
for (( n=0; n<=$estframes; n++ ))
do
	#ffmpeg -ss $(($n*$mult*$mininterval)) -i "$f" -vf "scale=360*dar:360" $tmpdir/$(printf %03d $n).jpg 2>/dev/null &
	cmds+=($(($n*$mult*$mininterval)) $(printf %03d $n))
done
wait
parallel --halt 2 -j $jobs -N 2 ffmpeg -threads 1 -ss {2} -i {1} -vf "scale=360*dar:360" -frames:v 1 $tmpdir/{4}.jpg 2>/dev/null ::: "$f" ::: ${cmds[@]}
if [[ $? != 0 ]]
then
	echo -e "\e[1;41m\e[KError!\e[0m"
	exit 1
fi
echo -e Editing and merging thumbnails"\t\t\t\t\e[2m"$1"\e[0m"
cmds=()
for n in $(ls $tmpdir/*.jpg | sed 's/.jpg//' | sed 's~'$tmpdir'/~~' | sort -n)
do
	num=$(($(sed 's/^0*//' <<< $n)+0))
	ts=$(($num*$mult*$mininterval))
	h=$(printf %02d $(($ts/3600)))
	m=$(printf %02d $(($ts%3600/60)))
	s=$(printf %02d $(($ts%60)))
	#convert -background "rgba(0,0,0,0.5)" -pointsize "36" -fill white label:$h":"$m":"$s -trim -gravity southeast -splice 10x10 -gravity northwest -splice 10x10 $tmpdir/$n.jpg +swap -gravity southeast -geometry +5+5 -composite $tmpdir/$n.jpg &
	cmds+=($h":"$m":"$s $n)
done
wait
#For some reason when in a folder mounted with sshfs all commands are slower, even if they apparently don't use local files
cd $tmpdir
parallel -j $jobs -N 2 magick -background '"rgba(0,0,0,0.5)"' -pointsize "36" -fill white label:{1} -trim -gravity southeast -splice 10x10 -gravity northwest -splice 10x10 $tmpdir/{2}.jpg +swap -gravity southeast -geometry +5+5 -composite $tmpdir/{2}.jpg ::: ${cmds[@]}
num=$(ls $tmpdir | wc -l)
lines=$(echo "f=sqrt(3/4*$num*$(identify -format "%w/%h" $tmpdir"/000.jpg"));scale=0;((f+0.5)+((f+0.5)<1))/1" | bc -l) #Calculation of real value, then round at 0.5, but rounding all <1 to 1. The destination ratio is 4/3.
if [[ -f $outfile || -f $outfile.tmp ]]
then
	echo -e "\e[1;33m$outfile already exists\e[0m"
	exit
fi
touch "$outfile".tmp #Quickly create it to avoid others passing the previous condition
montage -fill white -geometry +0+0 -tile x$lines $tmpdir/* -background black -pointsize $((6*$lines)) JPEG:"$outfile".tmp
destwidth=$(identify -format "%w" "$outfile".tmp)
destheight=$(($(identify -format "%h" "$outfile".tmp)/15))
magick -font "/mnt/c/Windows/Fonts/msjhbd.ttc" -background black -fill white -size x$destheight label:"$(basename "$f")" -gravity center -extent "%[fx:max(w+$destheight,$destwidth)]x" -resize "${destwidth}x" -extent x$destheight pnm:- | montage - "$outfile".tmp -tile 1x -geometry +0+0 JPEG:"$outfile".tmp
mv "$outfile".tmp "$outfile"
comment="Source: $(basename "${f%.*}" | tr -d '\n' | base64 -w 0)
Version: $VERSION
Options:$optionString"
exiftool -q -overwrite_original -comment="$comment" -XPComment="$comment" "$outfile"
cd - >/dev/null
rm -rf $tmpdir
echo -e Done"\t\t\t\t\t\t\t\e[2m"$1"\e[0m"