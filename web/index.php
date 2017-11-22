<?php
	require('includes/vars.php');
	
	// clear any sessions
	if(isset($_SESSION["login"])){
		$_SESSION["login"] = "";
	}
	
	$m = '';
	$newpass = '';
		
	if (isset($_POST['answer'])) {
		if(is_numeric($_POST['answer']) && is_numeric($_POST['first']) && is_numeric($_POST['second'])) {
			$first = intval($_POST['first']);
			$second = intval($_POST['second']);
			$answer = intval($_POST['answer']);
			$op = strval($_POST['operand']);
			
			$p = eval('return ' . $first . $op . $second . ';');
			
			try {
				$correct = $p == $answer ? 1 : 0;
				$db->exec("INSERT INTO question (left, op, right, correct) VALUES ('$first','$op','$second',$correct)");
			} catch(PDOException $e) {
				print 'Exception inserting: '.$e->getMessage();
			}
			
			if($p == $answer){
				$newpass = file_get_contents('ap/newpass');
			} else {
				header("Location: index.php");
				exit();
			}
		}
	}
	
	// Math problems - phase1
	$level = 10;
	$first = Rand(1, ($level * intval($skillmulti)));
	$second = Rand(1,  (($level * intval($skillmulti)) * Rand(2, 5)));
	$operands = array("+","-","*");		// no division, rounding.. doh...
	$operand = $operands[Rand(0,2)];
?>

<?php require('includes/header.php'); ?>
		
	<?php if(strlen($newpass) > 0) { ?>
		<p align="center"><i class="fa fa-wifi fa-4x" aria-hidden="true"></i></p>
		<h3>Today's Wifi Password (ssid: <b><?= $ssid ?></b>) is:<h3>
		<h1 class="display-4"><?= $newpass; ?></h1>
		
		<script>setTimeout("location.href = self.location;", 60000);</script>
	<?php } else { ?>
	
		<?php if(strlen($ap_disabled) == 0 || $ap_disabled == "0") { ?>
	
			<p align="center"><i class="fa fa-question-circle fa-4x" aria-hidden="true"></i></p>
			<h1 class="display-4">Wifi Password Challenge</h1>
			<hr/>
			
			<form method="POST" name="myform" class="form-signin">
				<input type='hidden' name='first' value='<?= $first ?>' />
				<input type='hidden' name='second' value='<?= $second ?>' />
				<input type="hidden" id="operand" name="operand" value="<?= $operand ?>"/>
				
				<?= "<h1 class='mb-4'>What is " . $first . " " . $operand . " " . $second . "?" . "</h1>" ?>
				
				<div class="form-group" style="text-align:left;">	
					<label for="answer" class="sr-only">Your answer</label>
					<input type="text" name="answer" id="answer" class="form-control" placeholder="Your answer" required="required"/>
				</div>
				
				<button class="btn btn-lg btn-dark" type="submit">Submit</button>
			</form>
			
			<?php $secs = 60 * intval($skillmulti) / 2; ?>
			
			<p id="div_timer" style="margin-top: 20px;"><?= $secs ?> seconds remaining...</p>
			<script>
				var timer = setInterval("countdown()", 1000);
				
				var seconds = <?= $secs ?>;
				function countdown() {
					seconds=seconds-1;
					$("#div_timer").html(seconds + " seconds remaining...");
					
					if(seconds <= 0){
						clearInterval(timer);
						location.href = self.location;
					}
				}
				
				window.onload = function() {
					document.getElementById("answer").focus();
				}
			</script>
		
		<?php } else { ?>
			
			<h1 class="display-4">Wifi is Disabled</h1>
			
			<script>
				var timer = setInterval("countdown()", 1000);
				
				var seconds = 60;
				function countdown() {
					seconds=seconds-1;
					
					if(seconds <= 0){
						clearInterval(timer);
						location.href = self.location;
					}
				}
			</script>
			
		<?php } ?>
		
	<?php } ?>
	
<?php require('includes/footer.php'); ?>