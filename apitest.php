<?php
session_start();

if($_POST['json_password'] != null) $_POST['json_passhash'] = sha1($_POST['json_password']);

$_SESSION['post_action'] = stripslashes($_POST['action']);

foreach($_POST as $key => $value){
	if(substr($key, 0, 5) == 'json_'){
		$jsonArray[substr($key, 5)] = stripslashes($value);
	}
}

$_SESSION['post_json'] = json_encode($jsonArray);
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
<input type="hidden" name="action" value="login" />
<label for="json_email">eMail</label><input name="json_email" /><br/>
<label for="json_password">Password</label><input name="json_password" /><br/>
<input type="submit" />
</form>

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

</div>

<div class="rightcol">
<?php
$jsonSearch = Array(
	',',
	'{',
	'}'
);
$jsonReplace = Array(
	",\n\t",
	"{\n\t",
	"\n}"
);
?>
<div style=""><?php echo('<strong>POST[\'action\']</strong><br/><pre>'.$_SESSION['post_action'].'</pre><strong>POST[\'json\']</strong><br/><pre>'.str_replace($jsonSearch, $jsonReplace, $_SESSION['post_json']).'</pre>'); ?></div>
<iframe style="width: 100%; height: 600px;" src="api.php"></iframe>

</div>

</body>

</html>