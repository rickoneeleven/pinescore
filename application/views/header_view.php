<?php
	$this->benchmark->mark('code_start');
	if(!isset($refresh_content)) {
		$refresh_content = "";
	}
?>
<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="utf-8">
	<title><?php echo $title; ?></title>
	<meta name="description" content="<?php echo $description; ?>">
	<meta name="keywords" content="<?php echo $keywords; ?>">
	<meta name="author" content="Ryan Alexander Partington">
	<link rel="stylesheet" type="text/css" href="<?php echo base_url()?>112/Styles.css">
    <meta http-equiv="refresh" content="<?php echo $refresh_content;?>">

    <link rel="apple-touch-icon" sizes="180x180" href="/apple-touch-icon.png">
    <link rel="icon" type="image/png" href="/favicon-32x32.png" sizes="32x32">
    <link rel="icon" type="image/png" href="/favicon-16x16.png" sizes="16x16">
    <link rel="manifest" href="/manifest.json">
    <link rel="mask-icon" href="/safari-pinned-tab.svg" color="#5bbad5">
    <meta name="theme-color" content="#ffffff">
</head>
<body>
<div id="wrap">
<div class="signup_wrap">
<div class="topleft"><a name="first"></a>
    <a href="<?php echo base_url();?>"><img src="<?php echo base_url();?>112/trans-black-horiz.png"/></a></div>
	<div class="signin_form">
		<?php
            echo form_open("auth/user/login");
			if(($this->session->userdata('user_email')!="")) { //is user lgged in check [true] else
				//$this->welcome();
				echo 'Welcome, '. $this->session->userdata('user_email'). " ".anchor('auth/user2/logout', 'Logout'). " | ".anchor('user_options/options', 'Options').
				" | <a href=\"https://trello.com/b/Br0ttuuu/step-programming\">Roadmap</a> 
				| <a href=\"https://www.reddit.com/r/pinescore/\">Forum</a>"
				;
			} else {
				echo '
					<label for="email">Email:</label>
					<input type="text" id="email" name="email" value="" />
					<label for="password">Password:</label>
					<input type="password" id="pass" name="pass" value="" />
					<input type="submit" class="" value="Sign in" /> '.
					anchor('auth/user/register', 'Register')." | ".anchor('auth/user/forgot', 'Forgot').
					" | <a href=\"https://trello.com/b/Br0ttuuu/step-programming\">Roadmap</a> 
					| <a href=\"https://www.reddit.com/r/pinescore/\">Forum</a>"
					;
			} 
    echo " | ".anchor('inmyday/oldtools', 'Tools');
    echo form_close();
		?>
	</div>
</div>
<div class="content">
