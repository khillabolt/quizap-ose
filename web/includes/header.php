<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
	<head>
		<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
		<title>quizap.net - Wifi Password Challenge</title>
		<link rel="stylesheet" href="css/bootstrap.min.css"/>
		<link rel="stylesheet" href="css/font-awesome-4.7.0/css/font-awesome.min.css"/>
		<link rel="stylesheet" href="css/style2.css"/>
		<script src="js/jquery-3.2.1.min.js"></script>
		<script src="js/popper.min.js"></script>
		<script src="js/bootstrap.min.js"></script>
		<script src="js/quizap.js"></script>
	</head>

	<body>
		<div class="site-wrapper">

	      <div class="site-wrapper-inner">
	
	        <div class="cover-container">
	
	          <header class="masthead clearfix">
	            <div class="inner">
	              <h3 class="masthead-brand" data-toggle="tooltip"><i class="fa fa-wifi" aria-hidden="true"></i>quizap<a href="#" id="ip_address" data-toggle="tooltip" data-placement="bottom" title="<?= $ip ?>">.</a>net <small>(beta)</small></h3>
	              
	              <nav class="nav nav-masthead">
	                <a class="nav-link <?php if(basename($_SERVER['PHP_SELF']) == 'index.php') echo ' active'?>" href="/">Home</a>
	                <a class="nav-link <?php if(basename($_SERVER['PHP_SELF']) == 'config.php') echo ' active'?>" href="config.php"><i class="fa fa-lock" aria-hidden="true"></i>&nbsp;Config</a>
	              </nav>
	              
	            </div>
	          </header>
	          
	          <script>$('#ip_address').tooltip();</script>
	          
	          <main role="main" class="inner cover">