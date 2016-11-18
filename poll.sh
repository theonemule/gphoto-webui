#!/bin/bash

#path where motion writes the files to.
motion_dir="/mnt/ramdisk"

#the url of the camera services. This is probably OK to leave as is.
camera_url="http://localhost:9000/service.php?action=takePicture"

#starts the php web server in the background.
nohup php -S 0.0.0.0:9000 > phpd.log 2>&1 &

#polls the motion dir every half second for changes.
while true
do
	filecount="$(find ${motion_dir} -type f | wc -l)"
	
	if [ "${filecount}" -ne "0" ]
	then
		rm $motion_dir/* #Removes the files from the motion directory to reset it.
		wget  -qO- $camera_url &> /dev/null #instructs the web service to take a picture.
	fi
	
	sleep 0.5 #sleep half a second.
done