<?php

require_once('plugins/EmailAddressValidator.php');

class phylobyteAPI{
	
	var $DB = null;
	
	var $for = null;
	var $do = null;
	var $with = null;
	var $token = null;
	
	var $format = 'min'; //raw, min, pretty
	var $showInfo = false;
	var $onlyData = false;
	
	var $result = Array();
	
	var $execStartTime = null;
	
	var $apiGroupId = null;
	
	function __construct(){
	
		$dbinfo = unserialize(file_get_contents('data/dbconfig.array'));
		try{
			$this->DB = new PDO('mysql:host='.$dbinfo['dbh'].';dbname='.$dbinfo['dbn'], $dbinfo['dbu'], $dbinfo['dbp']);
		}catch(PDOException $e){
			echo 'Failed to open database: '.$e;
			session_destroy();
		}
		
		//get the apiUsers group ID
		$getGroupId = $this->DB->prepare('SELECT id FROM p_groups WHERE name="apiUsers";');
		$getGroupId->execute();
		$getGroupId = $getGroupId->fetchAll(PDO::FETCH_ASSOC);
		
		
		
		if(count($getGroupId) < 1){
			//make sure that the group exists
			$createGroup = $this->DB->prepare('INSERT IGNORE INTO p_groups (name, description) VALUES ("apiUsers", "API User Group");');
			if(!$createGroup->execute()){
				die('Error creating api users group.');
			}
			
			//now get the group again
			$getGroupId = $this->DB->prepare('SELECT id FROM p_groups WHERE name="apiUsers";');
			$getGroupId->execute();
			$getGroupId = $getGroupId->fetchAll(PDO::FETCH_ASSOC);
			
			$getGroupIdArray = array_pop($getGroupId);
			$this->apiGroupId = $getGroupIdArray['id'];
			
			//if I'm here, i also need to initialize the group attributes
			
			$addAttributePlatform = $this->DB->prepare("
				INSERT INTO p_gattributes (gid, attribute, defaultvalue) VALUES ({$this->apiGroupId}, 'loginLastPlatform', 'none');
			");
			$addAttributeTime = $this->DB->prepare("
				INSERT INTO p_gattributes (gid, attribute, defaultvalue) VALUES ({$this->apiGroupId}, 'loginLastTime', 'none');
			");
			$addAttributeToken = $this->DB->prepare("
				INSERT INTO p_gattributes (gid, attribute, defaultvalue) VALUES ({$this->apiGroupId}, 'loginToken', 'none');
			");

			$addAttributePlatform->execute();
			$addAttributeTime->execute();
			$addAttributeToken->execute();

		}else{
			$getGroupIdArray = array_pop($getGroupId);
			$this->apiGroupId = $getGroupIdArray['id'];
		}
		
		$this->result['timestamp'] = null;
		$this->result['success'] = false;
		$this->result['message'] = false;
		$this->result['data'] = false;
		$this->result['exectime'] = false;
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
	}
	
	function get_attribute($group, $attribute, $userid){
		$DB = $this->DB;
		//first, try to get the group id
		$quotedGroup = $DB->quote($group);
		
		$groupId = $DB->prepare("
			SELECT id FROM p_groups WHERE name=$quotedGroup;
		");
		$groupId->execute();
		$groupId = $groupId->fetch(PDO::FETCH_ASSOC);
		if(ctype_digit($groupId['id'])){
			$groupId = $groupId['id'];
		}else{
			return false;
		}

		//ok, we have a group id, now we need the attributeid
		$quotedAttribute = $DB->quote($attribute);

		$attributeDefault = null;
		$attributeId = $DB->prepare("
			SELECT id, defaultvalue FROM p_gattributes WHERE attribute=$quotedAttribute AND gid=$groupId;
		");
		$attributeId->execute();
		$attributeId = $attributeId->fetch(PDO::FETCH_ASSOC);
		if(ctype_digit($attributeId['id'])){
			$attributeDefault = $attributeId['defaultvalue'];
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
		$userValue = $userValue->fetch(PDO::FETCH_ASSOC);
		if(trim($userValue['value']) != null){
			$userValue = $userValue['value'];
		}

		if($userValue == null){
			return $attributeDefault;
		}else{
			return $userValue;
		}
		
		return false;
	}


	function put_attribute($group, $attribute, $userid, $value=null){
		$DB = $this->DB;
		//first, try to get the group id
		$quotedGroup = $DB->quote($group);
		
		$groupId = $DB->prepare("
			SELECT id FROM p_groups WHERE name=$quotedGroup;
		");
		$groupId->execute();
		$groupId = $groupId->fetch(PDO::FETCH_ASSOC);
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
		$attributeId = $attributeId->fetch(PDO::FETCH_ASSOC);
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
		$userValue = $userValue->fetch(PDO::FETCH_ASSOC);
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
	
	function set_format($format){
		switch($format){
			case 'raw':
				$this->format = 'raw';
			break;
			
			case 'pretty':
				$this->format = 'pretty';
			break;
			
			case 'tabs':
				$this->format = 'tabs';
			break;
			
			case 'min':
			default:
				$this->format = 'min';
			break;
		}
	}
	
	function set_do($do){
		$this->do = $do;
	}
	
	function set_for($for){
		$this->for = $for;
	}
	
	function set_with($with){
		$this->with = $with;
		
	}
	
	function set_token($token){
		$this->token = $token;
	}
	
	function set_onlydata($onlyData){
		if($onlyData){
			$this->onlyData = true;
		}else{
			$this->onlyData = false;
		}
	}
	
	function set_info($info){
		if($info){
			$this->showInfo = true;
		}else{
			$this->showInfo = false;
		}
	}
	
	function execute(){
	
		$this->result['success'] = false;
		
		$this->execStartTime = microtime(true);
		
		if($this->do == null){
			$this->result['message'] = 'Error: No action specified.';
		}else{
		
			if($this->token == null){
				//do unauthenticated actions
				$this->doUnauthenticated();
			}else{
				//try authentication
				if( $this->checkAuthentication() ){
					$this->result['message'] = 'Alert: Authentication passed.';
					$this->doAuthenticated();
				}else{
					$this->result['message'] = 'Error: Token passed but authentication failed.';
				}
			}
		
		}
		
		$this->result['timestamp'] = time();
		
		if($this->showInfo){
			$this->result['info'] = null;
			$this->result['info'] = array(
				'Author' => 'Daniel Marcus',
				'Version' => '1.9',
				'Type' => 'JSON',
				'Formatting' => $this->format,
				'input' => array(
					'for' => $this->for,
					'do' => $this->do,
					'with' => $this->with,
					'token' => $this->token
				)
			);
		}
		
		$this->result['exectime'] = microtime(true)-$this->execStartTime;
	}
	
	function checkAuthentication(){
		if($this->token == $this->get_attribute('apiUsers', 'loginToken', $this->for)){
		//echo($this->get_attribute('apiUsers', 'loginToken', $this->for));
			return true;
		}else{
			return false;
		}
	}
	
	function doUnauthenticated(){
		include('api_unauthenticated_switch.php');
	}
	
	function doAuthenticated(){
		include('api_authenticated_switch.php');
	}
	
	function getResult(){
		return $this->result;
	}
	
	function getResultString(){
	
		if($this->onlyData){
			$results = $this->result['data'];
		}else{
			$results = $this->result;
		}

		switch ($this->format) {
		 
			case 'pretty':
				return $this->json_format(json_encode($results), '  ');
			break;
			
			case 'tabs':
				return $this->json_format(json_encode($results));
			break;
			
			case 'raw':
				return print_r($results, true);
			break;
			
		    case 'min':
		    default:
				return json_encode($results);
		    break;
		    
		}
	}
	
	function json_format($json, $tablulator = "\t"){
		$tab = $tablulator;
		$new_json = "";
		$indent_level = 0;
		$in_string = false;

		$json_obj = json_decode($json);

		if($json_obj === false)
			return false;

		$json = json_encode($json_obj);
		$len = strlen($json);

		for($c = 0; $c < $len; $c++){
			$char = $json[$c];
			switch($char){
				case '{':
				case '[':
					if(!$in_string){
						$new_json .= $char . "\n" . str_repeat($tab, $indent_level+1);
						$indent_level++;
					}else{
						$new_json .= $char;
					}
					break;
				case '}':
				case ']':
					if(!$in_string){
						$indent_level--;
						$new_json .= "\n" . str_repeat($tab, $indent_level) . $char;
					}else{
						$new_json .= $char;
					}
					break;
				case ',':
					if(!$in_string){
						$new_json .= ",\n" . str_repeat($tab, $indent_level);
					}else{
						$new_json .= $char;
					}
					break;
				case ':':
					if(!$in_string){
						$new_json .= ": ";
					}else{
						$new_json .= $char;
					}
					break;
				case '"':
					if($c > 0 && $json[$c-1] != '\\'){
						$in_string = !$in_string;
					}
				default:
					$new_json .= $char;
					break;
			}
		}

		return $new_json;
	}
	
}

?>