<?php

if($_POST['with_password'] != null) $_POST['with_passhash'] = sha1($_POST['with_password']);

if($_POST['setdefaults'] != null){
	$_SESSION['api_default_id'] = stripslashes($_POST['default_for']);
	$_SESSION['api_default_token'] = stripslashes($_POST['default_token']);
	$_SESSION['api_default_info'] = stripslashes($_POST['default_info']);
}

$_SESSION['post_do'] = stripslashes($_POST['do']);
$_SESSION['post_for'] = stripslashes($_POST['for']);
$_SESSION['post_token'] = stripslashes($_POST['token']);

if($_SESSION['api_default_info']){
	$_SESSION['post_info'] = true;
}else{
	$_SESSION['post_info'] = false;
}

foreach($_POST as $key => $value){
	if(substr($key, 0, 5) == 'with_'){
		$jsonObj[substr($key, 5)] = stripslashes($value);
	}
}

if($jsonObj != null){
$_SESSION['post_with'] = json_encode($jsonObj);
}else{
unset($_SESSION['post_with']);
}


if($_POST['setfeatures'] != null){
	$_SESSION['show_features'] = stripslashes($_POST['selectfeatures']);
}

if($_SESSION['show_features'] != null){
$this->breadcrumbs.=' &raquo; '.$_SESSION['show_features'];
}

$forms = null;

switch($_SESSION['show_features']){

	case 'Login and Registration':
	$forms = '
<form action="?'.$_SERVER['QUERY_STRING'].'" method="post">
<h3>Log In</h3>
<input type="hidden" name="do" value="login" />
<label for="with_username">Username</label><input name="with_username" /><br/>
<label for="with_password">Password</label><input name="with_password" /><br/>
<label for="with_platform">Platform</label><input name="with_platform" /><br/>
<input type="submit" />
<div class="ff">&nbsp;</div>
</form>

';
	break;

	case 'Basic':
	$forms = '
<form action="?'.$_SERVER['QUERY_STRING'].'" method="post">
<h3>Get Attribute</h3>
<input type="hidden" name="do" value="get_attribute" />
<label for="for">User ID</label><input name="for" value="'.$_SESSION['api_default_id'].'"/><br/>
<label for="token">Token</label><input name="token" value="'.$_SESSION['api_default_token'].'"/><br/>
<label for="with_group">Group</label><input name="with_group" /><br/>
<label for="with_attribute">Attribute</label><input name="with_attribute" /><br/>
<input type="submit" />
<div class="ff">&nbsp;</div>
</form>

<form action="?'.$_SERVER['QUERY_STRING'].'" method="post">
<h3>Put Attribute</h3>
<input type="hidden" name="do" value="put_attribute" />
<label for="for">User ID</label><input name="for" value="'.$_SESSION['api_default_id'].'"/><br/>
<label for="token">Token</label><input name="token" value="'.$_SESSION['api_default_token'].'"/><br/>
<label for="with_group">Group</label><input name="with_group" /><br/>
<label for="with_attribute">Attribute</label><input name="with_attribute" /><br/>
<label for="with_value">New Value</label><input name="with_value" /><br/>
<input type="submit" />
<div class="ff">&nbsp;</div>
</form>
	';
	break;

	case 'User and Account Management':
	$forms = '
<form action="?'.$_SERVER['QUERY_STRING'].'" method="post">
<h3>Get User Info</h3>
<input type="hidden" name="do" value="user_info" />
<label for="for">User ID</label><input name="for" value="'.$_SESSION['api_default_id'].'"/><br/>
<label for="token">Token</label><input name="token" value="'.$_SESSION['api_default_token'].'"/><br/>
<input type="submit" />
<div class="ff">&nbsp;</div>
</form>

<form action="?'.$_SERVER['QUERY_STRING'].'" method="post">
<h3>Update User Info</h3>
<input type="hidden" name="do" value="user_updateinfo" />
<label for="for">User ID</label><input name="for" value="'.$_SESSION['api_default_id'].'"/><br/>
<label for="token">Token</label><input name="token" value="'.$_SESSION['api_default_token'].'"/><br/>
<label for="with_nickname">Nickname</label><input name="with_nickname" /><br/>
<label for="with_email">eMail</label><input name="with_email" /><br/>
<input type="submit" />
<div class="ff">&nbsp;</div>
</form>

<form action="?'.$_SERVER['QUERY_STRING'].'" method="post">
<h3>Update Password</h3>
<input type="hidden" name="do" value="user_updatepassword" />
<label for="for">User ID</label><input name="for" value="'.$_SESSION['api_default_id'].'"/><br/>
<label for="token">Token</label><input name="token" value="'.$_SESSION['api_default_token'].'"/><br/>
<label for="with_password">New Password</label><input name="with_password" /><br/>
<input type="submit" />
<div class="ff">&nbsp;</div>
</form>

<form action="?'.$_SERVER['QUERY_STRING'].'" method="post">
<h3>Get Group Membership</h3>
<input type="hidden" name="do" value="user_groups" />
<label for="for">User ID</label><input name="for" value="'.$_SESSION['api_default_id'].'"/><br/>
<label for="token">Token</label><input name="token" value="'.$_SESSION['api_default_token'].'"/><br/>
<input type="submit" />
<div class="ff">&nbsp;</div>
</form>

<form action="?'.$_SERVER['QUERY_STRING'].'" method="post">
<h3>Check Group Membership</h3>
<input type="hidden" name="do" value="user_groupcheck" />
<label for="for">User ID</label><input name="for" value="'.$_SESSION['api_default_id'].'"/><br/>
<label for="token">Token</label><input name="token" value="'.$_SESSION['api_default_token'].'"/><br/>
<label for="with_group">Group Name</label><input name="with_group" /><br/>
<input type="submit" />
<div class="ff">&nbsp;</div>
</form>
';
	break;
	
	default:
		$forms = '
		<br/><br/>
			<strong>Please select a feature set to test.</strong>
		';
}


