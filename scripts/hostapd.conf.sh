#!/bin/sh

#
# hostname
#
hn=`cat /home/pi/quizap/web/ap/hostname`
hs=`grep -n '127.0.1.1' /etc/hosts | awk -F: '{print $1}' | head -1`
cat /etc/hosts | sudo sed "${hs}s/.*/127.0.1.1\t$hn/" > /tmp/hosts.tmp
sudo mv /tmp/hosts.tmp /etc/hosts

echo $hn > /tmp/hostname.tmp
sudo mv /tmp/hostname.tmp /etc/hostname
#
# END
#

#
# ssid
#
a=`grep -n 'ssid=' /etc/hostapd/hostapd.conf | awk -F: '{print $1}' | head -1`
a1=`cat /home/pi/quizap/web/ap/ssid`

cat /etc/hostapd/hostapd.conf | sudo sed "${a}s/.*/ssid=$a1/" > /tmp/hostapd.conf.tmp
sudo mv /tmp/hostapd.conf.tmp /etc/hostapd/hostapd.conf
#
# END
#

#
# wpa_passphrase
#
b=`grep -n 'wpa_passphrase=' /etc/hostapd/hostapd.conf | awk -F: '{print $1}' | head -1`
b1=`cat /home/pi/quizap/web/ap/newpass`

cat /etc/hostapd/hostapd.conf | sudo sed "${b}s/.*/wpa_passphrase=$b1/" > /tmp/hostapd.conf.tmp
sudo mv /tmp/hostapd.conf.tmp /etc/hostapd/hostapd.conf
#
# END
#

# restart hostapd
echo "restarting hostapd"
sudo service hostapd stop
sudo service hostapd start

echo "restarting dnsmasq"
sudo service dnsmasq stop
sudo service dnsmasq start
