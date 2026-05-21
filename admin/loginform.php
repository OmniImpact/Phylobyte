<?php
// Check database connection status
$db_connected = (phylobyte::$phylobyteDB !== null);

//process
if($db_connected && (isset($_POST['p_submit']) || (isset(phylobyte::$sessionUserInfo['status']) && phylobyte::$sessionUserInfo['status'] == 'override'))){
	
	if(isset($_POST['p_submit'])){
		$currentUserName = trim(stripslashes($_POST['p_username']));
		$currentPassword = isset($_POST['p_password']) ? trim(stripslashes($_POST['p_password'])) : '';
		if(strlen($currentUserName) == 0){
			phylobyte::messageAddError('User Name can not be blank.');
		}
		if(strlen($currentPassword) == 0 && (!isset($_POST['p_password1']) || strlen($_POST['p_password1']) <= 1)){
			phylobyte::messageAddError('Password can not be blank.');
		}
		$userquery = phylobyte::$phylobyteDB->prepare("SELECT * FROM p_users WHERE username='$currentUserName';");
	}else{
		$userquery = phylobyte::$phylobyteDB->prepare("SELECT * FROM p_users WHERE id='{$_SESSION['loginid']}';");
	}

	$userquery->execute();
	$userqueryResult = $userquery->fetchAll();
	
	if (count($userqueryResult) > 0) {
		$userqueryArray = $userqueryResult[0];
		phylobyte::$sessionUserInfo = $userqueryArray;
	} else {
		$userqueryArray = []; // Initialize as empty array to prevent further errors
		phylobyte::messageAddError('The user name and password do not match.');
	}
	
	if(isset($userqueryArray['id']) && $userqueryArray['id'] != null){
		//we have a user! we now check if override is set
		if($userqueryArray['status'] == 'override'){
			$_SESSION['loginid'] = $userqueryArray['id'];
			//log the user in anyway, and return true
			$userquery = phylobyte::$phylobyteDB->prepare("SELECT * FROM p_users WHERE id='{$_SESSION['loginid']}';");
			$userquery->execute();
			$userqueryResult = $userquery->fetchAll();
			if (count($userqueryResult) > 0) {
				$userqueryArray = $userqueryResult[0];
				phylobyte::$sessionUserInfo = $userqueryArray;
			} else {
				$userqueryArray = []; // Should not happen if $_SESSION['loginid'] is valid
			}
			phylobyte::messageAddAlert('You are now logged in with override.');
			include('accountSetup.php');
			return false;
		}else{
			//now check credentials and account status
			$passwordhash = sha1($currentPassword);
			if($userqueryArray['passwordhash'] == $passwordhash && $userqueryArray['status'] == 'active'){
			//good so far, check to see if in group "admin"
			$adminquery = phylobyte::$phylobyteDB->prepare("SELECT * FROM p_memberships WHERE userid='{$userqueryArray['id']}' AND groupid='1';");
			$adminquery->execute();
			$adminqueryArray = $adminquery->fetchAll();
				if(count($adminqueryArray) > 0){
					phylobyte::messageAddNotification('Welcome, '.$userqueryArray['name'].'. Thank you for logging in.');
					$_SESSION['loginid'] = $userqueryArray['id'];
					phylobyte::$sessionUserInfo = $userqueryArray;
					$accountverify = 'success';
					return true;
				}else{
					phylobyte::messageAddError('Your account does not have administrative privilages.');
				}
			}else{
				phylobyte::messageAddError('The user name and password do not match.');
			}
		}
	}else{
		phylobyte::messageAddError('The user name and password do not match.');
	}
} elseif (!$db_connected) {
    phylobyte::messageAddError('Database is not connected. Login functionality is unavailable.');
}

//build
$this->pageTitle.=' | Log In';

if ($db_connected) {
    phylobyte::messageAddAlert('To use Phylobyte, you must log in.');
} else {
    phylobyte::messageAddError('Cannot connect to the database. Please check your database configuration.');
}


$this->docArea = '
<h3>Welcome to Phylobyte</h3>
<p>
Log in to Phylobyte using the form to the left. If you do not know your log in information, please contact your website administrator.
</p>
<h3>The Message Pile</h3>
<p>
The "Messages" slider, called the "Message Pile" contains often useful feedback based on your current actions. The drawer will show whenever there are messages, and will automatically roll up after three seconds. To open and close the drawer to either read messages or make extra space to work, click the tab.
</p>';

if(isset($_GET['phylobyte']) && $_GET['phylobyte'] == 'logout'){
	$queryString = '?';
}else{
	$queryString = '?'.$_SERVER['QUERY_STRING'];
}

$loginFormContent = '';
if ($db_connected) {
    $loginFormContent = '
    <form action="'.$queryString.'" method="POST" style="border-bottom: none; padding-bottom: 0;">
        <label for="p_username">User Name</label><input type="text" name="p_username" value="'.(isset($currentUserName) ? $currentUserName : '').'" id="defaultInput"/><br/>
        <label for="p_password">Password</label><input type="password" name="p_password" value="'.(isset($currentPassword) ? $currentPassword : '').'"/><br/>
        <label for="p_submit">&nbsp;</label><input type="submit" name="p_submit" value="Log In" style="margin-left: 5%; margin-right: 12%; width: 40%;" />
        <script type="text/javascript">
         document.getElementById (\'defaultInput\').focus();
        </script>
    </form>';
} else {
    $loginFormContent = '<p>Login is currently unavailable due to a database connection issue.</p>';
}


$this->pageArea = '
<img src="gfx/logo_color_md.png" style="max-width: 100%;"/>
<div style="float: right; width: 80%;">
	<h2>Love your website. Make it grow.</h2>
</div>

<div class="floatfix">&nbsp;</div>

<fieldset style="float: right; '.$GLOBALS['MS']->mobileReturn('width: 90%; margin-right: 5%;', 'width: 40%; margin-right: 15%;').'">
	<legend>Log In</legend>
    '.$loginFormContent.'
<form action="../" method="POST" style="border-top: none; padding-top: 0;">
	<label for="p_submit">&nbsp;</label><input type="submit" name="p_submit" value="Return to Website" style="margin-left: 5%; margin-right: 12%; width: 40%;" />
	<div class="ff">&nbsp;</div>
</form>
</fieldset>
';
?>