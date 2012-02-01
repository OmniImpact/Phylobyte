<?php

//DEFAULTS
//if there is no default for the limit, set it now, otherwise, apply what we're wanting

if(isset($_POST['u_limit'])){
	if(ctype_digit($_POST['u_limit'])){
		$_SESSION['user_list_limit'] = $_POST['u_limit'];
	}else{
		$this->messageAddError('The limit must be a number.');
	}
}elseif($_SESSION['user_list_limit'] == ''){
	$this->messageAddAlert('No limit set; setting displayed user limit to 20. You can change this limit in the form below.');
	$_SESSION['user_list_limit'] = '20';
}

//if there is no default for the user filter, that's OK, but we still need to set it if asked
if(isset($_POST['u_filter'])){
	$_SESSION['user_list_filter'] = stripslashes($_POST['u_filter']);
}

//check that a user name is provided if trying to add a user.
if($_POST['u_submit'] == 'Add User' && trim(stripslashes($_POST['u_name'])) == null ){
	$this->messageAddError('You must provide a user name to add a new user.');
	$_POST['u_submit'] = null;
}

//delete a user if not the last admin
//if there is more than one admin, go ahead and delete the user
//if there is only one admin, pull the user info, if primarygroup is 1, fail
//get the number of members in the group
$adminMembers = $this->phylobyteDB->prepare("SELECT COUNT(*) FROM p_users WHERE primarygroup=1;");
$adminMembers->execute();
$adminMembers = $adminMembers->fetch();
$adminMembers = $adminMembers[0];

if($_POST['u_submit'] == 'Delete User' && $adminMembers > 1){
	if($this->phylobyteDB->exec("DELETE FROM p_users WHERE id={$_POST['u_uid']}")){
		$this->messageAddNotification('Successfully deleted user from database.');
	}
}elseif($_POST['u_submit'] == 'Delete User'){
//we need to check that this isn't the last admin!
	$userInfo = $this->phylobyteDB->prepare("SELECT COUNT(*) FROM p_users WHERE primarygroup=1 AND id<>'{$_POST['u_uid']}' AND status='active';");
	$userInfo->execute();
	$userInfo = $userInfo->fetch();
	$userInfo = $userInfo[0];
	if($userInfo < 1){
		$this->messageAddError('You can not delete the last member of the "<em>admin</em>" group.');
	}else{
		if($this->phylobyteDB->exec("DELETE FROM p_users WHERE id={$_POST['u_uid']}")){
			$this->messageAddNotification('Successfully deleted user from database.');
		}
	}
}

//check if user exists

$newUserName = $this->phylobyteDB->quote(stripslashes($_POST['u_name']));
$userExists = $this->phylobyteDB->prepare("SELECT COUNT(*) FROM p_users WHERE username=$newUserName");
$userExists->execute();
$userExists = $userExists->fetch();
$userExists = $userExists[0];

if($userExists && $_POST['u_submit'] != 'Save User Details'){
	$this->messageAddError('That user name is already in use.');
	$_POST['u_name'] = $_POST['u_initialusername'];
}



