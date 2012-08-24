<?php
//process
include('../plugins/EmailAddressValidator.php');

$EV = new EmailAddressValidator;

if($EV->check_email_address(stripslashes($_POST['p_email']))){
	$this->sessionUserInfo['email'] = stripslashes($_POST['p_email']);
}else{
	$this->messageAddError('Not a valid email address.');
}

if(strlen(stripslashes($_POST['p_password1'])) < 4){
	$this->messageAddError('Password must be more than four characters.');
}

if(stripslashes($_POST['p_password1']) == stripslashes($_POST['p_password2']) &&
	isset($this->sessionUserInfo['email']) && strlen(stripslashes($_POST['p_password1'])) >= 4 ){
	//ready to write configuration
	//if no group is specified, put the user in "administrator"
	$this->messageAddAlert('Passwords match. Ready to write initial configuration.');
	$username = stripslashes($_POST['p_username']);
	$email = stripslashes($_POST['p_email']);
	$passwordhash = sha1(stripslashes($_POST['p_password1']));
	if($this->phylobyteDB->exec("
		UPDATE p_users SET username='$username', passwordhash='$passwordhash', status='active', email='$email' WHERE id={$_SESSION['loginid']};
		") > 0) $this->messageAddNotification('Wrote configuration to database.');
	$userquery = $this->phylobyteDB->prepare("SELECT * FROM p_users WHERE id='{$_SESSION['loginid']}';");
	$userquery->execute();
	$userqueryArray = $userquery->fetchAll();
	$this->sessionUserInfo = $userqueryArray[0];
	$accountverify = 'success';
	return true;
}


//build

$this->pageTitle.=' | First Time Setup';

$this->navigationArea.='
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
	<label for="p_username">User Name</label><input type="text" name="p_username" value="'.$this->sessionUserInfo['username'].'"/><br/>
	<label for="p_email">EMail</label><input type="text" name="p_email" value="'.$this->sessionUserInfo['email'].'"/><br/><hr/>
	<label for="p_password1">Password</label><input type="password" name="p_password1" value=""/><br/>
	<label for="p_password2">Password (again)</label><input type="password" name="p_password2" value=""/><br/>
	<label for="p_submit">&nbsp;</label><input type="submit" name="p_submit" value="Save Account Information" />
</form>
</fieldset>
';

return false;
?>
