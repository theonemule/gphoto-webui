#!/bin/bash

motion_dir="/mnt/ramdisk"
camera_url="http://localhost:9000/service.php?action=takePicture"

nohup php -S 0.0.0.0:9000 > phpd.log 2>&1 &

while true
do
	filecount="$(find ${motion_dir} -type f | wc -l)"

	
	if [ "${filecount}" -ne "0" ]
	then
		#do stuff here
		echo "nuking files"
		rm $motion_dir/*
		wget  -qO- $camera_url &> /dev/null
	fi
	
	sleep 0.5
done