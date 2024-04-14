# Decrypts Telegram Android .enc files, given the corresponding .key file
# .enc files are from Android/media, .key files are from /data/data
# If not found, the key file is read directly with adb

#!/bin/bash
if [ $# -lt 1 ]
then
	echo $0" infile [outfile]"
fi
infile=$1
keyfile=$infile".key"
if [[ ! -z $2 ]]
then
	outfile=$2
else
	outfile=${infile/%.enc/}
	if [[ $infile == $outfile ]]
	then
		echo ".enc extension missing from input file, manually specify output file"
		exit 1
	fi
fi
if [[ ! -f $infile ]]
then
	echo $infile" not found"
fi
if [[ ! -f $keyfile ]]
then
	if ! serial=$(connectadb)
	then
		echo "Device not found"
		exit 1
	fi
	if ! adb -s $serial shell 'su -c ls /data/data/org.telegram.messenger/cache/'$keyfile >/dev/null 2>/dev/null
	then
		echo "Remote key file not found"
		exit 1
	fi
	key=$(adb -s $serial shell 'su -c xxd -p -c256 /data/data/org.telegram.messenger/cache/'$keyfile | tr -d '\n ' | head -c64)
	iv=$(adb -s $serial shell 'su -c xxd -p -c256 /data/data/org.telegram.messenger/cache/'$keyfile | tr -d '\n ' | tail -c +65 | head -c 24)
	if [[ -z $key || -z $iv ]]
	then
		echo "Key file exists, but is not long enough"
		exit
	fi
	iv=$iv"00000000"
else
	key=$(xxd -p -c999 $keyfile | head -c64)
	iv=$(xxd -p -c999 $keyfile | tail -c +65 | head -c 24)"00000000"
fi
openssl enc -d -aes-256-ctr -nosalt -in $infile -K $key -iv $iv -out $outfile