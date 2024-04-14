if [ -z "$1" ]
	then echo "Specify disk"
	exit
fi
sudo mount -t drvfs $1: /mnt/"${1,,}"