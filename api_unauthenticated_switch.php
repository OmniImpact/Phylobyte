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

case 'teacher_register':
	$DB = $this->DB;
	
	$username = $this->with['username'];
	$passwordhash = $this->with['passhash'];
	$email = $this->with['email'];
	$nickname = $this->with['nickname'];
	
	$usernameQuoted = $DB->quote($username);
	$passhashQuoted = $DB->quote($passwordhash);
	$emailQuoted = $DB->quote($email);
	$nicknameQuoted = $DB->quote($nickname);
	
	//make sure that the user name is not taken
	$checkUserName = $DB->prepare("
		SELECT id FROM p_users WHERE username = $usernameQuoted;
	");
	$checkUserName->execute();
	
	$checkResults = $checkUserName->fetchAll(PDO::FETCH_ASSOC);
	
	if(count($checkResults) > 0){
		$this->result['success'] = false;
		$this->result['message'] = 'That user name is taken.';
	}else{
		//ok to insert
		$getGroupId = $DB->prepare("
			SELECT * FROM p_groups WHERE name='teachers';
		");
		$getGroupId->execute();
		$groupId = $getGroupId->fetchAll(PDO::FETCH_ASSOC);
		$groupId = $groupId[0]['id'];
		
		$addNewTeacher = $DB->prepare("
			INSERT INTO p_users (username, name, email, passwordhash, status)
			VALUES ($usernameQuoted, $nicknameQuoted, $emailQuoted, $passhashQuoted, 'active');
		");
		
		if($addNewTeacher->execute()){
		
			$getUserId = $DB->prepare("
				SELECT id FROM p_users WHERE username = $usernameQuoted;
			");
			$getUserId->execute();
			$userIdResult = $getUserId->fetchAll(PDO::FETCH_ASSOC);
			$userId = $userIdResult[0]['id'];
			
			$userIdQuoted = $DB->quote($userId);
			$groupIdQuoted = $DB->quote($groupId);
			
			$addToGroup = $DB->prepare("
				INSERT INTO p_memberships (userid, groupid)
				VALUES ($userIdQuoted, $groupIdQuoted);
			");
		
			if($addToGroup->execute()){
				$this->result['success'] = true;
				$this->result['message'] = 'Successfully added user account.';
			}
		}
	}
	
break;
	
case 'search_teachers':

	$DB = $this->DB;
	$quotedSearchterm = $DB->quote('%'.$this->with['searchterm'].'%');
	
	$searchTeachers = $DB->prepare("
		SELECT DISTINCT
		p_users.id, p_users.username, p_users.name, p_users.email
		FROM p_users, p_groups, p_memberships, cc_courses
		WHERE p_groups.name = 'teachers'
		AND p_users.id = p_memberships.userid
		AND p_groups.id=p_memberships.groupid
		AND (cc_courses.createdby=p_users.id) AND
		(p_users.username LIKE $quotedSearchterm OR p_users.name LIKE $quotedSearchterm OR
		p_users.email LIKE $quotedSearchterm OR cc_courses.coursename LIKE $quotedSearchterm OR
		cc_courses.subject LIKE $quotedSearchterm OR cc_courses.schoolname LIKE $quotedSearchterm OR
		cc_courses.coursecode LIKE $quotedSearchterm OR cc_courses.locationschoolzip LIKE $quotedSearchterm)
		UNION
		SELECT DISTINCT
		p_users.id, p_users.username, p_users.name, p_users.email
		FROM p_users, p_groups, p_memberships
		WHERE p_groups.name = 'teachers'
		AND p_users.id = p_memberships.userid
		AND p_groups.id=p_memberships.groupid AND
		(p_users.username LIKE $quotedSearchterm OR p_users.name LIKE $quotedSearchterm OR
		p_users.email LIKE $quotedSearchterm)
		;
	");
	$searchTeachers->execute();
	$searchResults = $searchTeachers->fetchAll(PDO::FETCH_ASSOC); 
		
	$this->result['data'] = $searchResults;
	$this->result['message'] = 'Search returned '.count($searchResults).' results.';
	$this->result['success'] = true;

break;

case 'search_courses':

	$DB = $this->DB;
	$quotedSearchterm = $DB->quote('%'.$this->with['searchterm'].'%');
	
	$searchCourses = $DB->prepare("
		SELECT DISTINCT
		p_users.name AS teacher, p_users.email,
		cc_courses.coursename, cc_courses.coursecode,
		cc_courses.schoolname, cc_courses.subject, cc_courses.description
		FROM p_users, p_groups, p_memberships, cc_courses
		WHERE p_groups.name = 'teachers'
		AND p_users.id = p_memberships.userid
		AND p_groups.id=p_memberships.groupid
		AND cc_courses.createdby=p_users.id AND
		(p_users.email LIKE $quotedSearchterm OR p_users.name LIKE $quotedSearchterm OR
		cc_courses.coursename LIKE $quotedSearchterm OR cc_courses.description LIKE $quotedSearchterm OR
		cc_courses.subject LIKE $quotedSearchterm OR cc_courses.schoolname LIKE $quotedSearchterm OR
		cc_courses.coursecode LIKE $quotedSearchterm OR cc_courses.locationschoolzip LIKE $quotedSearchterm)
		ORDER BY cc_courses.coursename;
	");
	$searchCourses->execute();
	$searchResults = $searchCourses->fetchAll(PDO::FETCH_ASSOC); 
		
	$this->result['data'] = $searchResults;
	$this->result['message'] = 'Search returned '.count($searchResults).' results.';
	$this->result['success'] = true;

break;

default:
	$this->result['message'] = 'Error: Requested action not available.';
break;

} ?> 
