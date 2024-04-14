d=$(mktemp -d)
u=$(mktemp -d)
if [[ -z "$1" ]]
then
	echo "Specify input gif file"
	exit 1
elif [[ ! -f "$1" ]]
then
	echo $1" does not exist"
	exit 1
fi
delay=$(identify -verbose "$1" | grep Delay -m1 | sed 's/.* //' | sed 's/x.*//')
case $delay in
	''|*[!0-9]*)
		echo "Found gif delay is not numeric: "$delay
		exit 1
	;;
esac
echo $1": Splitting"
convert "$1" -coalesce $d/png.png
echo $1": Undithering"
ls $d/*.png | parallel undither {} $u"/"{/}
echo $1": Merging"
apngasm "${1%.*}.apng" $(ls $u/*.png | sort -V) $delay 100 -z0 -i0 >/dev/null
#convert -delay ${delay} $(ls $u/*.png | sort -V) "apng:"${1%.*}".apng" #WRONG DELAY!