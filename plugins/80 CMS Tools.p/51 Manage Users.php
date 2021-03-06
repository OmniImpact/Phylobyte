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


//check that a user name is provided if trying to edit a user.
if($_POST['u_submit'] == 'Edit User' && trim(stripslashes($_POST['u_uid'])) == null ){
	$this->messageAddAlert('You must select a user to edit.');
	$_POST['u_submit'] = null;
}

if($_POST['u_submit'] == 'Delete User'){
	$GLOBALS['UGP']->user_delete($_POST['u_uid']);
}

//do the rest of the checks for a new user. if it is all good, add the user, issue a success message, and clear 'Add User'
if($_POST['u_submit'] == 'Save User Details' || $_POST['u_submit'] == 'Create User Account'){
	//i do need to preprocess the password
	//null is no change

	if($_POST['u_autopass'] == 'on'){
		$autopass = true;
		$password = null;
	}elseif($_POST['u_pass1'] == $_POST['u_pass2'] && strlen($_POST['u_pass1']) >= 4){
		$password = stripslashes($_POST['u_pass1']);
	}else{
		$password = null;
	}

	if($GLOBALS['UGP']->user_put(Array(
		'id' => stripslashes($_POST['u_uid']),
		'username' => stripslashes($_POST['u_username']),
		'name' => stripslashes($_POST['u_name']),
		'email' => stripslashes($_POST['u_email']),
		'status' => stripslashes($_POST['u_status']),
		'autopass' => $autopass,
		'password' => $password
	))){
	};

	if($_POST['u_submit'] == 'Create User Account'){
		$_POST['u_submit'] = null;
	}
}

if($_POST['u_submit'] == 'Add Membership'){
	$GLOBALS['UGP']->membership_add($_POST['u_uid'], $_POST['u_group']);
}

if($_POST['u_submit'] == 'Remove Membership'){
	$GLOBALS['UGP']->membership_remove($_POST['g_mid']);
}

