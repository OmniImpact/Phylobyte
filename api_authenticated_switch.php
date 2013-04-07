<?php switch($this->do){
	
case 'user_info':
	$userid = $this->DB->quote($this->for);
	$userinfoQuery = "SELECT id, status, username, name, email FROM p_users WHERE id=$userid;";
	$userQuery = $this->DB->prepare($userinfoQuery);
	$userQuery->execute();
	$userinfo = $userQuery->fetchAll(PDO::FETCH_ASSOC);
	$this->result['data'] = $userinfo;
	$this->result['message'] = 'User info for '.$userinfo['username'].'.';
	$this->result['success'] = true;
break;

case 'user_updateinfo':
	$userid = $this->DB->quote($this->for);

	//check that the new user is valid
	$EV = new EmailAddressValidator;

	$continue = true;

	//check valid email
	if(!$EV->check_email_address($this->with['email'])){
		$continue = false;
		$this->result['message'] = 'That email address does not appear to be valid. ';
	}

	//add the new user
	if($continue){

		$updateEmail = $this->DB->quote(stripslashes($this->with['email']));
		$updateNickname = $this->DB->quote($this->with['nickname']);

		$updateinfoQuery = "UPDATE p_users SET name=$updateNickname, email=$updateEmail WHERE id=$userid;";
		$updateinfo = $this->DB->prepare($updateinfoQuery);
		$updateinfo->execute();
		
		$userinfoQuery = "SELECT id, status, username, name, email FROM p_users WHERE id=$userid;";

		$userinfo = $this->DB->prepare($userinfoQuery);
		$userinfo->execute();
		$userinfo = $userinfo->fetchAll(PDO::FETCH_ASSOC);

		$this->result['success'] = true;
		$this->result['message'] = 'Successfully updated user info.';
		$this->result['data'] = $userinfo;
	}else{
		$this->result['success'] = false;
	}
break;

case 'user_updatepassword':
	//we have to assume the password hash is OK

	$userid = $this->DB->quote($this->for);
	$updatePasshash = $this->DB->quote(stripslashes($this->with['passhash']));

	$updatePasshashQuery = "UPDATE p_users SET passwordhash=$updatePasshash WHERE id=$userid;";

	$updatePassword = $this->DB->prepare($updatePasshashQuery);
	$updatePassword->execute();

	$this->result['success'] = true;
	$this->result['message'] = 'Successfully updated password.';
break;

case 'user_groups':
	$userid = $this->DB->quote($this->for);
	$groupsQuery = $this->DB->prepare("
		SELECT DISTINCT * FROM p_groups WHERE EXISTS
		(SELECT * FROM p_memberships WHERE p_memberships.groupid = p_groups.id
		AND p_memberships.userid = $userid)
	");
	$groupsQuery->execute();
	$this->result['data'] = $groupsQuery->fetchAll(PDO::FETCH_ASSOC);
	$this->result['success'] = true;
	$this->result['message'] = 'This user is a member of these groups.';
break;

case 'user_groupcheck':
	$userid = $this->DB->quote($this->for);
	$groupName = $this->DB->quote($this->with['group']);
	$groupsQuery = $this->DB->prepare("
		SELECT DISTINCT * FROM p_groups WHERE EXISTS
		(SELECT * FROM p_memberships WHERE p_memberships.groupid = p_groups.id
		AND p_memberships.userid = $userid AND p_groups.name = $groupName)
	");
	$groupsQuery->execute();
	if(count($groupsQuery->fetchAll(PDO::FETCH_ASSOC)) > 0){
		$this->result['success'] = true;
		$this->result['data'] = true;
		$this->result['message'] = 'Membership confirmed.';
	}else{
		$this->result['success'] = true;
		$this->result['data'] = false;
		$this->result['message'] = 'No membership.';
	}
	
	
break;

case 'get_attribute':
	$data = array();
	$retrieved = $this->get_attribute($this->with['group'], $this->with['attribute'], $this->for);
	if($retrieved){
		$data['group'] = $this->with['group'];
		$data['attribute'] = $this->with['attribute'];
		$data['value'] = $this->get_attribute($this->with['group'], $this->with['attribute'], $this->for);
		$this->result['data'] = $data;
		$this->result['message'] = 'Successfully retrieved attribute.';
		$this->result['success'] = true;
	}else{
		$this->result['message'] = 'Error: Failed to retrieve attribute.';
	}
break;

case 'put_attribute':
	$inserted = $this->put_attribute($this->with['group'], $this->with['attribute'], $this->for, $this->with['value']);
	if($inserted){
		$data = array();
		$retrieved = $this->get_attribute($this->with['group'], $this->with['attribute'], $this->for);
		if($retrieved){
			$data['group'] = $this->with['group'];
			$data['attribute'] = $this->with['attribute'];
			$data['value'] = $this->get_attribute($this->with['group'], $this->with['attribute'], $this->for);
			$this->result['data'] = $data;
		$this->result['message'] = 'Successfully stored attribute.';
			$this->result['success'] = true;
		}else{
			$this->result['message'] = 'Error: Failed to retrieve attribute.';
		}
	}else{
		$this->result['message'] = 'Error: Failed to insert attribute.';
	}
break;

case 'add_course':

	$coursename = $this->with['coursename'];
	$coursecode = $this->with['coursecode'];
	$schoolname = $this->with['schoolname'];
	$locationstate = $this->with['locationstate'];
	$locationschoolzip = $this->with['locationschoolzip'];
	$description = $this->with['description'];
	$subject = $this->with['subject'];
	$createdby = $this->for;
	
	$coursenameQuoted = $this->DB->quote($this->with['coursename']);
	$coursecodeQuoted = $this->DB->quote($this->with['coursecode']);
	$schoolnameQuoted = $this->DB->quote($this->with['schoolname']);
	$locationstateQuoted = $this->DB->quote($this->with['locationstate']);
	$locationschoolzipQuoted = $this->DB->quote($this->with['locationschoolzip']);
	$descriptionQuoted = $this->DB->quote($this->with['description']);
	$subjectQuoted = $this->DB->quote($this->with['subject']);
	$createdbyQuoted = $this->DB->quote($this->for);
	
	$createCourseQuery = $this->DB->prepare("
		INSERT INTO cc_courses
		(coursename, coursecode, schoolname, locationstate, locationschoolzip, description, subject, createdby)
		VALUES
		($coursenameQuoted, $coursecodeQuoted, $schoolnameQuoted, $locationstateQuoted,
		$locationschoolzipQuoted, $descriptionQuoted, $subjectQuoted, $createdbyQuoted)
	");
	
	if($createCourseQuery->execute()){
		$this->result['success'] = true;
		$this->result['message'] = 'Successfully added course.';
	}
	
break;

case 'teacher_getcourses':
	$createdbyQuoted = $this->DB->quote($this->for);
	$coursesQuery = $this->DB->prepare("
		SELECT * FROM cc_courses WHERE createdby=$createdbyQuoted;
	");
	if($coursesQuery->execute()){
		$coursesResults = $coursesQuery->fetchAll(PDO::FETCH_ASSOC);
		$this->result['success'] = true;
		$this->result['data'] = $coursesResults;
	}
break;

default:
	$this->result['message'] = 'Error: Authentication passed, but requested action not available.';
break;

} ?>
