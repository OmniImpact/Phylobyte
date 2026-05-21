<?php
//process
include('../plugins/EmailAddressValidator.php');

$EV = new EmailAddressValidator;

$p_email = isset($_POST['p_email']) ? trim(stripslashes($_POST['p_email'])) : '';
$p_password1 = isset($_POST['p_password1']) ? trim(stripslashes($_POST['p_password1'])) : '';
$p_password2 = isset($_POST['p_password2']) ? trim(stripslashes($_POST['p_password2'])) : '';

if($EV->check_email_address($p_email)){
	phylobyte::$sessionUserInfo['email'] = $p_email;
}else{
	phylobyte::messageAddError('Not a valid email address.');
}

if(strlen($p_password1) < 4){
	phylobyte::messageAddError('Password must be more than four characters.');
}

if($p_password1 == $p_password2 &&
	isset(phylobyte::$sessionUserInfo['email']) && strlen($p_password1) >= 4 ){
	//ready to write configuration
	//if no group is specified, put the user in "administrator"
	phylobyte::messageAddAlert('Passwords match. Ready to write initial configuration.');
	$username = isset($_POST['p_username']) ? trim(stripslashes($_POST['p_username'])) : '';
	$email = $p_email;
	$passwordhash = sha1($p_password1);
	if(phylobyte::$phylobyteDB->exec("
		UPDATE p_users SET username='$username', passwordhash='$passwordhash', status='active', email='$email' WHERE id={$_SESSION['loginid']};
		") > 0) phylobyte::messageAddNotification('Wrote configuration to database.');
	$userquery = phylobyte::$phylobyteDB->prepare("SELECT * FROM p_users WHERE id='{$_SESSION['loginid']}';");
	$userquery->execute();
	$userqueryArray = $userquery->fetchAll();
	phylobyte::$sessionUserInfo = $userqueryArray[0];
	$accountverify = 'success';
	return true;
}


//build

$this->pageTitle.=' | First Time Setup';

phylobyte::$navigationArea.='
<ul>
<li><a href="?">Home</a></li>
<li><a href="?phylobyte=logout">Log Out</a></li>
</ul>
';

$this->docArea = '
<h3>Why does it say I am logged in with "override"?</h3>
<p>
When a new install is performed, or when a new administrator is trying to log in, their account may have "override" status. This means that they may have limited account information. Phylobyte needs the rest of the information filled in before it can function properly.
</p>
<h3>Help! I missed what the error message was!</h3>
<p>
If you miss one of the messages that Phylobyte is trying to tell you, don\'t worry. Click the tab to the left that says "Message Pile". The Pile will drop down to display the recent messages, such as invalid fields or missing information.
</p>';

$this->pageArea = '
<div style="display: block; text-align: center;">
	<img src="gfx/logo_color_md.png" /><br/>
	<h2>Welcome! To begin, please set up your account.</h2>
</div>

<div class="floatfix">&nbsp;</div>

<fieldset>
	<legend>Account Setup</legend>
<form action="?" method="POST">
	<label for="p_username">User Name</label><input type="text" name="p_username" value="'.(isset(phylobyte::$sessionUserInfo['username']) ? phylobyte::$sessionUserInfo['username'] : '').'"/><br/>
	<label for="p_email">EMail</label><input type="text" name="p_email" value="'.(isset(phylobyte::$sessionUserInfo['email']) ? phylobyte::$sessionUserInfo['email'] : '').'"/><br/><hr/>
	<label for="p_password1">Password</label><input type="password" name="p_password1" value=""/><br/>
	<label for="p_password2">Password (again)</label><input type="password" name="p_password2" value=""/><br/>
	<label for="p_submit">&nbsp;</label><input type="submit" name="p_submit" value="Save Account Information" />
	<div class="ff">&nbsp;</div>
</form>
</fieldset>
';

return false;
?>