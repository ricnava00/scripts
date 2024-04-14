if [ -z "$1" ]
	then echo "Specify disk"
	exit
fi
sudo umount /mnt/"${1,,}"