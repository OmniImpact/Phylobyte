<?php
$execStartTime = microtime(true);
session_start();
if(isset($_SESSION['post_for'])) $_POST['for'] = $_SESSION['post_for'];
if(isset($_SESSION['post_do'])) $_POST['do'] = $_SESSION['post_do'];
if(isset($_SESSION['post_token'])) $_POST['token'] = $_SESSION['post_token'];

if(isset($_SESSION['post_with'])) $_POST['with'] = $_SESSION['post_with'];

$for = stripslashes($_POST['for']);
$do = stripslashes($_POST['do']);
$with = json_decode(stripslashes($_POST['with']), true);
$token = stripslashes($_POST['token']);

header('Cache-Control: no-cache, must-revalidate');
header('Content-type: application/json');

$dbinfo = unserialize(file_get_contents('data/dbconfig.array'));
try{
	$DB = new PDO('mysql:host='.$dbinfo['dbh'].';dbname='.$dbinfo['dbn'], $dbinfo['dbu'], $dbinfo['dbp']);
	$GLOBALS['APIDB'] = $DB;
}catch(PDOException $e){
	echo 'Failed to open database: '.$e;
}

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

function get_attribute($group, $attribute, $userid){
	$DB = $GLOBALS['APIDB'];
	//first, try to get the group id
	$quotedGroup = $DB->quote($group);
	
	$groupId = $DB->prepare("
		SELECT id FROM p_groups WHERE name=$quotedGroup;
	");
	$groupId->execute();
	$groupId = denumber_array($groupId->fetch());
	if(ctype_digit($groupId['id'])){
		$groupId = $groupId['id'];
	}else{
		return false;
	}

	//ok, we have a group id, now we need the attributeid
 	$quotedAttribute = $DB->quote($attribute);

 	$attributeDefalt = null;
 	$attributeId = $DB->prepare("
 		SELECT id, defaultvalue FROM p_gattributes WHERE attribute=$quotedAttribute AND gid=$groupId;
 	");
 	$attributeId->execute();
 	$attributeId = denumber_array($attributeId->fetch());
 	if(ctype_digit($attributeId['id'])){
 		$attributeDefalt = $attributeId['defaultvalue'];
 		$attributeId = $attributeId['id'];
 	}else{
 		return false;
	}

	//now we have an attributeid and default value, we need to see if the user has a value
	$userValue = null;
 	$userValue = $DB->prepare("
 		SELECT value FROM p_uattributes WHERE uid=$userid AND aid=$attributeId;
 	");
 	$userValue->execute();
 	$userValue = denumber_array($userValue->fetch());
 	if(trim($userValue['value']) != null){
 		$userValue = $userValue['value'];
 	}else{
 		return false;
	}

	if($userValue == null){
		return $attributeDefalt;
	}else{
		return $userValue;
	}
	
	return false;
}


function put_attribute($group, $attribute, $userid, $value=null){
	$DB = $GLOBALS['APIDB'];
	//first, try to get the group id
	$quotedGroup = $DB->quote($group);
	
	$groupId = $DB->prepare("
		SELECT id FROM p_groups WHERE name=$quotedGroup;
	");
	$groupId->execute();
	$groupId = denumber_array($groupId->fetch());
	if(ctype_digit($groupId['id'])){
		$groupId = $groupId['id'];
	}else{
		return false;
	}

	//ok, we have a group id, now we need the attributeid
 	$quotedAttribute = $DB->quote($attribute);

 	$attributeId = $DB->prepare("
 		SELECT id FROM p_gattributes WHERE attribute=$quotedAttribute AND gid=$groupId;
 	");
 	$attributeId->execute();
 	$attributeId = denumber_array($attributeId->fetch());
 	if(ctype_digit($attributeId['id'])){
 		$attributeId = $attributeId['id'];
 	}else{
 		return false;
	}

	//now we have an attributeid, we need to see if the user has a value
	$userValue = null;
 	$userValue = $DB->prepare("
 		SELECT value FROM p_uattributes WHERE uid=$userid AND aid=$attributeId;
 	");
 	$userValue->execute();
 	$userValue = denumber_array($userValue->fetch());
 	if(trim($userValue['value']) != null){
 		$userValue = $userValue['value'];
 	}else{
 		$userValue = false;
	}
	
	//if $value is null, delete the existing value if it exists, otherwise just return true
	if($value == null && $userValue == false){
		return true;
	}else if($value == null && $userValue != false){
		//delete the value
		$deleteValue = $DB->prepare("
			DELETE FROM p_uattributes WHERE uid=$userid AND aid=$attributeId;
		");
		if($deleteValue->execute()){
			return true;
		}else{
			return false;
		}
	}else if($value != null && $userValue != false){
		//update the value
		$quotedValue = $DB->quote($value);
		$updateValue = $DB->prepare("
			UPDATE p_uattributes SET value=$quotedValue WHERE uid=$userid AND aid=$attributeId;
		");
		if($updateValue->execute()){
			return true;
		}else{
			return false;
		}
	}else if($value != null && $userValue == false){
		//we need to add it
		$quotedValue = $DB->quote($value);
		$updateValue = $DB->prepare("
			INSERT INTO p_uattributes (uid, aid, value) VALUES ($userid, $attributeId, $quotedValue);
		");
		if($updateValue->execute()){
			return true;
		}else{
			return false;
		}
	}
	return false;
}

function genRandomString($source, $length) {
	$string = '';
	for ($p = 0; $p < $length; $p++) {
		$string .= $source[mt_rand(0, strlen($source))];
	}
	return $string;
}

$output = null;
$messageString = null;
$successStatus = false;
$dataObj = null;

if(trim($_POST['for']) == null){
//these are functions not tied to a user

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
				//generate token

				//write token
				$token = genRandomString('0123456789', 64);
				put_attribute('raphraseUsers', 'loginToken', $data['userid'], $token);

				//read token
				$data['token'] = get_attribute('raphraseUsers', 'loginToken', $data['userid']);
				
				$dataObj = $data;
				
			}else{
				$successStatus = false;
				$messageString = 'Could not locate an active user account.';
			}
		}
	break;

}

}else{

//first, verify that the user and token match
if($token == get_attribute('raphraseUsers', 'loginToken', $for)){

switch($do){

	case 'get_attribute':
		$return = get_attribute($with['group'], $with['attribute'], $for);
		if($return){
			$successStatus = true;
			$messageString = 'Successfully retrieved value for attribute.';
			$dataObj['group'] = $with['group'];
			$dataObj['attribute'] = $with['attribute'];
			$dataObj['value'] = $return;
		}
	break;

	case 'put_attribute':
		put_attribute($with['group'], $with['attribute'], $for, $with['value']);
		$return = get_attribute($with['group'], $with['attribute'], $for);
		if($return){
			$successStatus = true;
			$messageString = 'Successfully set value for attribute.';
			$dataObj['group'] = $with['group'];
			$dataObj['attribute'] = $with['attribute'];
			$dataObj['value'] = $return;
		}
	break;

}

}else{

	$successStatus = false;
	$messageString = 'Unable to verify login with token.';

}

}

$output['success'] = $successStatus;
$output['timestamp'] = time();
$output['exectime'] = microtime(true)-$execStartTime;
$output['message'] = $messageString;
$output['data'] = $dataObj;

$output = json_encode($output);

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

echo(trim(str_replace($jsonSearch, $jsonReplace, $output)));

?>