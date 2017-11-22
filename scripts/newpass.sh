#!/bin/sh

# Create today's random password 
newpass=`diceware -d - -n 2`
echo $newpass > /home/pi/quizap/web/ap/newpass

sudo /home/pi/quizap/scripts/hostapd.conf.sh