if($_POST['u_submit'] == 'Set User Value' && $_POST['a_id'] != null){
	$GLOBALS['UGP']->user_putattr($_POST['u_uid'], $_POST['a_id'], stripslashes($_POST['a_newdefault']) );
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
if($_POST['u_submit'] == 'Add User' && trim(stripslashes($_POST['u_username'])) != null || $_POST['u_submit'] == 'Create User Account'){
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
	$groupList = $GLOBALS['UGP']->group_get(null);
	$groupListSelect = $GLOBALS['UGP']->group_format($groupList, '<option value="%i%">%n%</option>');

$this->pageArea.= '

<fieldset>
	<legend>Add New User</legend>
<form action="?'.$_SERVER['QUERY_STRING'].'" method="POST">
	<input type="hidden" name="u_initialusername" value="'.$_POST['u_username'].'" />
	<input type="hidden" name="u_uid" value="" />
	<label for="u_username">User Name</label><input type="text" name="u_username" value="'.$_POST['u_username'].'"/><br/>
	<label for="u_name">Nick Name</label><input type="text" name="u_name" value="'.$_POST['u_fname'].'"/><br/>
	<label for="u_email">e-Mail Address</label><input type="text" name="u_email" value="'.$_POST['u_email'].'"/><br/>
	<hr/>
	<label for="u_status">Account Status</label>
	<select name="u_status">
		'.$statuses.'
	</select>
	<hr/>
	<label for="u_autopass">Auto-Generate a Password?</label><input type="checkbox" name="u_autopass" style="width: 2em;"
	onclick="changeClass(\'passwordinput\',\'itemhide\',\'itemshowblock\');"/><br/>
	<span class="itemshowblock" id="passwordinput">
	<label for="u_pass1">Password</label><input type="password" name="u_pass1" value="'.$_POST['u_pass1'].'"/><br/>
	<label for="u_pass2">Password (Again)</label><input type="password" name="u_pass2" value="'.$_POST['u_pass2'].'"/>
	</span>
	<label for="u_submit">&nbsp;</label><input type="submit" name="u_submit" value="Create User Account" />
	<div class="ff">&nbsp;</div>
</form>
</fieldset>

';

}elseif(	$_POST['u_submit'] == 'Edit User' || $_POST['u_submit'] == 'Add Membership' ||
			$_POST['u_submit'] == 'Save User Details' || $_POST['u_submit'] == 'Remove Membership'){

$this->breadcrumbs.=' &raquo; Edit User';

if($_POST['u_uid'] == $_SESSION['loginid']){
	$this->messageAddAlert('You are discouraged from editing your own account from this plugin.<br/>
	If you wish to change your account settings, please use the "<a href="?phylobyte=account">My Account</a>" link. ');
}

$userExists = $GLOBALS['UGP']->user_get(stripslashes($_POST['u_uid']));

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

';

//list of existing groups
$groupList = $GLOBALS['UGP']->group_get(null, null, $_POST['u_uid'], true);

$groupMemberships = $GLOBALS['UGP']->group_get(null, null, $_POST['u_uid']);

$groupMembershipTemplate = '
	<tr id="u_memberships_row_%i%" class="table_row_normal">
	<td style="text-align: center;">
		<input type="radio" name="g_mid" value="%mi%"
		onchange="changeLast(\'table_row_normal\', \'table_row_highlight\');changeClass(\'u_memberships_row_%i%\', \'table_row_normal\', \'table_row_highlight\');"
		style="cursor: pointer;"/>
	</td>
	<td>%n%</td><td>%d%</td>
	</tr>';

$groupMembershipList = $GLOBALS['UGP']->group_format($groupMemberships, $groupMembershipTemplate);

$groupListSelect = null;
foreach($groupList as $groupArray){
	$groupListSelect.='
	<option value="'.$groupArray['id'].'">'.$groupArray['name'].'</option>
	';
}

if($groupMembershipList != ''){
	$groupMembershipRemove = '<label for="u_submit">&nbsp;</label>
	<div class="destructive">
	<input type="submit" name="u_submit" value="Remove Membership" />
	<div class="ff">&nbsp;</div>
	</div>';
}else{
	$groupMembershipRemove = '<p style="text-align: center;">This user is not a member of any groups.</p>';
}


$this->pageArea.= $GLOBALS['UGP']->user_format($userExists, '

<fieldset>
	<legend>Edit User</legend>
<form action="?'.$_SERVER['QUERY_STRING'].'" method="POST">
	<input type="hidden" name="u_uid" value="%i%" />

	<h3>User Details</h3>
	
	<label for="u_username">User Name</label><input type="text" name="u_username" value="%u%"/><br/>
	<label for="u_name">Nick Name</label><input type="text" name="u_name" value="%n%"/><br/>
	<label for="u_email">e-Mail Address</label><input type="text" name="u_email" value="%e%"/><br/>
	<hr/>
	<label for="u_status">Account Status</label>
	<select name="u_status">
		<option value="%s%">Preserve status: %s%</option>
		<option value="active">Active</option>
		<option value="disabled">Disabled</option>
		<option value="suspended">Suspended</option>
		<option value="flagged">Flagged</option>
		<option value="reserved">Reserved (No password changes allowed)</option>
	</select>
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
	<label for="u_done">&nbsp;</label><input type="submit" name="u_done" value="Done Editing User" />
		<div class="ff">&nbsp;</div>

	<h3 style="margin-top: 2em;">Group Memberships</h3>

	<table class="selTable">
		<tr>
			<th>Select</th><th>Group Name</th><th>Description</th>
		</tr>
		'.$groupMembershipList.'
	</table>

	<label for="u_group">Select Group</label>
	<select name="u_group">
		'.$groupListSelect.'
	</select><br/>

	<label for="u_submit">&nbsp;</label><input type="submit" name="u_submit" value="Add Membership" />
	<div class="ff">&nbsp;</div>
	
	'.$groupMembershipRemove.'

</form>
</fieldset>

');

}elseif($_POST['u_submit'] == 'Edit Attributes' || $_POST['u_submit'] == 'Set User Value'){

$this->breadcrumbs.=' &raquo; Edit Attributes';

$groupMemberships = $GLOBALS['UGP']->group_get(null, null, $_POST['u_uid']);

foreach($groupMemberships as $groupMembership) {

	$attributes = $GLOBALS['UGP']->user_getattrs($_POST['u_uid'], $groupMembership['id']);

	if($attributes == false){
		$this->pageArea.= '

		<fieldset>
			<legend>Edit User Attributes for Group "'.$groupMembership['name'].'"</legend>
		<form action="?'.$_SERVER['QUERY_STRING'].'" method="POST">
			<h3 style="text-align: center;">This group has no attributes to edit.</h3>
			<div style="display: block; text-align: right;">
			<input type="submit" name="u_done" value="Done Editing Attributes" style="width: 14em;"/>
			</div>
			<div class="ff">&nbsp;</div>
		</form>
		</fieldset>

		';
	}else{

		$attributesTable = null;
		foreach($attributes as $attribute) {
		    $attributesTable.= '
				<tr id="u_table_row_'.$attribute['id'].'" class="table_row_normal">
					<td style="text-align: center;">
						<input type="radio" name="a_id" value="'.$attribute['id'].'"
						onchange="changeLast(\'table_row_normal\', \'table_row_highlight\');changeClass(\'u_table_row_'.$attribute['id'].'\', \'table_row_normal\', \'table_row_highlight\');"
						style="cursor: pointer;"/>
					</td>
					<td>'.$attribute['attribute'].'</td>
					<td>'.$attribute['defaultvalue'].'</td>
					<td>'.$attribute['value'].'</td>
				</tr>
		    ';
		}
	
		$this->pageArea.= '

		<fieldset>
			<legend>Edit User Attributes for Group "'.$groupMembership['name'].'"</legend>
		<form action="?'.$_SERVER['QUERY_STRING'].'" method="POST">
		<input type="hidden" name="u_uid" value="'.stripslashes($_POST['u_uid']).'" />
			<table class="selTable">
				<tr>
					<th style="width: 6em;">Select</th><th style="width: 6em;">Attribute</th><th>Default Value</th><th>User Value</th>
				</tr>
			'.$attributesTable.'
			</table>
			<div style="display: block; text-align: right;">
			<label for="a_newdefault">New User Value</label><input type="text" name="a_newdefault" value=""/><br/>
			<input type="submit" name="u_submit" value="Set User Value" style="width: 14em;"/>
			<input type="submit" name="u_done" value="Done Editing Attributes" style="width: 14em;"/>
			</div>
			<div class="ff">&nbsp;</div>
		</form>
		</fieldset>

		';
	}
}




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
		<td>%e%</td>
		<td style="text-align: center; font-weight: bold;"><span style="color: %sC%;">%s%</span></td>
	</tr>
';

$userListTable = $GLOBALS['UGP']->user_format($userList, $userListFormat);


$this->pageArea.= '

<fieldset>
	<legend>Add New User</legend>
<form action="?'.$_SERVER['QUERY_STRING'].'" method="POST">
	<label for="u_username">User Name</label><input type="text" name="u_username" value=""/><br/>
	<label for="u_submit">&nbsp;</label><input type="submit" name="u_submit" value="Add User" />
		<div class="ff">&nbsp;</div>
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
			<th style="width: 6em;">Select</th><th>User Name</th><th>e-Mail</th><th style="width: 6em;">Status</th>
		</tr>
		'.$userListTable.'
	</table>

	<div style="display: block; text-align: right;">
		<input type="submit" name="u_submit" value="Edit User"/>
		<input type="submit" name="u_submit" value="Edit Attributes"/>
		<div class="ff">&nbsp;</div>
		<div class="destructive">
		<input type="submit" name="u_submit" value="Delete User"/>
		<div class="ff">&nbsp;</div>
		</div>
	</div>
		<div class="ff">&nbsp;</div>
</form>
</fieldset>

';

}

?>
