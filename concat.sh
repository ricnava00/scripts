#!/bin/bash
if [ -z "$1" ]
then
	echo "Specify output file"
	exit
fi
ffmpeg -f concat -safe 0 -i <(ls *.mp4 | sort -V | while read -r f; do echo "file '$(pwd)/$f'"; done) -c copy "$1" -y