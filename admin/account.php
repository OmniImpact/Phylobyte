<?php
//breadcrumbs
self::$breadcrumbs.='<a href="?">Home</a> &raquo; <a href="?phylobyte=account">Account</a>';

//process

include('../plugins/EmailAddressValidator.php');

$EV = new EmailAddressValidator;

if(isset($_POST['p_submit'])){

	$userquery = self::$phylobyteDB->prepare("SELECT * FROM p_users WHERE id='{$_SESSION['loginid']}';");
	$userquery->execute();
	$userqueryArray = $userquery->fetchAll();
	$userqueryArray = $userqueryArray[0];
	
	self::$sessionUserInfo = $userqueryArray;

	$p_currentpass = isset($_POST['p_currentpass']) ? stripslashes($_POST['p_currentpass']) : '';
	$p_password1 = isset($_POST['p_password1']) ? stripslashes($_POST['p_password1']) : '';
	$p_password2 = isset($_POST['p_password2']) ? stripslashes($_POST['p_password2']) : '';
	$p_username = isset($_POST['p_username']) ? stripslashes($_POST['p_username']) : '';
	$p_email = isset($_POST['p_email']) ? stripslashes($_POST['p_email']) : '';
	$p_name = isset($_POST['p_name']) ? stripslashes($_POST['p_name']) : '';


	if(sha1($p_currentpass) == self::$sessionUserInfo['passwordhash']){
		//user entered correct password, ready to check updates

		if(strlen($p_password1) < 5 && $p_password1 != null){
			$this->messageAddError('Password must be more than five characters.');
		}elseif ($p_password1 == $p_password2 && $p_password1 != null) {
			$passwordhash = sha1($p_password1);
			$this->messageAddNotification('Updating Password...');
		}else {
			$passwordhash = self::$sessionUserInfo['passwordhash'];
		}
		
		if(trim($p_username) != ''){
			$username = $p_username;
			$this->messageAddNotification('Updating User Name...');
		}else {
		    $username = self::$sessionUserInfo['username'];
		}
		
	}elseif($p_username != null || $p_password1 != null || $p_password2 != null){
		$this->messageAddError('There was a problem updating your login details.');
		$username = self::$sessionUserInfo['username'];
		$passwordhash = self::$sessionUserInfo['passwordhash'];
	}else{
		$username = self::$sessionUserInfo['username'];
		$passwordhash = self::$sessionUserInfo['passwordhash'];
	}

	if(trim($p_email) != null){
		$email = $p_email;
	}else{
		$email = self::$sessionUserInfo['email'];
	}

	if(trim($p_name) != null){
		$name = $p_name;
	}else{
		$name = self::$sessionUserInfo['name'];
	}

	//now that any potential changes have been saved to the session, update the database
	$name = self::$phylobyteDB->quote($name);
	if(self::$phylobyteDB->exec("
			UPDATE p_users SET username='$username', passwordhash='$passwordhash', status='active', email='$email', name=$name
			WHERE id={$_SESSION['loginid']};
			") > 0) $this->messageAddNotification('Your changes have been saved.');

	$userquery = self::$phylobyteDB->prepare("SELECT * FROM p_users WHERE id='{$_SESSION['loginid']}';");
	$userquery->execute();
	$userqueryArray = $userquery->fetchAll();
	$userqueryArray = $userqueryArray[0];
	self::$sessionUserInfo = $userqueryArray;

}
		
//build

$this->pageTitle.=' | Account Settings';

$this->docArea = '
<h3>Your Account Settings</h3>
<p>
Fill in the rest of your account information to personalize Phylobyte and enable all features to work fully.
</p>

<h3>Changing Login Details</h3>
<p>
To change your login details, you will need to provide your current password. Your login details are your user name and password that you use to sign in to Phylobyte. When changing your password, please ensure that it is at least five characters.
</p>

<h3>Password Tips</h3>
<p>
Although Phylobyte only enforces passwords more than five characters, there are some tips you can follow to make them harder to guess. Longer passwords take longer to break. For the best security, choose a password more than 10 characters. Make sure that your password is not a dictionary word or phrase. You can do this by including a number, mixing capital and lowercase letters, or using punctuation.
</p>

';

$name = self::$sessionUserInfo['name'] ?? '';
$email = self::$sessionUserInfo['email'] ?? '';
$username = self::$sessionUserInfo['username'] ?? '';

$this->pageArea = <<<HTML
<script type="text/javascript" src="../plugins/nicEdit.js"></script>

<!--<script type="text/javascript">
bkLib.onDomLoaded(function() {
	new nicEditor({buttonList : ['bold','italic','underline','ol','ul'], iconsPath : '../plugins/nicEditorIcons.gif'}).panelInstance('p_description');
});
</script>-->

<fieldset>
	<legend>My Acount Details</legend>
<form action="?phylobyte=account" method="POST">

	<label for="p_name">Nick Name</label><input type="text" name="p_name" value="$name"/><br/>
	<label for="p_email">eMail Address</label><input type="text" name="p_email" value="$email"/><br/>
	<label for="p_submit">&nbsp;</label><input type="submit" name="p_submit" value="Save Account Details" />
		<div class="ff">&nbsp;</div>
</form>
</fieldset>

<fieldset>
	<legend>Change Login Details</legend>
<form action="?phylobyte=account" method="POST">
	<label for="p_currentpass">Current Password</label><input type="password" name="p_currentpass" value=""/><hr/>
	<label for="p_username">User Name</label><input type="text" name="p_username" value="$username"/><br/>
	<label for="p_password1">Password</label><input type="password" name="p_password1" value=""/><br/>
	<label for="p_password2">Password (again)</label><input type="password" name="p_password2" value=""/><br/>
	<label for="p_submit">&nbsp;</label><input type="submit" name="p_submit" value="Save Login Details" />
		<div class="ff">&nbsp;</div>
</form>
</fieldset>
HTML;
?>