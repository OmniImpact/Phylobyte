<?php
session_start();

if($_POST['with_password'] != null) $_POST['with_passhash'] = sha1($_POST['with_password']);

$_SESSION['post_do'] = stripslashes($_POST['do']);
$_SESSION['post_for'] = stripslashes($_POST['for']);
$_SESSION['post_token'] = stripslashes($_POST['token']);

foreach($_POST as $key => $value){
	if(substr($key, 0, 5) == 'with_'){
		$jsonObj[substr($key, 5)] = stripslashes($value);
	}
}

$_SESSION['post_with'] = json_encode($jsonObj);
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">

<html xmlns="http://www.w3.org/1999/xhtml">

<head>

<title>API Test Script</title>

<link rel="stylesheet" type="text/css" href="oi_reset.css" />
<link rel="stylesheet" type="text/css" href="style_base.css" />

</head>

<body>

<style>
.leftcol{
width: 40%;
float: left;
padding: 4pt;
}
.leftcol form{
display: block;
border: 1px solid gray;
padding: 4pt;
}
.leftcol form label{
display: inline-block;
text-align: right;
width: 40%;
margin-right: .5em;
}
.leftcol form input{
display: inline-block;
width: 50%;
}
.rightcol{
width: 50%;
float: left;
padding: 4pt;
}
</style>

<h1>API Test Page</h1>

<div class="leftcol">

<h2>Log In</h2>
<form action="?" method="post">
<input type="hidden" name="do" value="login" />
<label for="with_username">Username</label><input name="with_username" /><br/>
<label for="with_password">Password</label><input name="with_password" /><br/>
<input type="submit" />
</form>

<!--
<h2>Check Credentials</h2>
<form action="?" method="post">
<input type="hidden" name="action" value="register_checkcredentials" />
<label for="json_email">eMail</label><input name="json_email" /><br/>
<label for="json_password">Password</label><input name="json_password" /><br/>
<label for="json_repeat">Password Repeat</label><input name="json_passrepeat" /><br/>
<input type="submit" />
</form>

<h2>Check Email</h2>
<form action="?" method="post">
<input type="hidden" name="action" value="check_validemail" />
<label for="json_email">eMail</label><input name="json_email" /><br/>
<input type="submit" />
</form>

<h2>Update Registration</h2>
<form action="?" method="post">
<input type="hidden" name="action" value="register_update" />
<label for="json_email">eMail</label><input name="json_email" /><br/>
<label for="json_password">Password</label><input name="json_password" />
<input type="submit" />
</form>
-->

<h2>Get Attribute</h2>
<form action="?" method="post">
<input type="hidden" name="do" value="get_attribute" />
<label for="for">User ID</label><input name="for" /><br/>
<label for="token">Token</label><input name="token" /><br/>
<label for="with_group">Group</label><input name="with_group" /><br/>
<label for="with_attribute">Attribute</label><input name="with_attribute" /><br/>
<input type="submit" />
</form>

<h2>Put Attribute</h2>
<form action="?" method="post">
<input type="hidden" name="do" value="put_attribute" />
<label for="for">User ID</label><input name="for" /><br/>
<label for="token">Token</label><input name="token" /><br/>
<label for="with_group">Group</label><input name="with_group" /><br/>
<label for="with_attribute">Attribute</label><input name="with_attribute" /><br/>
<label for="with_value">New Value</label><input name="with_value" /><br/>
<input type="submit" />
</form>

</div>

<div class="rightcol">
<?php
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
?>
<div style=""><?php
echo('
<strong>POST[\'for\']</strong><br/><pre>'.$_SESSION['post_for'].'</pre>
<strong>POST[\'do\']</strong><br/><pre>'.$_SESSION['post_do'].'</pre>
<strong>POST[\'token\']</strong><br/><pre>'.$_SESSION['post_token'].'</pre>
<strong>POST[\'with\']</strong><br/><pre>'.trim(str_replace($jsonSearch, $jsonReplace, $_SESSION['post_with']).'</pre>'));
?></div>
<iframe style="width: 100%; height: 600px;" src="api.php"></iframe>

</div>

</body>

</html>