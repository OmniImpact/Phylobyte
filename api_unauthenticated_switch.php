<?php switch($this->do){
	
case 'login':
	$DB = $this->DB;
	
	$quotedUsername = $DB->quote($this->with['username']);
	$quotedPasshash = $DB->quote($this->with['passhash']);

	if(trim($this->with['username']) == null || trim($this->with['passhash']) == null){
		$this->result['success'] = false;
		$this->result['message'] = 'You must enter a username and password.';
	}else{
		$user = $DB->prepare("
			SELECT id,username,status,passwordhash
			FROM p_users WHERE username=$quotedUsername
			AND passwordhash=$quotedPasshash AND status='active';");
		$user->execute();
		$user = $user->fetchAll(PDO::FETCH_ASSOC);
		if(count($user) == 1){
			
			$this->result['success'] = true;
			$this->result['message'] = 'Login successful.';
			$userData = $user[0];
			
			//make sure the user is in apiUsers
			$quotedId = $this->DB->quote($userData['id']);
			$checkQuery = $this->DB->prepare("
				SELECT p_memberships.id, p_groups.id AS 'groupid', p_groups.name AS 'groupname'
				FROM p_memberships INNER JOIN p_groups ON p_memberships.groupid = p_groups.id
				WHERE p_memberships.userid=$quotedId  AND p_groups.name='apiUsers'
			");
			$checkQuery->execute();
			$checkResults = $checkQuery->fetchAll(PDO::FETCH_ASSOC);
			
			if(count($checkResults) < 1){
			
				$getGroupIdQuery = $this->DB->prepare("
					SELECT id FROM p_groups WHERE name='apiUsers';
				");
				$getGroupIdQuery->execute();
				$groupIdArray = $getGroupIdQuery->fetchAll(PDO::FETCH_ASSOC);
				$quotedGroupId = $this->DB->quote($groupIdArray[0]['id']);
			
				$this->DB->exec("
					INSERT INTO p_memberships (userid, groupid) VALUES ($quotedId, $quotedGroupId)
				");
			}
			
			//generate token
			$token = $this->genRandomString('0123456789', 64);
			
			//write token
			$this->put_attribute('apiUsers', 'loginToken', $userData['id'], $token);

			//add the last platform
			$this->put_attribute('apiUsers', 'loginLastPlatform', $userData['id'], $this->with['platform']);
			$this->put_attribute('apiUsers', 'loginLastTime', $userData['id'], time());

			//add to result['data']
			$this->result['data']['userid'] = $userData['id'];
			$this->result['data']['token'] = $this->get_attribute('apiUsers', 'loginToken', $userData['id']);
			
		}else{
			$this->result['success'] = false;
			$this->result['message'] = 'Could not locate an active user account.';
		}
	}
break;

default:
	$this->result['message'] = 'Error: Requested action not available.';
break;

} ?> 
