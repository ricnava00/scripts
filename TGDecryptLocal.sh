# Decrypts Telegram Android .enc files, given the corresponding .key file
# .enc files are from Android/media, .key files are from /data/data

#!/bin/bash
if [ $# -lt 2 ]
then
	echo $0" infile keyfile [outfile]"
fi
infile=$1
keyfile=$2
outfile=$3
if [[ ! -f $infile ]]
then
	echo $infile" not found"
fi
if [[ ! -f $keyfile ]]
then
	echo $keyfile" not found"
fi
key=$(xxd -p -c999 $keyfile | head -c64)
iv=$(xxd -p -c999 $keyfile | tail -c +65 | head -c 24)"00000000"
openssl enc -d -aes-256-ctr -nosalt -in $infile -K $key -iv $iv -out $outfile