<?php

switch($do){

	case 'login':
		$quotedUsername = $DB->quote($with['username']);
		$quotedPasshash = $DB->quote($with['passhash']);

		if(trim($with['username']) == null || trim($with['passhash']) == null){
			$output['success'] = false;
			$output['message'] = 'You must enter a username and password.';
		}else{
			$user = $DB->prepare("
				SELECT id,username,status,passwordhash
				FROM p_users WHERE username=$quotedUsername
				AND passwordhash=$quotedPasshash AND status='active';");
			$user->execute();
			$user = $user->fetchAll();
			if(count($user) == 1){
				$successStatus = true;
				$messageString = 'Login successful.';
				$userData = $user[0];
				$data['userid'] = $userData['id'];
				$data['token'] = 'TOKEN';
				$dataObj = $data;
			}else{
				$successStatus = false;
				$messageString = 'Could not locate an active user account.';
			}
		}
	break;

	case 'register_checked':
		//check that the passwords match and email is not yet registered

		$pass1 = $actionjson['password'];
		$pass2 = $actionjson['passrepeat'];

		include_once('plugins/EmailAddressValidator.php');
		$validator = new EmailAddressValidator;
		if(!$validator->check_email_address($with['email'])) {
			$output['message'].='Email address must be valid. ';
		}

		$quotedEmail = $kDB->quote(trim($actionjson['email']));

		$user = $kDB->prepare("
			SELECT id
			FROM p_users WHERE email=$quotedEmail;");
		$user->execute();
		$user = $user->fetchAll();
		if(count($user) > 0){
			$output['message'].='A user with that email address is already registered. ';
		}

		if($output['message'] == ''){
			$output['message'] = 'Everything looks OK.';
			$output['result'] = 'success';
		}else{
			$output['result'] = 'failure';
		}

	break;

	case 'check_validemail':

		$quotedEmail = $kDB->quote($actionjson['email']);
		include_once('plugins/EmailAddressValidator.php');
		$validator = new EmailAddressValidator;
		if($validator->check_email_address($actionjson['email'])) {
			$output = 'true';
		}else{
			$output = 'false';
		}

	break;

	case 'register_update':
		//note that this uses the "replace" function, so be careful how you call it.
		$quotedEmail = $kDB->quote($actionjson['email']);
		$quotedPasshash = $kDB->quote($actionjson['passhash']);

		function genRandomString($source, $length) {
			$string = '';
			for ($p = 0; $p < $length; $p++) {
				$string .= $source[mt_rand(0, strlen($source))];
			}
			return $string;
		}

		//get ID if available
		$existinguser = $kDB->prepare("
			SELECT id FROM p_users WHERE email=$quotedEmail;
		");
		$existinguser->execute();
		$existinguserID = $existinguser->fetchAll();
		$existinguserID = $existinguserID[0]['id'];
		$quotedID = $kDB->quote($existinguserID);

		if($existinguserID == ''){
			$status = genRandomString('0123456789', 12);
			$quotedStatus = $kDB->quote($status);
			$register = $kDB->prepare("
				INSERT INTO p_users (email, passwordhash, status, primarygroup)
				VALUES ($quotedEmail, $quotedPasshash, $quotedStatus, '2')
			;");
		}else{
			$register = $kDB->prepare("
				UPDATE p_users SET passwordhash=$quotedPasshash WHERE id=$quotedID
			;");
			$status = $existinguserID;
		}

		if($register->execute()){
			if($status == 'active'){
				$output = 'true';
			}else{
				$output = $status;
			}
		}else{
			$output = 'false';
		}

	break;

	case 'register_validate':

		$quotedEmail = $kDB->quote($actionjson['email']);
		$quotedKey = $kDB->quote($actionjson['key']);

		$activate = $kDB->prepare("
			UPDATE p_users SET status='active'
			WHERE email=$quotedEmail AND status=$quotedKey;
		;");

		if($activate->execute()){
			$output = 'true';
		}else{
			$output = 'false';
		}

	break;

}

?>