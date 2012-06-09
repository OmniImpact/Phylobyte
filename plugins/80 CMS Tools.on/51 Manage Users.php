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
	$this->messageAddAlert('You must provide a user name to add a new user.');
	$_POST['u_submit'] = null;
}

if($_POST['u_submit'] == 'Delete User'){
		$this->messageAddAlert('Try To delete user...');
}

//do the rest of the checks for a new user. if it is all good, add the user, issue a success message, and clear 'Add User'
if($_POST['u_submit'] == 'Save User Account'){
	$this->messageAddAlert('Create/Update User Account');
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
$userList = $GLOBALS['UGP']->user_get(null, $_SESSION['user_list_filter'], $_SESSION['user_list_limit']);

$userListFormat = '
	<tr id="u_table_row_%i%" class="table_row_normal">
		<td style="text-align: center;">
		<input type="radio" name="u_uid" value="%i%"
		onchange="changeLast(\'table_row_normal\', \'table_row_highlight\');changeClass(\'u_table_row_%i%\', \'table_row_normal\', \'table_row_highlight\');"
		style="cursor: pointer;"/>
		</td>
		<td>%u%</td>
		<td>%G%</td>
		<td>%fn% %ln%</td>
		<td>%e%</td>
		<td style="text-align: center; font-weight: bold;"><span style="color: %sC%;">%s%</span></td>
	</tr>
';

$userListTable = $GLOBALS['UGP']->user_format($userList, $userListFormat);

/* $userListTable = null;
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
*/


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
