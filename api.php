<?php
session_start();
if(isset($_SESSION['post_action'])) $_POST['action'] = $_SESSION['post_action'];
if(isset($_SESSION['post_json'])) $_POST['json'] = $_SESSION['post_json'];

$action = stripslashes($_POST['action']);
$actionjson = json_decode(stripslashes($_POST['json']), true);

header('Cache-Control: no-cache, must-revalidate');
header('Content-type: application/json');

$dbinfo = unserialize(file_get_contents('data/dbconfig.array'));

function denumber_array($simpleArray){
	
	if(count($simpleArray) > 1){
		foreach($simpleArray as $key => $value){
			if(!is_int($key)){
				$resultArray[$key] = $value;
			}
		}
	}
	
	return $resultArray;

};

try{
	$kDB = new PDO('mysql:host='.$dbinfo['dbh'].';dbname='.$dbinfo['dbn'], $dbinfo['dbu'], $dbinfo['dbp']);
}catch(PDOException $e){
	echo 'Failed to open database: '.$e;
}

$output = null;

switch($action){

	case 'login':
		$quotedEmail = $kDB->quote($actionjson['email']);
		$quotedPasshash = $kDB->quote($actionjson['passhash']);
		
		$user = $kDB->prepare("
			SELECT id,username,status,email,fname,lname,description
			FROM p_users WHERE email=$quotedEmail
			AND passwordhash=$quotedPasshash AND status='active';");
		$user->execute();
		$user = $user->fetchAll();
		if(count($user) == 1){
			$output = denumber_array($user[0]);
		}else{
			$output = 'false';
		}
	break;
	
	case 'register_checkcredentials':
		//check that the passwords match and email is not yet registered
		
		$pass1 = $actionjson['password'];
		$pass2 = $actionjson['passrepeat'];
		
		if(strlen($pass1) < 4){
			$output['message'].='Password should be four or more characters. ';
		}
		if($pass1 != $pass2){
			$output['message'].= 'Passwords need to match. ';
		}
		
		include_once('plugins/EmailAddressValidator.php');
		$validator = new EmailAddressValidator;
		if(!$validator->check_email_address($actionjson['email'])) {
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

$output = json_encode($output);

$jsonSearch = Array(
	',',
	'{',
	'}',
	']'
);
$jsonReplace = Array(
	",\n\t",
	"{\n\t",
	"\n}",
	"]\n"
);

echo(str_replace($jsonSearch, $jsonReplace, $output));

?>