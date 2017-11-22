<?php
	require('includes/vars.php');
	
	require('includes/php-crontab-manager/src/CrontabManager.php');
	require('includes/php-crontab-manager/src/CronEntry.php');
	
	use php\manager\crontab\CrontabManager;
	
	$errorMsg = "";
	$successMsg = "";
	$validUser = isset($_SESSION['login']) ? $_SESSION['login'] === true : false;
	
	if(isset($_POST["username"])) {
		try {
			$validUser = $_POST["username"] == $adminuser && $_POST["password"] == $adminpass;
			
			if(!$validUser) {
				$errorMsg = "Invalid username or password.";
			} else {
				$_SESSION["login"] = true;				
			}
			
		} catch(Exception $e) {
			print 'Exception: ' . $e->getMessage();
		}
	}
	
	if($validUser && isset($_POST["configuration"]) && $_POST["configuration"] == "save") {
		$should_reboot = false;
		
		$usernameVal = isset($_POST["adminuser"]) ? $_POST["adminuser"] : "admin";
		$passwordVal = isset($_POST["adminpass"]) ? $_POST["adminpass"] : "quizap";
		
		$skillmulti = isset($_POST["skillmulti"]) ? $_POST["skillmulti"] : "1";
		$hostnameVal = isset($_POST["hostname"]) ? $_POST["hostname"] : "quizap";
		
		$orig_ssid = isset($_POST["orig_ssid"]) ? $_POST["orig_ssid"] : "";
		$ssid = isset($_POST["ssid"]) ? $_POST["ssid"] : "quizap";
		
		$orig_passphrase = isset($_POST["orig_passphrase"]) ? $_POST["orig_passphrase"] : "";
		$passphraseVal = isset($_POST["passphrase"]) ? $_POST["passphrase"] : "changeme";
		$reset_passphrase_at = isset($_POST["reset_passphrase_at"]) ? $_POST["reset_passphrase_at"] : "";
		
		$enabled_wk = isset($_POST["enabled_wk"]) ? $_POST["enabled_wk"] : "";
		$disabled_wk = isset($_POST["disabled_wk"]) ? $_POST["disabled_wk"] : "";
		$enabled_wd = isset($_POST["enabled_wd"]) ? $_POST["enabled_wd"] : "";
		$disabled_wd = isset($_POST["disabled_wd"]) ? $_POST["disabled_wd"] : "";
		
		$ap_disabled = isset($_POST["ap_disabled"]) ? $_POST["ap_disabled"] : "0";
		
		if(strlen($ap_disabled) > 0 && $ap_disabled == "1") {
			shell_exec("sudo /home/pi/quizap/scripts/forwardoff.sh");
		} else {
			shell_exec("sudo /home/pi/quizap/scripts/forwardon.sh");
		}
		
		if (strlen($usernameVal) > 0 && strlen($passwordVal) > 0 && strlen($hostnameVal) > 0 && strlen($ssid) > 0 && strlen($passphraseVal) > 0) {
			try {
				$query="UPDATE config SET adminuser='$usernameVal', adminpass='$passwordVal', skillmulti='$skillmulti', hostname='$hostnameVal', ssid='$ssid', passphrase='$passphraseVal', reset_passphrase_at='$reset_passphrase_at', enabled_wk='$enabled_wk', disabled_wk='$disabled_wk', enabled_wd='$enabled_wd', disabled_wd='$disabled_wd', ap_disabled='$ap_disabled', timestamp=CURRENT_TIMESTAMP";
				
				if($db->exec($query)){
					
					$rows = $db->query("SELECT * FROM config");
					$row = $rows->fetchArray();
					
					$adminuser=$row['adminuser'];
					$adminpass=$row['adminpass'];
					$skillmulti=$row['skillmulti'];
					$ap_disabled=$row['ap_disabled'];
					
					$hostname=$row['hostname'];
					$ssid=$row['ssid'];
					$newpass=$row['passphrase'];
					$reset_passphrase_at=$row['reset_passphrase_at'];
					
					file_put_contents('ap/hostname', $hostname);
					
					if($orig_ssid != $ssid){
						file_put_contents('ap/ssid', $ssid);
						$should_reboot=true;
					}
					
					if($orig_passphrase != $newpass){
						file_put_contents('ap/newpass', $newpass);
					}
					
					try {
						/*
						* CrontabManager entries
						*/
						$crontab = new CrontabManager();
						
						# remove all existing jobs
						$existingJobs = explode("\n", $crontab->listJobs()); // get the old jobs
						if (is_array($existingJobs)) {
							foreach ($existingJobs as $existingJob) {
								if ($existingJob != '') {
									$crontab->deleteJob(substr($existingJob, strpos($existingJob, '# ') + 2));
									$crontab->save(false);
								}
							}
						}
						
						if(strlen($reset_passphrase_at) > 0){
							$job = $crontab->newJob();
							$job->on($reset_passphrase_at . ' * * *')->doJob("sudo /home/pi/quizap/scripts/newpass.sh");
							$crontab->add($job);
						}
						
						if(strlen($enabled_wk) > 0){
							$job = $crontab->newJob();
							$job->on($enabled_wk . ' * * 0-4')->doJob("sudo /home/pi/quizap/scripts/forwardon.sh");
							$crontab->add($job);
						}
						
						if(strlen($disabled_wk) > 0){
							$job = $crontab->newJob();
							$job->on($disabled_wk . ' * * 0-4')->doJob("sudo /home/pi/quizap/scripts/forwardoff.sh");
							$crontab->add($job);
						}
						
						if(strlen($enabled_wd) > 0){
							$job = $crontab->newJob();
							$job->on($enabled_wd . ' * * 5,6')->doJob("sudo /home/pi/quizap/scripts/forwardon.sh");
							$crontab->add($job);
						}
						
						if(strlen($disabled_wd) > 0){
							$job = $crontab->newJob();
							$job->on($disabled_wd . ' * * 5,6')->doJob("sudo /home/pi/quizap/scripts/forwardoff.sh");
							$crontab->add($job);
						}
						
						$crontab->save();
						
					} catch(Exception $e) {
						print 'Exception: '.$e->getMessage();
					}
										
					if($should_reboot){
						// This will also apply settings
						file_put_contents('ap/reboot.server', 'Reboot now');
					}else{
						file_put_contents('ap/apply.settings', 'Apply settings');
					}
					
					$successMsg="Configuration changes were successfully applied and will be updated shortly. Note that the server may restart and be unavailable for a short period of time.";
					
					$_SESSION["login"] = "";
				} // sql didn't run right, cannot continue
				
			} catch(PODException $e) {
				print 'Exception: '.$e->getMessage();
			}
		}
	}
