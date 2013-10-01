<?php
session_start();
include('plugins/oi_mobilesupport.php');

$MOBILE = new oi_mobilesupport;

include_once('plugins/api_connector.php');
$API = new api_connector;

include_once('pages/process.php');

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">

<html xmlns="http://www.w3.org/1999/xhtml">

<head>

<title>Phylobyte</title>

<?php $MOBILE->setScale(); ?>

<?php $MOBILE->addMobileStyle(null, 'style'); ?>
<link rel="icon" 
      type="image/icon" 
      href="gfx/favicon.ico" />

</head>

<body>
<div class="setlimits">

<div class="header">
<div class="navigation">
	<dl>
		<dt><a class="dtg" href="?at=home">Home</a></dt>
		<?php
		if($userInfo->name != null){$displayName = $userInfo->name;}else{$displayName = $userInfo->username;}
		
		if($_SESSION['usertoken'] == null){
			echo('<dt><a class="dtb" href="?at=login">Log In</a></dt>');
		}else{
			echo('
			<dt><a class="dtn" href="?at=account">My Account</a></dt>
			<dt><a class="dtb" href="?at=login&do=logout">Log Out, '.$displayName.'</a></dt>');
		}
		?>
		
	</dl>
</div>
	<div class="ff">&nbsp;</div>
</div>

<div class="container">

<?php

$include = stripslashes($_GET['at']);

switch($include){

	case 'about':
	include('pages/about.php');
	break;

	case 'login':
	include('pages/login.php');
	break;

	case 'account':
	include('pages/account.php');
	break;

	default:
	include('pages/home.php');
	break;
}

?>

</div>

<div class="footer">
	&copy;2013 Phylobyte Site Template | <?php $MOBILE->mobileToggle('Go Mobile', 'Desktop Mode'); ?> | <a href="admin">Admin</a>
</div>


</div>
</body>

</html>