<?php
	$pass=shell_exec('diceware -d - -n 2');
	echo $pass;
?>