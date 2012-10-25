<?php

//process
if(isset($_POST['p_submit']) || $this->sessionUserInfo['status'] == 'override'){
	
	if(isset($_POST['p_submit'])){
		$currentUserName = trim(stripslashes($_POST['p_username']));
		$currentPassword = trim(stripslashes($_POST['p_password']));
		if(strlen($currentUserName) == 0){
			$this->messageAddError('User Name can not be blank.');
		}
		if(strlen($currentPassword) == 0 && strlen($_POST['p_password1']) <= 1){
			$this->messageAddError('Password can not be blank.');
		}
		$userquery = $this->phylobyteDB->prepare("SELECT * FROM p_users WHERE username='$currentUserName';");
	}else{
		$userquery = $this->phylobyteDB->prepare("SELECT * FROM p_users WHERE id='{$_SESSION['loginid']}';");
	}

	$userquery->execute();
	$userqueryArray = $userquery->fetchAll();
	$userqueryArray = $userqueryArray[0];
	$this->sessionUserInfo = $userqueryArray;
	
	if($userqueryArray['id'] != null){
		//we have a user! we now check if override is set
		if($userqueryArray['status'] == 'override'){
			$_SESSION['loginid'] = $userqueryArray['id'];
			//log the user in anyway, and return true
			$userquery = $this->phylobyteDB->prepare("SELECT * FROM p_users WHERE id='{$_SESSION['loginid']}';");
			$userquery->execute();
			$userqueryArray = $userquery->fetchAll();
			$userqueryArray = $userqueryArray[0];
			$this->sessionUserInfo = $userqueryArray;
			$this->messageAddAlert('You are now logged in with override.');
			include('accountSetup.php');
			return false;
		}else{
			//now check credentials and account status
			$passwordhash = sha1($currentPassword);
			if($userqueryArray['passwordhash'] == $passwordhash && $userqueryArray['status'] == 'active'){
			//good so far, check to see if in group "admin"
			$adminquery = $this->phylobyteDB->prepare("SELECT * FROM p_memberships WHERE userid='{$userqueryArray['id']}' AND groupid='1';");
			$adminquery->execute();
			$adminqueryArray = $adminquery->fetchAll();
				if(count($adminqueryArray) > 0){
					$this->messageAddNotification('Welcome, '.$userqueryArray['name'].'. Thank you for logging in.');
					$_SESSION['loginid'] = $userqueryArray['id'];
					$this->sessionUserInfo = $userqueryArray;
					$accountverify = 'success';
					return true;
				}else{
					$this->messageAddError('Your account does not have administrative privilages.');
				}
			}else{
				$this->messageAddError('The user name and password do not match.');
			}
		}
	}else{
		$this->messageAddError('The user name and password do not match.');
	}
}
//build
$this->pageTitle.=' | Log In';

$this->messageAddAlert('To use Phylobyte, you must log in.');

$this->docArea = '
<h3>Welcome to Phylobyte</h3>
<p>
Log in to Phylobyte using the form to the left. If you do not know your log in information, please contact your website administrator.
</p>
<h3>The Message Pile</h3>
<p>
The "Messages" slider, called the "Message Pile" contains often useful feedback based on your current actions. The drawer will show whenever there are messages, and will automatically roll up after three seconds. To open and close the drawer to either read messages or make extra space to work, click the tab.
</p>';

if($_GET['phylobyte'] == 'logout'){
	$queryString = '?';
}else{
	$queryString = '?'.$_SERVER['QUERY_STRING'];
}

$this->pageArea = '
<img src="gfx/logo_color_md.png" style="max-width: 100%;"/>
<div style="float: right; width: 80%;">
	<h2>Love your website. Make it grow.</h2>
</div>

<div class="floatfix">&nbsp;</div>

<fieldset style="float: right; '.$GLOBALS['MS']->mobileReturn('width: 90%; margin-right: 5%;', 'width: 40%; margin-right: 15%;').'">
	<legend>Log In</legend>
<form action="'.$queryString.'" method="POST" style="border-bottom: none; padding-bottom: 0;">
	<label for="p_username">User Name</label><input type="text" name="p_username" value="'.$currentUserName.'" id="defaultInput"/><br/>
	<label for="p_password">Password</label><input type="password" name="p_password" value="'.$currentPassword.'"/><br/>
	<label for="p_submit">&nbsp;</label><input type="submit" name="p_submit" value="Log In" />
	<script type="text/javascript">
	 document.getElementById (\'defaultInput\').focus();
	</script>
</form>
<form action="../" method="POST" style="border-top: none; padding-top: 0;">
	<label for="p_submit">&nbsp;</label><input type="submit" name="p_submit" value="Return to Website" />
	<div class="ff">&nbsp;</div>
</form>
</fieldset>
';
?>