//do the rest of the checks for a new user. if it is all good, add the user, issue a success message, and clear 'Add User'
if($_POST['u_submit'] == 'Create User Account' &&
	( ( (strlen(stripslashes($_POST['u_pass1'])) >= 5 && $_POST['u_pass1'] == $_POST['u_pass2']) || $_POST['u_autopass'] == true ) ||
	$_POST['u_status'] == 'auto' && ($_POST['u_autopass'] == true || $_POST['u_pass1'] == $_POST['u_pass2']) ) &&
	trim(stripslashes($_POST['u_name'])) != null && !$userExists){
	//we are ready to add a new user!
	$this->messageAddAlert('Preparing to add new user to database.');

	$usernameAvailable = true;

	if($usernameAvailable == true){
		
	//because of the checks, we can assume everything is ready to go, but we need to figure a few things out
	//if autopass is set, we need to generate a password
	
	if($_POST['u_autopass'] == true){
		$newPassword = genRandomString('aaabcdeeefgghhiiijkllmnnooopqrrssttuuuvwxyyz', 6).genRandomString('0123456789', 3);
		$autogen = true;
	}else{
		$newPassword = trim(stripslashes($_POST['u_pass1']));
		$autogen = false;
	}
	$newPasswordSHA = sha1($newPassword);

	$newUName = strtolower(stripslashes($_POST['u_name']));
	$newFName = stripslashes($_POST['u_fname']);
	$newLName = stripslashes($_POST['u_lname']);
	$neweMail = stripslashes($_POST['u_email']);
	$newPersonalPhone = stripslashes($_POST['u_personalp']);
	$newPublicPhone = stripslashes($_POST['u_publicp']);

	if($_POST['u_status'] == 'auto'){
		if($_POST['u_autopass'] == true){
			$newAccountStatus = 'active';
		}elseif($_POST['u_pass1'] != null){
			$newAccountStatus = 'active';
		}else{
			$newAccountStatus = 'reserved';
		}
	}else{
		$newAccountStatus = stripslashes($_POST['u_status']);
	}
	$newAccountStatus; //write some logic so that if auto then if no password, make "reserved"
	$newPrimaryGroup = $_POST['u_primarygroup'];

	$db_username = $this->phylobyteDB->quote($newUName);
	$db_status = $this->phylobyteDB->quote($newAccountStatus);
	$db_primarygroup = $this->phylobyteDB->quote($newPrimaryGroup);
	$db_passwordhash = $this->phylobyteDB->quote($newPasswordSHA);
	$db_email = $this->phylobyteDB->quote($neweMail);
	$db_fname = $this->phylobyteDB->quote($newFName);
	$db_lname = $this->phylobyteDB->quote($newLName);
	$db_personalphone = $this->phylobyteDB->quote($newPersonalPhone);
	$db_publicphone = $this->phylobyteDB->quote($newPublicPhone);
	if($autogen == true){
		$db_description = $this->phylobyteDB->quote('Welcome, new user. Your temporary password is "'.$newPassword.'".');
		$this->messageAddAlert('A new user with temporary password of '.$newPassword.' has been created.');
	}else{
		$db_description = $this->phylobyteDB->quote('New User');
	}
	
	if(
	$this->phylobyteDB->exec("INSERT INTO p_users
	(username, status, primarygroup, passwordhash, email, fname, lname, personalphone, publicphone, description)
	VALUES
	($db_username, $db_status, $db_primarygroup, $db_passwordhash, $db_email, $db_fname, $db_lname, $db_personalphone, $db_publicphone, $db_description);") && !$userExists){
		$this->messageAddNotification('Successfully added user to database.');
	}
		$_POST['u_submit'] == null;
	}
	
	
	}elseif($_POST['u_submit'] == 'Create User Account'){
	//user is trying to create an account, but they did something wrong. time to tell them what, put them back to the form

	if(trim(stripslashes($_POST['u_name'])) == null){
		$this->messageAddError('You must specify a user name.');
		$_POST['u_name'] = $_POST['u_initialusername'];
	}

	if($_POST['u_autopass'] != true && $_POST['u_status'] == 'auto' && $_POST['u_pass1'] != $_POST['u_pass2']){
		$this->messageAddError('Password is blank or passwords do not match.');
	}elseif($_POST['u_autopass'] != true){
		$this->messageAddError('The password and status combination you attemted is not valid.');
	}

	if($_POST['u_pass1'] != null && strlen($_POST['u_pass1']) < 5){
		$this->messageAddError('Password must be more than five characters.');
	}

	$_POST['u_submit'] = 'Add User';
}

//if you're saving, do the checks and so on, but send them back to the edit page.
if($_POST['u_submit'] == 'Save User Details'){

	//we're ready to figure out what is different and save it.
	$u_uid = $this->phylobyteDB->quote(stripslashes($_POST['u_uid']));
	$thisUser = $this->phylobyteDB->prepare("SELECT * FROM p_users WHERE id=$u_uid");
	$thisUser->execute();
	$thisUser = $thisUser->fetch();
	
	if(trim(stripslashes($_POST['u_name'])) == null){
		$this->messageAddAlert('The user name can not be blank. Resetting to initial user name.');
	}elseif(trim(stripslashes($_POST['u_name'])) != null && !$userExists){
		$this->messageAddNotification('Updating User Name...');
		$thisUser['username'] = trim(stripslashes($_POST['u_name']));
	}
	
	if(trim(stripslashes($_POST['u_fname'])) != $thisUser['fname']){
		$this->messageAddNotification('Updating first name...');
		$thisUser['fname'] = trim(stripslashes($_POST['u_fname']));
	}
	if(trim(stripslashes($_POST['u_lname'])) != $thisUser['lname']){
		$this->messageAddNotification('Updating last name...');
		$thisUser['lname'] = trim(stripslashes($_POST['u_lname']));
	}
	if(trim(stripslashes($_POST['u_email'])) != $thisUser['email']){
		$this->messageAddNotification('Updating eMail...');
		$thisUser['email'] = trim(stripslashes($_POST['u_email']));
	}
	if(trim(stripslashes($_POST['u_personalp'])) != $thisUser['personalphone']){
		$this->messageAddNotification('Updating personal phone number...');
		$thisUser['personalphone'] = trim(stripslashes($_POST['u_personalp']));
	}
	if(trim(stripslashes($_POST['u_publicp'])) != $thisUser['publicphone']){
		$this->messageAddNotification('Updating public phone number...');
		$thisUser['publicphone'] = trim(stripslashes($_POST['u_publicp']));
	}
	if(trim(stripslashes($_POST['u_description'])) != $thisUser['description']){
		$this->messageAddNotification('Updating description...');
		$thisUser['description'] = trim(stripslashes($_POST['u_description']));
	}
	
	$checkLast = $this->phylobyteDB->prepare("SELECT COUNT(*) FROM p_users WHERE primarygroup=1 AND id<>'{$thisUser['id']}' AND status='active';");
	$checkLast->execute();
	$checkLast = $checkLast->fetch();
	$checkLast = $checkLast[0];
	
	if(trim(stripslashes($_POST['u_status'])) != $thisUser['status']){
		if($checkLast < 1){
			$this->messageAddError('You must have at least one admin.');
		}else{
			//we need to see if the password is blank
			if($thisUser['passwordhash'] != null &&
			($_POST['u_changepass'] && ($_POST['u_autopass'] ||
			($_POST['u_pass1'] == $_POST['u_pass2'] &&
			strlen(trim(stripslashes($_POST['u_pass1']))) >= 5 ) ) )
			)
			$this->messageAddNotification('Updating status...');
			$thisUser['status'] = trim(stripslashes($_POST['u_status']));
		}
		if($thisUser['status'] == 'reserved'){
			$this->messageAddAlert('As long as the status is "Reserved" no password changes will be processed. To change the password, you must set the status to something other than "Reserved".');
		}
	}
	
	if(trim(stripslashes($_POST['u_primarygroup'])) != $thisUser['primarygroup']){
		
		if($checkLast < 1){
			$this->messageAddError('You must have at least one admin.');
		}else{
			$this->messageAddNotification('Updating primary group membership...');
			$thisUser['primarygroup'] = trim(stripslashes($_POST['u_primarygroup']));
		}
		
	}
	
	if($thisUser['status'] != 'reserved' && $_POST['u_changepass']){
		//handle the password as requested
		
		$newPasswordSHA = $thisUser['passwordhash'];
		
		if($_POST['u_autopass'] == true){
			$newPassword = genRandomString('aaabcdeeefgghhiiijkllmnnooopqrrssttuuuvwxyyz', 6).genRandomString('0123456789', 3);
			$newPasswordSHA = sha1($newPassword);
			$autogen = true;
		}else{
			if(strlen(trim(stripslashes($_POST['u_pass1']))) >= 5){
				if($_POST['u_pass1'] == $_POST['u_pass2']){
					$newPassword = trim(stripslashes($_POST['u_pass1']));
					$newPasswordSHA = sha1($newPassword);
					$autogen = false;
				}else{
					$this->messageAddError('Passwords do not match');
				}
			}else{
				$this->messageAddError('Password must be more than five characters.');
			}
		}
		
		if($autogen == true){
			$thisUser['description'].= ' The latest auto-generated password is "'.$newPassword.'".';
			$this->messageAddAlert('Auto-generated a temporary password of "'.$newPassword.'".');
		}
		
		if($newPasswordSHA == $thisUser['passwordhash']){
			$this->messageAddAlert('The password is unchanged.');
		}else{
			$this->messageAddNotification('Updating password...');
		}
	}
	
	//prepare user info for write
	$db_uid = $this->phylobyteDB->quote($u_uid);
	$db_username = $this->phylobyteDB->quote($thisUser['username']);
	$db_fname = $this->phylobyteDB->quote($thisUser['fname']);
	$db_lname = $this->phylobyteDB->quote($thisUser['lname']);
	$db_email = $this->phylobyteDB->quote($thisUser['email']);
	$db_personalphone = $this->phylobyteDB->quote($thisUser['personalphone']);
	$db_publicphone = $this->phylobyteDB->quote($thisUser['publicphone']);
	$db_description = $this->phylobyteDB->quote($thisUser['description']);
	$db_status = $this->phylobyteDB->quote($thisUser['status']);
	$db_primarygroup = $this->phylobyteDB->quote($thisUser['primarygroup']);
	$db_passwordhash = $this->phylobyteDB->quote($newPasswordSHA);
	
	//write changes to database
	$this->phylobyteDB->exec(
	"UPDATE p_users SET
	username=$db_username, fname=$db_fname, lname=$db_lname, email=$db_email, status=$db_status,
	personalphone=$db_personalphone, publicphone=$db_publicphone, description=$db_description,
	primarygroup=$db_primarygroup, passwordhash=$db_passwordhash
	WHERE id=$u_uid;"
	);
	
	
	//send them back to the editing page.
	$_POST['u_submit'] = 'Edit User';
}

//a little javascript
$this->pageArea.= '
<script type="text/javascript">
	var lastChangedName = null;

	function changeClass(targetID, classA, classB){
		var node = document.getElementById(targetID);
		lastChangedName = targetID;
		var currentClasses = node.className.split(\' \');
		var currentFirstClass = currentClasses[0];
		if(currentFirstClass == classA){
			currentClasses[0] = classB;
		}else if(currentFirstClass == classB){
			currentClasses[0] = classA;
		}
		node.className = currentClasses.join(\' \');
	}

	function changeLast(classA, classB){
		if(lastChangedName != null){
			changeClass(lastChangedName, classA, classB);
		}
	}
</script>
';

//if new user, edit user, or viewing
if($_POST['u_submit'] == 'Add User' && trim(stripslashes($_POST['u_name'])) != null){
	$this->breadcrumbs.=' &raquo; Add New User';

	//help
	$this->docArea='
	<h3>Passwords</h3>
	<p>Phylobyte requires that the password be at least four characters. If you do not enter a password, a <strong>Reserved</strong> account will be created.</p>

	<h3>Account Status</h3>
	<p>The account status can have a special meaning under some circumstances. An <em>Active</em> account status is required to log in to the administrative area. If you choose "Auto/Active" as you account status, the account will become <em>Active</em> if a password is set by hand or automatically, otherwise, it will become <em>Reserved</em>. If you choose a different status, it will generate an error if the password is left blank.</p>

	<h3>Groups</h3>
	<p>This sets the primary group for a user. To make then an administrator, assign them to the <em>Admin</em> group.</p>
	';

	//list of statuses
	$statuses = '
	<option value="auto">Auto/Active</option>
	<option value="disabled">Disabled</option>
	<option value="suspended">Suspended</option>
	<option value="flagged">Flagged</option>
	';
	
	//list of existing groups
	$groupList = $this->phylobyteDB->prepare("SELECT * FROM p_groups ORDER BY name;");
	$groupList->execute();
	$groupList = $groupList->fetchAll();
	
	$groupListSelect = null;
	foreach($groupList as $groupArray){
		$groupListSelect.='
		<option value="'.$groupArray['id'].'">'.$groupArray['name'].'</option>
		';
	}

$this->pageArea.= '

<fieldset>
	<legend>Add New User</legend>
<form action="?'.$_SERVER['QUERY_STRING'].'" method="POST">
	<input type="hidden" name="u_initialusername" value="'.$_POST['u_name'].'" />
	<label for="u_name">User Name</label><input type="text" name="u_name" value="'.$_POST['u_name'].'"/><br/>
	<label for="u_fname">First Name</label><input type="text" name="u_fname" value="'.$_POST['u_fname'].'"/><br/>
	<label for="u_lname">Last Name</label><input type="text" name="u_lname" value="'.$_POST['u_lname'].'"/><br/>
	<label for="u_email">e-Mail Address</label><input type="text" name="u_email" value="'.$_POST['u_email'].'"/><br/>
	<label for="u_personalp">Personal Phone</label><input type="text" name="u_personalp" value="'.$_POST['u_personalp'].'"/><br/>
	<label for="u_publicp">Public Phone</label><input type="text" name="u_publicp" value="'.$_POST['u_publicp'].'"/>
	<hr/>
	<label for="u_status">Account Status</label>
	<select name="u_status">
		'.$statuses.'
	</select><br/>
	<label for="u_primarygroup">Primary Group</label>
	<select name="u_primarygroup">
		'.$groupListSelect.'
	</select><br/>
	<hr/>
	<label for="u_autopass">Auto-Generate a Password?</label><input type="checkbox" name="u_autopass" style="width: 2em;"
	onclick="changeClass(\'passwordinput\',\'itemhide\',\'itemshowblock\');"/><br/>
	<span class="itemshowblock" id="passwordinput">
	<label for="u_pass1">Password</label><input type="password" name="u_pass1" value="'.$_POST['u_pass1'].'"/><br/>
	<label for="u_pass2">Password (Again)</label><input type="password" name="u_pass2" value="'.$_POST['u_pass2'].'"/>
	</span>
	<label for="u_submit">&nbsp;</label><input type="submit" name="u_submit" value="Create User Account" />
	
</form>
</fieldset>

';
	
}elseif($_POST['u_submit'] == 'Edit User'){

$this->breadcrumbs.=' &raquo; Edit User';

	//get user information
	$u_uid = $this->phylobyteDB->quote(stripslashes($_POST['u_uid']));
	$userExists = $this->phylobyteDB->prepare("SELECT * FROM p_users WHERE id=$u_uid");
	$userExists->execute();
	$userExists = $userExists->fetch();
	$_POST['u_name'] = $userExists['username'];
	$_POST['u_fname'] = $userExists['fname'];
	$_POST['u_lname'] = $userExists['lname'];
	$_POST['u_email'] = $userExists['email'];
	$_POST['u_status'] = $userExists['status'];
	$_POST['u_personalp'] = $userExists['personalphone'];
	$_POST['u_publicp'] = $userExists['publicphone'];
	$_POST['u_description'] = $userExists['description'];



//list of statuses
switch($userExists['status']){

	case 'active':
		$status = 'Active';
	break;

	case 'disabled':
		$status = 'Disabled';
	break;

	case 'suspended':
		$status = 'Suspended';
	break;

	case 'flagged':
		$status = 'Flagged';
	break;

	default:
		if(ctype_digit($userExists['status']) || $userExists['status'] == 'reserved'){
			$status = 'Reserved (No password changes allowed)';
		}else{
			$status = ucfirst($userExists['status']);
		}
}

$statuses = '
<option value="'.$userExists['status'].'">Preserve status: '.$status.'</option>
<option value="active">Active</option>
<option value="disabled">Disabled</option>
<option value="suspended">Suspended</option>
<option value="flagged">Flagged</option>
<option value="reserved">Reserved (No password changes allowed)</option>
';

//list of existing groups
$groupList = $this->phylobyteDB->prepare("SELECT * FROM p_groups ORDER BY name;");
$groupList->execute();
$groupList = $groupList->fetchAll();

$groupListSelect = null;
foreach($groupList as $groupArray){
	if($userExists['primarygroup'] == $groupArray['id']){
		$selector = ' selected="selected"';
	}else{
		$selector = '';
	}
	$groupListSelect.='
	<option value="'.$groupArray['id'].'"'.$selector.'>'.$groupArray['name'].'</option>
	';
}
	
$this->pageArea.= '

<fieldset>
	<legend>Edit User</legend>
<form action="?'.$_SERVER['QUERY_STRING'].'" method="POST">
	<input type="hidden" name="u_initialusername" value="'.$_POST['u_name'].'" />
	<input type="hidden" name="u_uid" value="'.$_POST['u_uid'].'" />
	<label for="u_name">User Name</label><input type="text" name="u_name" value="'.$_POST['u_name'].'"/><br/>
	<label for="u_fname">First Name</label><input type="text" name="u_fname" value="'.$_POST['u_fname'].'"/><br/>
	<label for="u_lname">Last Name</label><input type="text" name="u_lname" value="'.$_POST['u_lname'].'"/><br/>
	<label for="u_email">e-Mail Address</label><input type="text" name="u_email" value="'.$_POST['u_email'].'"/><br/>
	<label for="u_personalp">Personal Phone</label><input type="text" name="u_personalp" value="'.$_POST['u_personalp'].'"/><br/>
	<label for="u_publicp">Public Phone</label><input type="text" name="u_publicp" value="'.$_POST['u_publicp'].'"/><br/>
	<label for="u_description">Personal Description</label>
	<textarea rows="6" name="u_description" id="p_description">'.$_POST['u_description'].'</textarea>
	<hr/>
	<label for="u_status">Account Status</label>
	<select name="u_status">
		'.$statuses.'
	</select><br/>
	<label for="u_primarygroup">Primary Group</label>
	<select name="u_primarygroup">
		'.$groupListSelect.'
	</select><br/>
	<hr/>
	<label for="u_changepass">Change or Update Password?</label><input type="checkbox" name="u_changepass" style="width: 2em;" onclick="changeClass(\'changepassword\',\'itemhide\',\'itemshowblock\');"/><br/>
	<span id="changepassword" class="itemhide">
	<label for="u_autopass">Auto-Generate a New Password?</label><input type="checkbox" name="u_autopass" style="width: 2em;" 
	onclick="changeClass(\'passwordinput\',\'itemhide\',\'itemshowblock\');"/><br/>
	<span class="itemshowblock" id="passwordinput">
	<label for="u_pass1">Set New Password</label><input type="password" name="u_pass1" value=""/><br/>
	<label for="u_pass2">New Password (Again)</label><input type="password" name="u_pass2" value=""/>
	</span>
	</span>
	<label for="u_submit">&nbsp;</label><input type="submit" name="u_submit" value="Save User Details" />
	
</form>
</fieldset>

';

}else{

//query the users to populate the table
$userList = $this->phylobyteDB->prepare("SELECT * FROM p_users WHERE username LIKE '%{$_SESSION['user_list_filter']}%' ORDER BY username LIMIT {$_SESSION['user_list_limit']};");
$userList->execute();
$userList = $userList->fetchAll();

$userListTable = null;
foreach($userList as $userArray){
	switch($userArray['status']){

		case 'active':
			$status = '<span style="color: #080;">Active</span>';
		break;

		case 'disabled':
			$status = '<span style="color: #008;">Disabled</span>';
		break;

		case 'suspended':
			$status = '<span style="color: #800;">Suspended</span>';
		break;

		case 'flagged':
			$status = '<span style="color: #540;">Flagged</span>';
		break;

		default:
			if(ctype_digit($userArray['status'])){
				$status = '<span style="color: #080;">Reserved</span>';
			}else{
				$status = ucfirst($userArray['status']);
			}
	}
	
	$groupQuery = $this->phylobyteDB->prepare("SELECT name FROM p_groups WHERE id={$userArray['primarygroup']}");
	$groupQuery->execute();
	$userGroup = $groupQuery->fetchAll();
	$userGroup = $userGroup[0][0];
	
	$userListTable.='
	<tr id="u_table_row_'.$userArray['id'].'" class="table_row_normal">
		<td style="text-align: center;">
		<input type="radio" name="u_uid" value="'.$userArray['id'].'"
		onchange="changeLast(\'table_row_normal\', \'table_row_highlight\');changeClass(\'u_table_row_'.$userArray['id'].'\', \'table_row_normal\', \'table_row_highlight\');"
		style="cursor: pointer;"/>
		</td>
		<td>'.$userArray['username'].'</td><td>'.$userGroup.'</td><td>'.$userArray['fname'].' '.$userArray['lname'].'</td><td>'.$userArray['email'].'</td>
		<td style="text-align: center; font-weight: bold;">'.$status.'</td>
	</tr>
	';
}



$this->pageArea.= '

<fieldset>
	<legend>Add New User</legend>
<form action="?'.$_SERVER['QUERY_STRING'].'" method="POST">
	<label for="u_name">User Name</label><input type="text" name="u_name" value=""/><br/>
	<label for="u_submit">&nbsp;</label><input type="submit" name="u_submit" value="Add User" />
</form>
</fieldset>

<fieldset>
	<legend>Existing Users</legend>
<form action="?'.$_SERVER['QUERY_STRING'].'" method="POST">

	<label for="u_filter">Filter Users</label><input type="text" name="u_filter" value="'.$_SESSION['user_list_filter'].'"/><br/>
	<label for="u_limit">Limit of Users Shown</label><input type="text" name="u_limit" value="'.$_SESSION['user_list_limit'].'"/><br/>
	<label for="u_submit">&nbsp;</label><input type="submit" name="u_submit" value="Apply" />

	<table class="selTable">
		<tr>
			<th style="width: 6em;">Select</th><th>User Name</th><th>Primary Group</th><th>Given Name</th><th>e-Mail</th><th style="width: 6em;">Status</th>
		</tr>
		'.$userListTable.'
	</table>

	<div style="display: block; text-align: right;">
		<input type="submit" name="u_submit" value="Edit User" style="width: 14em;" />
		<input type="submit" name="u_submit" value="Delete User"  style="width: 14em;" />
	</div>

</form>
</fieldset>

';

}




?>
