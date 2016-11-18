gphoto-webui
============

The reason I wrote this was simple: I wanted a remote control for my DSLR. That way, I can snap photos while I was in front of the camera from my smartphone and review them after the fact. I’m somewhat of a shutter bug in addition to being a tech geek. I could have bought a fancy dongle and installed an app on my phone to get the same sort of behavior, but I already had a Raspberry Pi (RasPi), and thought, “What if I could use that little mini computer to control my DSLR, then use my phone as remote?” Something like this maybe?

[Smartphone] ~~~WiFi~~~ [RasPI] >—USB—< [Camera]

Turns out, I can thanks to a project called gphoto. gphoto is a CLI tool for doing things on many different models of cameras from all kinds of manufacturers, including Canon, which I have. All it needed was a way to control gphoto from my smartphone. I could use an SSH shell or a remote desktop, but where’s the fun in that? I applied some PHP, HTML, and JavaScript magic to make it all happen so any device with a web browser can control the camera as a remote control. I simply wrote a web based front-end for gphoto that is mobile friendly and posted it to GitHub for all to use, modify, and enjoy.

I’m using a RasPi model “B” (512 MB), but there’s no reason this wouldn’t work on model “A” or another box using Debian or Ubuntu Linux. I also have a WiFi adapter on my RasPi. Mobile WiFi might be challenge… You could “tether” your RasPi to your phone or tablet, or your could set up your RasPi as a WiFi AP too. My phone and tablet both support tethering, so I just use that.

Also, the RasPi needs power, and it doesn’t have a battery. It requires a 5v 1000ma DC connection to work. This isn’t to hard to reproduce with off the shelf batteries and a little McGyvering. There’s also after-market battery packs for the RasPi too.

Anyways, here’s how to make it work…

1.) Power up your Raspberry Pi. Pull up a Terminal, logon through SSH, or whatever you do to get to a console.

2.) Update your Raspberry Pi

sudo apt-get update

sudo apt-get upgrade

3.) Install the following packages.

sudo apt-get install imagemagick php5 php5-cli php5-imagick gphoto2 zip unzip exiv2

Note: You do not need a web server. In fact, I wouldn’t recommend it for this little app because this app will need more elevated permissions than the www-data user has. Configuring the server to run a user with elevated permissions might work, but not necessary. PHP has a built in development server that will work just fine and also run as the logged on user. 

DON'T RUN THIS ON THE PUBLIC INTERNET. THIS IS NOT INTENDED FOR THAT, NOR IS IT SECURE! 

Note: As of writing this, the gphoto2 package that comes with Raspian is a little outdated (version 2.4). I had some problems with my camera (a Canon 50D) and the packaged version, so I compiled it from the source. Here’s a cut-and-paste recipe for doing just that. I’d recommend running sudo -i before performing this guide to ensure no problems with permissions. http://whysohardtoget.blogspot.com/2013/05/compiling-gphoto2-252-with-raspberry-pi.html

4.) Download the gphoto web ui from from github. (wget works if you're using a console.) You'll need to be in a writeable folder. I suggest your home folder.

cd ~

wget https://github.com/theonemule/gphoto-webui/archive/master.zip

5.) Extract the files into a directory

unzip master.zip

6.) In the console, cd to that directory

cd gphoto-webui-master

7.) Start the php server. This will bind the server to port 8000 on all IP’s.

php5 -S 0.0.0.0:8000

8.) Point your browser to http://x.x.x.x:8000/index.html x.x.x.x is the IP if your Raspberry Pi.

9.) The WebUI is pretty much self explanatory. Hit the aperture icon to take a picture. View the images on the Gallery page. The images are captured in whatever format the camera is set to use, and this does support RAW images as well. Images are stored on the Raspberry Pi's SD card, not in the camera's memory.

10.) The source images can be downloaded through the Web UI or can be FTP'd through an app like FileZilla over SFTP. The images are all stored in the "images" folder in relative to the root of the application. (i.e. ~/gphoto-webui-master/images) .


###UPDATE:

I initially wrote this little app as a way to take pictures remotely my standing in front of the camera. Lots of folks have downloaded and forked it for lots of other projects which is cool!

I added a script called "poll.sh" that uses the [Motion](http://www.lavrsen.dk/foswiki/bin/view/Motion/WebHome) project for motion detection to trigger a camera. It uses a USB webcam as a motion detector. The webcam is ideal for this, rather than the DSLR because it is "always on" and doesn't use shutter clicks on a DSL between shots. When motion detects a difference between two shots, it writes a file to a specified directory. 

The script herein polls that directory looking for files as an indicator that motion has been detected. when found, it triggers the DSLR to take a shot by calling the web service from ghpoto-webui. 

After setting up the app as instructed above, setting up motion is pretty simple too.

1.) Install motion (and wget if it isn't already installed).

sudo apt-get install motion wget

2.) Create a ramdisk -- This step is optional, but it saves IO operations on the CD card and makes motion perform better.

sudo mount -t tmpfs -o size=256m tmpfs /mnt/ramdisk

Change the size your liking. 256 megabytes is more than suffecient for this applicaiton though.

Ifyou want to make the ramdisk mount at boot, add it to the fstab file.

sudo nano /etc/fstab

Add this to the buttom if the file.

tmpfs /mnt/ramdisk tmpfs nodev,nosuid,noexec,nodiratime,size=256M   0 0

3.) Configure motion:

sudo nano /etc/motion/motion.conf

Set the paramters as follows...

* daemon on #default off (This allows the motion to run in the background)
* framerate 15 #default 2 (increased framerate higher = more CPU usage)
* width 640 #default 320 (changed width to match that of the webcam)
* height 480 #default 240 (same as above but for height)
* threshold 1500 #default 1500 (the motion detection sesitivity. Lower is more sensetive)
* target_dir /mnt/ramdisk #default /tmp/motion (changed the directory where motion captures are stored. If you did step 2, then this will be wherever you mounted the ramdisk)

4.) Turn on the motion daemon:

sudo nano /etc/default/motion

Set:

start_motion_daemon=yes

5.) Start Motion

Ensure that your USB camera is attached to your Raspberry Pi

service motion start

6.) Edit the poll.sh

cd /path/to/gphoto-webui

nano poll.sh 

Change the motion_dir to the folder motion writes images too.

Ctrl + X to save.

7.) Start poll.sh. It will start the php webserver and poll for changes made by motion.

Once started, the script will be running. You should be able to motion activate your DSLR now by creating motion in front of your USB webcam

To stop the services, simply:

Stop the poll script:
Ctrl + C to 

Stop the PHP server:
killall php

Stop motion:
service motion stop

Well that it! enjoy the motion activated DSLR.
