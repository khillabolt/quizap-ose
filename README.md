# quizap.net - Access Point README.md

## Configure RaspberryPi

`sudo raspi-config`

* pi user password
* expand filesystem
* set wifi-country
* set timezone

**edit the `/boot/config.txt` file**
```
hdmi_force_hotplug=1
hdmi_drive=2
#hdmi_group=1
#hdmi_mode=16
disable_splash=1
```

__There seems to be a current issue where the quizap application will be in VGA mode, if the device is restarted without the HDMI device is on. Current workaround is to just power cycle the unix with HDMI on__

**Check everything up to date**

`sudo apt-get update && sudo apt-get upgrade -y`

# Reboot and SSH back in to RaspberryPI

`sudo reboot`

**Install all the things (this will take awhile)**

`sudo apt-get install joe apache2 php php-sqlite3 chromium-browser x11-xserver-utils unclutter dnsmasq hostapd diceware -y`

(optional remove libreoffice and wolfram-engine if they are installed)

```
sudo apt-get remove --purge libreoffice-*
sudo apt-get remove --purge wolfram-engine
sudo apt-get clean
```

**Nice to have, but not necessary is unattended automatic upgrades**
```
sudo apt-get install unattended-upgrades
sudo dpkg-reconfigure --priority=low unattended-upgrades
```

__follow prompts to configure__