?>

<?php require('includes/header.php'); ?>

	<p align="center"><i class="fa fa-cogs fa-4x" aria-hidden="true"></i></p>
	<h1 class="display-4">Configuration</h1>
	
	<script>
		function disableAp(shouldBe){
			$('#ap_disabled').val(shouldBe ? "1" : "0");
			document.getElementById("myform").submit();
		}
		
		function generatePassword(ctl){
			$.get( "suggest.php", function( data ) {
				$('#' + ctl).val(data);
			});
		}
	</script>
	
	<form method="POST" name="myform" id="myform" action="config.php" class="form-signin">
	<?php if($validUser) { ?>
		<input type='hidden' name='configuration' value='save' />
		<input type='hidden' name='orig_ssid' value='<?= $ssid ?>' />
		<input type='hidden' name='orig_passphrase' value='<?= $newpass ?>' />
		<input type='hidden' name='ap_disabled' id='ap_disabled' value='<?= $ap_disabled ?>' />
		
		<?php if(strlen($successMsg) > 0) { ?>
			<div class="p-3 mb-2 bg-success text-white"><?= $successMsg ?></div>
		<?php } ?>
		
		<div class="form-group" style="text-align:left;">
			<label for="adminuser">Admin Username:</label>
			<input type="text" name="adminuser" id="adminuser" class="form-control" placeholder="Admin Username" required="required" value="<?= $adminuser ?>"/>
		</div>
		
		<div class="form-group" style="text-align:left;">
			<label for="adminpass">Admin Password:</label>
			<div class="input-group mb-2 mb-sm-0">
				<div id="suggest_admin_pass" class="input-group-addon" onclick="javascript:generatePassword('adminpass');" data-toggle="tooltip" data-placement="left" title="Generate a new password"><i class="fa fa-key" aria-hidden="true"></i></div>
				<input type="text" name="adminpass" id="adminpass" class="form-control" placeholder="Admin Password" required="required" value="<?= $adminpass ?>"/>
			</div>
			<script>$('#suggest_admin_pass').tooltip();</script>
		</div>
		
		<div class="form-group text-left">
			<label for="skillmulti">Skill Level:</label>
			<select name="skillmulti" id="skillmulti" id="enabled_wk" class="form-control">
				<option value="1">easy</option>
				<option value="2">intermediate</option>
				<option value="3">advanced</option>
			</select>
			<small id="skillmultiHelp" class="form-text">Change skill level to adjust complexity of questions</small>
			<script>$('#skillmulti').val('<?= $skillmulti ?>');</script>
		</div>
		
		<div class="form-group" style="text-align:left;">
			<label for="hostname">Hostname:</label>
			<input type="text" name="hostname" id="hostname" class="form-control" placeholder="Username" required="required" value="<?= $hostname ?>"/>
			<small class="form-text">Current IP address: <?= $ip ?></small>
		</div>
		
		<div class="form-group" style="text-align:left;">	
			<label for="passphrase">ssid:</label>
			<input type="text" name="ssid" id="ssid" class="form-control" placeholder="ssid" required="required" value="<?= $ssid ?>"/>
			<small id="ssidHelp" class="form-text">Changing ssid requires a reboot and will make the system unavailable</small>
		</div>
		
		<div class="form-group" style="text-align:left;">	
			<label for="passphrase">Passphrase:</label>
			<div class="input-group mb-2 mb-sm-0">
				<div id="suggest_passphrase" class="input-group-addon" onclick="javascript:generatePassword('passphrase');" data-toggle="tooltip" data-placement="left" title="Generate a new passphrase"><i class="fa fa-key" aria-hidden="true"></i></div>
				<input type="text" name="passphrase" id="passphrase" class="form-control" placeholder="Passphrase" required="required" value="<?= $newpass ?>"/>
			</div>
			<script>$('#suggest_passphrase').tooltip();</script>
		</div>
		
		<div class="form-group" style="text-align:left;">	
			<label for="reset_passphrase_at"><small>Reset password daily at:</small></label>
			<select name="reset_passphrase_at" id="reset_passphrase_at" class="form-control">
				<option/>
				<?php require('includes/timeoptions.php'); ?>
			</select>
			<script>$('#reset_passphrase_at').val('<?= $reset_passphrase_at ?>');</script>
		</div>
		
		<div class="form-row">
			<div class="form-group col-md-6">Weekdays</div>
			<div class="form-group col-md-6">Weekends</div>
		</div>
		
		<div class="form-row">
			<div class="form-group col-md-3">
				<label for="enabled_wk">Enabled:</label>
				<select name="enabled_wk" id="enabled_wk" id="enabled_wk" class="form-control">
					<option/>
					<?php require('includes/timeoptions.php'); ?>
				</select>
				<script>$('#enabled_wk').val('<?= $enabled_wk ?>');</script>
			</div>
			<div class="form-group col-md-3">
				<label for="disabled_wk">Disabled:</label>
				<select name="disabled_wk" id="disabled_wk" id="disabled_wk" class="form-control">
					<option/>
					<?php require('includes/timeoptions.php'); ?>
				</select>
				<script>$('#disabled_wk').val('<?= $disabled_wk ?>');</script>
			</div>
			<div class="form-group col-md-3">
				<label for="enabled_wd">Enabled:</label>
				<select name="enabled_wd" id="enabled_wd" id="enabled_wd" class="form-control">
					<option/>
					<?php require('includes/timeoptions.php'); ?>
				</select>
				<script>$('#enabled_wd').val('<?= $enabled_wd ?>');</script>
			</div>
			<div class="form-group col-md-3">
				<label for="disabled_wd">Disabled:</label>
				<select name="disabled_wd" id="disabled_wd" id="disabled_wd" class="form-control">
					<option/>
					<?php require('includes/timeoptions.php'); ?>
				</select>
				<script>$('#disabled_wd').val('<?= $disabled_wd ?>');</script>
			</div>
		</div>
		
		<p/>
		<?php if(strlen($_SESSION["login"]) > 0) { // after saving, user users are no long able to save. ?>
			<div class="form-row">
				<div class="form-group col-md-4">
					<button class="btn btn-lg btn-dark btn-block" style="margin-top: 20px;" type="submit">Save</button>
				</div>
				<div class="form-group col-md-4">
					<button class="btn btn-lg btn-light btn-block" style="margin-top: 20px;" type="button" onclick="javascript:location.href='/';">Cancel</button>
				</div>
				<?php if(strlen($ap_disabled) == 0 || $ap_disabled == "0") { ?>
				<div class="form-group col-md-4">
					<button class="btn btn-lg btn-danger btn-block" style="margin-top: 20px;" type="button" onclick="javascript:disableAp(true);">Disable</button>
				</div>
				<?php } else { ?>
				<div class="form-group col-md-4">
					<button class="btn btn-lg btn-success btn-block" style="margin-top: 20px;" type="button" onclick="javascript:disableAp(false);">Enable</button>
				</div>
				<?php } ?>
			</div>
		<?php } else { ?>
			<button class="btn btn-lg btn-light btn-block" style="margin-top: 20px;" type="button" onclick="javascript:location.href='/';">Cancel</button>
			<script>setTimeout("location.href='/';", 10000);</script>
		<?php } ?>
		
		<div class="text-danger"><?= $errorMsg ?></div>
		
	<?php } else { ?>
	
		<h2 class="form-signin-heading">Please sign in</h2>
		
		<div class="form-group" style="text-align:left;">
			<label for="username" class="sr-only">Username</label>
			<input type="text" name="username" id="username" class="form-control" placeholder="Username" required="required" autofocus=""/>
		</div>
		
		<div class="form-group" style="text-align:left;">
			<label for="password" class="sr-only">Password</label>
			<input type="password" name="password" id="password" class="form-control" placeholder="Password" required="required"/>
		</div>
		
		
		<button class="btn btn-lg btn-dark btn-block" type="submit">Login</button>
		
		<div class="text-danger"><?= $errorMsg ?></div>
		
	<?php } ?>
	</form>
	
<?php require('includes/footer.php'); ?>