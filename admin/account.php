<?php
//breadcrumbs
$this->breadcrumbs.='<a href="?">Home</a> &raquo; <a href="?phylobyte=account">Account</a>';

//process

include('../plugins/EmailAddressValidator.php');

$EV = new EmailAddressValidator;

if(isset($_POST['p_submit'])){

	$userquery = $this->phylobyteDB->prepare("SELECT * FROM p_users WHERE id='{$_SESSION['loginid']}';");
	$userquery->execute();
	$userqueryArray = $userquery->fetchAll();
	$userqueryArray = $userqueryArray[0];
	
	$this->sessionUserInfo = $userqueryArray;

	if(sha1(stripslashes($_POST['p_currentpass'])) == $this->sessionUserInfo['passwordhash']){
		//user entered correct password, ready to check updates

		if(strlen(stripslashes($_POST['p_password1'])) < 5 && $_POST['p_password1'] != null){
			$this->messageAddError('Password must be more than five characters.');
		}elseif (stripslashes($_POST['p_password1']) == stripslashes($_POST['p_password2']) && $_POST['p_password1'] != null) {
			$passwordhash = sha1(stripslashes($_POST['p_password1']));
			$this->messageAddNotification('Updating Password...');
		}else {
			$passwordhash = $this->sessionUserInfo['passwordhash'];
		}
		
		if(trim($_POST['p_username']) != ''){
			$username = stripslashes($_POST['p_username']);
			$this->messageAddNotification('Updating User Name...');
		}else {
		    $username = $this->sessionUserInfo['username'];
		}
		
	}elseif($_POST['p_username'] != null || $_POST['p_password1'] != null || $_POST['p_password2'] != null){
		$this->messageAddError('There was a problem updating your login details.');
		$username = $this->sessionUserInfo['username'];
		$passwordhash = $this->sessionUserInfo['passwordhash'];
	}else{
		$username = $this->sessionUserInfo['username'];
		$passwordhash = $this->sessionUserInfo['passwordhash'];
	}

	if(trim($_POST['p_email']) != null){
		$email = stripslashes($_POST['p_email']);
	}else{
		$email = $this->sessionUserInfo['email'];
	}

	if(trim($_POST['p_name']) != null){
		$name = stripslashes($_POST['p_name']);
	}else{
		$name = $this->sessionUserInfo['name'];
	}

	//now that any potential changes have been saved to the session, update the database
	$name = $this->phylobyteDB->quote($name);
	if($this->phylobyteDB->exec("
			UPDATE p_users SET username='$username', passwordhash='$passwordhash', status='active', email='$email', name=$name
			WHERE id={$_SESSION['loginid']};
			") > 0) $this->messageAddNotification('Your changes have been saved.');

	$userquery = $this->phylobyteDB->prepare("SELECT * FROM p_users WHERE id='{$_SESSION['loginid']}';");
	$userquery->execute();
	$userqueryArray = $userquery->fetchAll();
	$userqueryArray = $userqueryArray[0];
	$this->sessionUserInfo = $userqueryArray;

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

$this->pageArea = '
<script type="text/javascript" src="../plugins/nicEdit.js"></script>

<!--<script type="text/javascript">
bkLib.onDomLoaded(function() {
	new nicEditor({buttonList : [\'bold\',\'italic\',\'underline\',\'ol\',\'ul\'], iconsPath : \'../plugins/nicEditorIcons.gif\'}).panelInstance(\'p_description\');
});
</script>-->

<fieldset>
	<legend>My Acount Details</legend>
<form action="?phylobyte=account" method="POST">

	<label for="p_name">Nick Name</label><input type="text" name="p_name" value="'.$this->sessionUserInfo['name'].'"/><br/>
	<label for="p_email">eMail Address</label><input type="text" name="p_email" value="'.$this->sessionUserInfo['email'].'"/><br/>
	<label for="p_submit">&nbsp;</label><input type="submit" name="p_submit" value="Save Account Details" />
		<div class="ff">&nbsp;</div>
</form>
</fieldset>

<fieldset>
	<legend>Change Login Details</legend>
<form action="?phylobyte=account" method="POST">
	<label for="p_currentpass">Current Password</label><input type="password" name="p_currentpass" value=""/><hr/>
	<label for="p_username">User Name</label><input type="text" name="p_username" value="'.$this->sessionUserInfo['username'].'"/><br/>
	<label for="p_password1">Password</label><input type="password" name="p_password1" value=""/><br/>
	<label for="p_password2">Password (again)</label><input type="password" name="p_password2" value=""/><br/>
	<label for="p_submit">&nbsp;</label><input type="submit" name="p_submit" value="Save Login Details" />
		<div class="ff">&nbsp;</div>
</form>
</fieldset>
';

return false;
?>
 
