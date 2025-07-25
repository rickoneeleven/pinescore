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
    <?php if ($refresh_content && $refresh_content > 0): ?>
    <!-- AJAX refresh enabled instead of meta refresh -->
    <script>
        window.icmpRefreshConfig = {
            refreshRate: <?php echo $refresh_content * 1000; ?>,
            groupId: <?php echo isset($group_id) ? $group_id : 'null'; ?>,
            autoStart: true
        };
        window.ownerMatchesTable = <?php echo isset($owner_matches_table) && $owner_matches_table ? 'true' : 'false'; ?>;
    </script>
    <script src="<?php echo base_url()?>js/icmp-table-updater.js?v=<?php echo time(); ?>-defaultfix" defer></script>
    <?php else: ?>
    <meta http-equiv="refresh" content="<?php echo $refresh_content;?>">
    <?php endif; ?>

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
<br>
<?php
if (!empty($last_truncation_timestamp)) {
    $truncation_time = strtotime($last_truncation_timestamp);
    $three_days_ago = strtotime('-3 days');

    if ($truncation_time > $three_days_ago) {
        $expiry_date = date('Y-m-d H:i', $truncation_time + (3 * 24 * 60 * 60));
        echo '<div style="background-color: #ffc; border: 1px solid #e0c600; padding: 15px; margin: 10px 0; color: #333; text-align: center;">';
        echo '<strong>System Maintenance Notice:</strong> The ping history was automatically archived on ' . date('Y-m-d', $truncation_time) . ' when the table reached 1 billion records (database integer limit). ';
        echo 'This is a long-term automatic cleanup process to ensure systems run autonomously for years without requiring user maintenance. ';
        echo 'Historical data will fully repopulate by ' . $expiry_date . '.';
        echo '</div>';
    }
}
?>
