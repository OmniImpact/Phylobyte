<?php
session_start();

function rpApi($action, $optsArray, $array = true, $apiUrl = 'http://raphrase.com/api.php'){

	$CI = curl_init();

	curl_setopt($CI, CURLOPT_URL, $apiUrl);
	curl_setopt($CI, CURLOPT_POST, 2);
	curl_setopt($CI, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($CI, CURLOPT_POSTFIELDS, 'action='.urlencode($action).'&json='.urlencode(json_encode($optsArray)) );

	$response = curl_exec($CI);

	curl_close($CI);
	
	if($array){
		$response = json_decode($response, true);
	}

	return $response;

}

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">

<html xmlns="http://www.w3.org/1999/xhtml">

<head>

<title>R&#257;Phrase</title>

<link rel="stylesheet" type="text/css" href="oi_reset.css" />
<link rel="stylesheet" type="text/css" href="style_base.css" />
<link rel="icon" 
      type="image/icon" 
      href="http://raphrase.com/gfx/favicon.ico" />

</head>

<body>

<div class="header">
<img src="gfx/raphrase_logo.png" alt="kopyuluh" />
</div>

<div class="container">

<h1>Welcome to R&#257;Phrase!</h1>

<p>Find our app on the Android Play Store. Having trouble registering? Try our online registration below.</p>

<div class="rightbanner">
	<img src="gfx/raphase_rightbanner.png" alt="banner"/>
</div>

<fieldset>
<?php

switch($_GET['action']){

	case 'validate':
		echo('
		<legend>Validate Account</legend>
		');
		//grab the email address and registration number
		
		$valid = rpApi('register_validate',
			Array(
			'email' => stripslashes($_GET['email']),
			'key' => stripslashes($_GET['key'])
			)
		);
		
		if($valid == 'true'){
			$message = 'Thank you, your account has been activated.';
		}else{
			$message = 'I am sorry, there was a problem activating your account.';
		}
		
		//try to validate
		//display success or failure
		echo('
		<script type="text/javascript">
		alert(\''.$message.'\');
		window.opener = \'x\';
		window.open(\'\', \'_self\', \'\');
		window.close();
		</script>
		<p>You may now leave this page and log in.</p>
		');
	break;

	case 'register':
		echo('
		<legend>Checking your registration...</legend>
		');
		
		$results = rpApi('register_checkcredentials',
					Array('email'=>$_POST['email'],
					'password'=>stripslashes($_POST['password']),
					'passrepeat'=>stripslashes($_POST['passrepeat'])
					) );
		
		if($results['result'] == 'success'){
			echo('Processing registration...');
			
			//use the API to register the user
			$result = rpApi('register_update',
						Array('email'=>stripslashes($_POST['email']),
						'passhash'=>sha1(stripslashes($_POST['password'])),
						'new'=>'true'
						) );
						
			if($result != false){
				mail(stripslashes($_POST['email']), 'RaPhrase Account Activation', '
Thank you for registering with RaPhrase!

Before you can use our service, please activate your account
by visiting the link at the end of this email.

We hope you enjoy your experience with us.

Activate your account:
http://raphrase.com/?action=validate&email='.urlencode(stripslashes($_POST['email'])).'&key='.$result.'
');
				echo('<br/>Thank you for registering. Please check your email to validate your account.');
			}else{
				echo('I\'m sorry, there was a problem processing your registration. Please try again later.');
			}
			
		}else{
			echo('There was a problem with your registration:<br/>
			'.$results['message']);
		}
		
		
	break;

	default:
	echo('
	<legend>Register Now</legend>
	<form action="?action=register" method="post">
	<br/>
		<label for="email">eMail</label><input name="email" /><br/>
		<label for="password">Password</label><input name="password" type="password" /><br/>
		<label for="passrepeat">Repeat Password</label><input name="passrepeat" type="password" /><br/>
		<label for="register">&nbsp;</label><input type="submit" name="register" value="Register Now"
		style="border: 2px outset gray; background-color: #eee; margin-left: 3px; padding: 4px; font-size: 90%;" />
	</form>
	');
	
}
?>
</fieldset>

</div>

<div class="footer">
	&copy;2012 RaPhrase
</div>

</body>

</html>