if($_SESSION['api_default_info']){
	$showInfoChecked = 'checked';
}else{
	$showInfoChecked = null;
}

$pageOutput = '
<div class="testcol">

<form action="?'.$_SERVER['QUERY_STRING'].'" method="post">
<h3>Select API Feature Set</h3>
<label for="selectfeature" style="width: 30%;">&nbsp;</label>
<select name="selectfeatures" style="width: 60%;">
	<option value="">Select Features to Test</option>
	<option>Login and Registration</option>
	<option>Basic</option>
	<option>User and Account Management</option>
</select>
<br/>
<input type="submit" name="setfeatures" value="Select"/>
<div class="ff">&nbsp;</div>
</form>

<form action="?'.$_SERVER['QUERY_STRING'].'" method="post">
<h3>Set Test Defaults</h3>
<label for="default_for">User ID</label><input name="default_for" value="'.$_SESSION['api_default_id'].'"/><br/>
<label for="default_token">Token</label><input name="default_token" value="'.$_SESSION['api_default_token'].'"/><br/>
<label for="default_info">Show Info</label><input name="default_info" '.$showInfoChecked.' type="checkbox" style="width:2em;"/><br/>
<input type="submit" name="setdefaults" value="Set"/>
<div class="ff">&nbsp;</div>
</form>


'.$forms.'
</div>
';

$jsonSearch = Array(
	',',
	'{',
	'}',
	']',
	"{\n\t\"",
	"\n},",
	"\n}\n}"
);
$jsonReplace = Array(
	",\n\t",
	"\n{\n\t",
	"\n}",
	"]\n",
	"\t{\n\t\"",
	"\n\t},",
	"\n\t}\n}"
);

$currentSettings = '
<div style="font-size: 50%; text-align: left;"><pre>
<strong style="font-size: 9pt;">POST[\'for\']</strong><br/>'.$_SESSION['post_for'].
'<hr style="background-color: gray; margin-bottom: -1em;"/>
<strong style="font-size: 9pt;">POST[\'do\']</strong><br/>'.$_SESSION['post_do'].
'<hr style="background-color: gray; margin-bottom: -1em;"/>
<strong style="font-size: 9pt;">POST[\'token\']</strong><br/>'.$_SESSION['post_token'].
'<hr style="background-color: gray; margin-bottom: -1em;"/>
<strong style="font-size: 9pt;">POST[\'with\']</strong><br/>'.trim(str_replace($jsonSearch, $jsonReplace, $_SESSION['post_with']).'</pre>').'</div>
';

$_SESSION['post_session_read'] = true;
if($_SESSION['post_do'] != null){
	$this->messageAddAlert($currentSettings);
}

$pageOutput.= '
<div class="previewcol">
<iframe style="width: 100%; height: 600px; border: none;" src="../api.php?format=highlightnarrow"></iframe>
</div>
';

$this->pageArea.=$pageOutput;

?>