**Enable crontab for (we'll be using it later to schedule jobs)**

`sudo crontab -u www-data -e`

**Also a good time to allow www-data to sudo the forward(off|on) scripts and the `tee` command, as it will be used later on. Run `sudo visudo` and add the following**

```
www-data ALL=(ALL) NOPASSWD: /home/pi/quizap/scripts/newpass.sh
www-data ALL=(ALL) NOPASSWD: /home/pi/quizap/scripts/hostapd.conf.sh
www-data ALL=(ALL) NOPASSWD: /home/pi/quizap/scripts/forwardoff.sh
www-data ALL=(ALL) NOPASSWD: /home/pi/quizap/scripts/forwardon.sh
www-data ALL=(ALL) NOPASSWD: /usr/bin/tee
www-data ALL=(ALL) NOPASSWD: /bin/mv
www-data ALL=(ALL) NOPASSWD: /bin/sed
www-data ALL=(ALL) NOPASSWD: /etc/hostname
www-data ALL=(ALL) NOPASSWD: /etc/hosts
www-data ALL=(ALL) NOPASSWD: /etc/hostapd/hostapd.conf
www-data ALL=(ALL) NOPASSWD: /usr/sbin/service
```

---
**Open up the dhcpcd configuration file with `sudo joe /etc/dhcpcd.conf` and configure our static IP on wlan0:**

```
interface wlan0
static ip_address=192.168.0.1/24
static routers=192.168.0.1
static domain_name_servers=8.8.8.8 8.8.4.4
```

**Restart `dhcpcd` with `sudo service dhcpcd restart` and then reload the configuration for `wlan0` with `sudo ifdown wlan0; sudo ifup wlan0`.**

**Configure `hostapd`. Create a new configuration file with `sudo joe /etc/hostapd/hostapd.conf` with the following contents:**

```
# This is the name of the WiFi interface we configured above
interface=wlan0
driver=nl80211
ssid=quizap					# <-- /home/pi/quizap/web/ap/hostname
hw_mode=g
channel=7
wmm_enabled=0
ht_capab=[HT40][SHORT-GI-20][DSSS_CCK-40]
macaddr_acl=0
auth_algs=1
ignore_broadcast_ssid=0
wpa=2
wpa_key_mgmt=WPA-PSK
wpa_passphrase=changeme		# <-- /home/pi/quizap/web/ap/newpass
rsn_pairwise=CCMP
```

**Check if it's working at this stage by running `sudo /usr/sbin/hostapd /etc/hostapd/hostapd.conf`**

**Open up the default configuration file with `sudo joe /etc/default/hostapd` and find the line `#DAEMON_CONF=""` and replace it with `DAEMON_CONF="/etc/hostapd/hostapd.conf"`.**

**Configure `dnsmasq`**

```
sudo mv /etc/dnsmasq.conf /etc/dnsmasq.conf.orig  
sudo joe /etc/dnsmasq.conf
```

Paste into new file:

```
interface=wlan0                                 # Use interface wlan0
#bind-interfaces                                # Bind to the interface to make sure we aren't sending things elsewhere  
server=8.8.8.8                                  # Forward DNS requests to Google DNS
server=8.8.4.4
domain-needed                                   # Don't forward short names
bogus-priv                                      # Never forward addresses in the non-routed address spaces.
dhcp-range=192.168.0.50,192.168.0.150,12h       # Assign IP addresses between 192.168.0.50 and 192.168.0.150 with a 12 hour lease time
```

---
**Set up IPV4 forwarding**

*Open up the sysctl.conf file with `sudo joe /etc/sysctl.conf`, and remove the `#` from the beginning of the line containing `net.ipv4.ip_forward=1`. This will enable it on the next reboot, but because we are impatient, activate it immediately with:*

`sudo sh -c "echo 1 > /proc/sys/net/ipv4/ip_forward"`

*We also need to share our Pi's internet connection to our devices connected over WiFi by the configuring a NAT between our `wlan0` interface and our `eth0` interface. We can do this using the following commands:*

```
sudo iptables -t nat -A POSTROUTING -o eth0 -j MASQUERADE  
sudo iptables -A FORWARD -i eth0 -o wlan0 -m state --state RELATED,ESTABLISHED -j ACCEPT  
sudo iptables -A FORWARD -i wlan0 -o eth0 -j ACCEPT 
```

We need these rules to be applied every time we reboot the Pi, so run `sudo sh -c "iptables-save > /etc/iptables.ipv4.nat"` to save the rules to the file `/etc/iptables.ipv4.nat`. Now we need to run this after each reboot, so open the `rc.local` file with `sudo joe /etc/rc.local` and just above the line exit 0, add the following line:

```
iptables-restore < /etc/iptables.ipv4.nat
```

**Now we just need to start our services:**

```
sudo service hostapd start  
sudo service dnsmasq start
```

---
## BEGIN SETUP WEB SOFTWARE AT LOCALHOST

**Login as pi user and place

```
cd ~
git clone https://github.com/khillabolt/quizap-ose quizap
sudo mv /var/www/html/ /var/www/html.orig
sudo ln -s ~/quizap/web /var/www/html
chmod -R 757 /home/pi/quizap/web/ap 
chmod -R 757 /home/pi/quizap/web/db
```

**Certain configuration changes will require a server reboot, or configuration changes. To handle, create a script as root by `sudo -i` then `joe /usr/local/sbin/checksettings.sh`**

Paste the following

```
#!/bin/bash

if [ -f /home/pi/quizap/web/ap/reboot.server ]; then
	rm -f /home/pi/quizap/web/ap/reboot.server
	/home/pi/quizap/scripts/hostapd.conf.sh
	/sbin/shutdown -r now
fi

if [ -f /home/pi/quizap/web/ap/apply.settings ]; then
	rm -f /home/pi/quizap/web/ap/apply.settings
	/home/pi/quizap/scripts/hostapd.conf.sh
fi
```

Mark script as executable `chmod +x /usr/local/sbin/checkreboot.sh` and add to root crontab `crontab -e`

```
* * * * * /usr/local/sbin/checkreboot.sh
```

__(optional) Add the following lines to the pi user's crontab (note you may need to exit from root (type `exit`) to enable automatic passphrase changes to happen on a daily basis. Adjust the times accordingly to meet your needs.__

```
# weekdays
0 9  * * 1-5 /home/pi/quizap/scripts/newpass.sh > /dev/null 2>&1

# weekends
0 7  * * 0,6 /home/pi/quizap/scripts/newpass.sh > /dev/null 2>&1
``` 

## END SETUP WEB SOFTWARE AT LOCALHOST

---
**Setting up Kiosk mode**

`sudo joe ~/.config/lxsession/LXDE-pi/autostart`

*This screen should match the below:*

```
#lxpanel --profile LXDE-pi
#@pcmanfm --desktop --profile LXDE-pi
#@xscreensaver -no-splash
@point-rpi

@xset s off
@xset -dpms
@xset s noblank

@sed -i 's/"exited_cleanly": false/"exited_cleanly": true/' ~/.config/chromium-browser/Default/Preferences

@chromium-browser --kiosk --no-first-run --noerrdialogs --disable-infobars --incognito --disable-java --disable-plugins http://localhost
```

# Splash screen
[https://scribles.net/customizing-boot-up-screen-on-raspberry-pi/]

```
sudo mkdir /usr/share/plymouth/themes/quizap
cd /usr/share/plymouth/themes/quizap
```

Create quizap.plymouth file by calling `sudo joe quizap.plymouth` and add the following:

```
[Plymouth Theme]
Name=quizap
Description=quizap.net theme
ModuleName=script

[script]
ImageDir=/usr/share/plymouth/themes/quizap
ScriptFile=/usr/share/plymouth/themes/quizap/quizap.script
```

Now create `sudo joe quizap.script` and add:

```
screen_width = Window.GetWidth();
screen_height = Window.GetHeight();

theme_image = Image("splash.png");
resized_wallpaper_image = theme_image.Scale(screen_width, screen_height);
sprite = Sprite(resized_wallpaper_image);
sprite.SetZ(-100);

message_sprite = Sprite();
message_sprite.SetPosition(screen_width * 0.1, screen_height * 0.8, 10000);

fun message_callback (text) {
    my_image = Image.Text(text, 1, 1, 1);
    message_sprite.SetImage(my_image);
}

Plymouth.SetUpdateStatusFunction(message_callback);
```

Copy splash screen png file to this directory `sudo cp ~/splash.png /usr/share/plymouth/themes/quizap/splash.png`

**Update plymouth to use this them**

`sudo plymouth-set-default-theme quizap`

*Remove Boot Messages*

`sudo joe /boot/cmdline.txt`

Then, replace `console=tty1` with `console=tty3`. This redirects boot messages to tty3.

Still in `/boot/cmdline.txt` add the following to the end of the line:

`logo.nologo vt.global_cursor_default=0`

*Here are brief explanations:*

‘splash’ : enables splash image
‘quiet’ : disable boot message texts
‘plymouth.ignore-serial-consoles’ : not sure about this but seems it’s required when use Plymouth.
‘logo.nologo’ : removes Raspberry Pi logo in top left corner.
‘vt.global_cursor_default=0’ : removes blinking cursor.

# sudo reboot

---
## (optional) Adding quizap as a Zeroconf advertised service.

Create the quizap service

`sudo /etc/avahi/services/quizap.service`

And paste the following information

```
<?xml version="1.0" standalone='no'?>
<!DOCTYPE service-group SYSTEM "avahi-service.dtd">
<service-group>
  <name>quizap</name>
  <service>
    <type>_http._tcp</type>
    <port>80</port>
  </service>
</service-group>
```

Restart the `avahi daemon` by issuing the following commands

```
sudo systemctl stop avahi-daemon
sudo systemctl start avahi-daemon
```

If all goes well, the service will now be published and available on the network for discovery


# FIN


