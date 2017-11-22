<?php
	session_start();
	
	$db = new SQLite3('db/quizap.db');
	
	$rows = $db->query("SELECT * FROM config");
	$row = $rows->fetchArray();
	
	$adminuser=$row['adminuser'];
	$adminpass=$row['adminpass'];
	$skillmulti=$row['skillmulti'];
	
	$hostname=file_get_contents('ap/hostname');
	
	$ssid=$row['ssid'];
	
	$newpass=file_get_contents('ap/newpass');
	$reset_passphrase_at=$row['reset_passphrase_at'];
	
	$enabled_wk=$row['enabled_wk'];
	$disabled_wk=$row['disabled_wk'];
	$enabled_wd=$row['enabled_wd'];
	$disabled_wd=$row['disabled_wd'];
	$ap_disabled=$row['ap_disabled'];
	
	// Server ip address
	$ip = shell_exec("/sbin/ifconfig eth0 | grep 'inet ' | cut -d: -f2 | awk '{ print $2}'");
?>