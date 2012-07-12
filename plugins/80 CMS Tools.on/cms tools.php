<?php

class ugp{

	static $pDB;

	/**
	 * Constructor for phylobyte Users and Groups class.
	 **/
	function __construct(){
		$this->pDB = $GLOBALS['PHYLOBYTEDB'];
	}

	/**
	 * Add or update a group. if an ID is provided and the group exists it will be updated.
	 * @param Array groupArray with id, name, description
	 * @return boolean
	 **/
	function group_put($groupArray){
		//take in a group array, write it to the database
		//use replace, so it will overwrite if an ID is provided

		//make sure we don't change the admin group
		if($groupArray['id'] == 1){
			phylobyte::messageAddAlert('Please note that "admin" is a special group that you can not rename or delete.');
			$groupArray['name'] = 'admin';
		}

		$name = $this->pDB->quote($groupArray['name']);
		$id = $this->pDB->quote($groupArray['id']);
		//check if the group exists
		$group = $this->pDB->prepare("
			SELECT name
			FROM p_groups WHERE name=$name AND id<>$id;");
		$group->execute();
		$group = $group->fetchAll();

		if(count($group) >= 1){
			phylobyte::messageAddError('Failed to update groups.');
			phylobyte::messageAddAlert('A group with that name already exists.');
			return false;
		}

		$description = $this->pDB->quote($groupArray['description']);
		$optional = ($groupArray['id'] != '' ? 'id,' : '');
		$id = ($groupArray['id'] != '' ? $this->pDB->quote($groupArray['id']).',' : '');
		$query = $this->pDB->prepare("
			REPLACE INTO p_groups ($optional name, description )
			VALUES ($id $name, $description ); ");
		if($query->execute()){
			phylobyte::messageAddNotification('Successfully updated groups.');
			return true;
		}else{
			phylobyte::messageAddError('Error updating groups.');
			return false;
		}
	}

	/**
	 * Add or update a user. if an ID is provided and the user exists it will be updated.
	 * @param Array userArray with attributes
	 * @return boolean
	 **/
	function  user_put($userArray){

		//if no user id is supplied, we have a certain set of requirements
		//a user name has to be provided, and either a password or autopass
		function genRandomString($source, $length) {
			$string = '';
			for ($p = 0; $p < $length; $p++) {
				$string .= $source[mt_rand(0, strlen($source))];
			}
			return $string;
		}

		if($userArray['autopass'] == true){
			$userArray['password'] = genRandomString('aaabcdeeefgghhiiijkllmnnooopqrrssttuuuvwxyyz', 6).genRandomString('0123456789', 3);
			phylobyte::messageAddAlert('Generated password: '.$userArray['password']);
		}

		if($userArray['id'] == null){
			if($userArray['name'] == null || strlen($userArray['password']) < 4){
				phylobyte::messageAddError('You need to provide at least a user name and password to create a new user.');
				return false;
			}
		}

		//now, regardless of whether a user id is supplied, we need to make sure
		//the user name is available
		$userNameArray = $this->user_get($userArray['name']);
		if($userNameArray[0]['id'] != $userArray['id'] && $userNameArray[0] != null){
			phylobyte::messageAddError('A user with that name already exists.');
			return false;
		}

		//fail if invalid email
		include_once('../plugins/EmailAddressValidator.php');
		$validator = new EmailAddressValidator;
		if (!$validator->check_email_address($userArray['email']) && $userArray['email'] != null) {
			phylobyte::messageAddError('That is not a valid email address.');
			return false;
		}

		//and just in case, we make sure that it's not the last admin
		//something is WRONG because that is YOU, and this is impossible
		if($userArray['id'] != null && $userArray['primarygroup'] != '1' && $this->group_format($this->group_get('1'), '%m%') == '1'){
			phylobyte::messageAddError('You can not change the group of the last admin.');
			return false;
		}

		//write changes to database, keepind in mind:
		//if the password is null, don't touch it
		//if the user ID is null, don't privide it
		//id, description, passwordhash are optional
		$passwordhash = null;
		if ($userArray['password'] != null) {
		    $passwordhash = sha1($userArray['password']);
		}


		if($userArray['id'] != null){
			$optionalKeys.= 'id, ';
			$optionalVals.= $this->pDB->quote($userArray['id']).', ';
		}
		if($userArray['description'] != null){
			$optionalKeys.= 'description, ';
			$optionalVals.= $this->pDB->quote($userArray['description']).', ';
		}
		if($passwordhash != null){
			$optionalKeys.= 'passwordhash, ';
			$optionalVals.= $this->pDB->quote($passwordhash).', ';
		}

		if($userArray['status'] == null || $userArray['primarygroup'] == null){
			phylobyte::messageAddError('You must supply a status and primary group.');
			return false;
		}

		$name = $this->pDB->quote($userArray['name']);
		$status = $this->pDB->quote($userArray['status']);
		$primarygroup = $this->pDB->quote($userArray['primarygroup']);
		$email = $this->pDB->quote($userArray['email']);
		$fname = $this->pDB->quote($userArray['fname']);
		$lname = $this->pDB->quote($userArray['lname']);
		$personalphone = $this->pDB->quote($userArray['personalphone']);
		$publicphone = $this->pDB->quote($userArray['publicphone']);

		$query = $this->pDB->prepare("
			REPLACE INTO p_users ($optionalKeys username, status, primarygroup, email, fname, lname, personalphone, publicphone)
			VALUES ($optionalVals $name, $status, $primarygroup, $email, $fname, $lname, $personalphone, $publicphone); ");
		if($query->execute()){
			phylobyte::messageAddNotification('Successfully updated users.');
			return true;
		}else{
			phylobyte::messageAddError('Error updating users.');
			return false;
		}
	}


	/**
	 * Delete a group.
	 * @param int groupID
	 * @return boolean
	 **/
	function group_delete($groupID){

		$delete = $this->group_deleteable($groupID);
		if($delete === true){
			$this->pDB->quote($groupID);
			if($this->pDB->exec("
				DELETE FROM p_groups
				WHERE id=$groupID;")
			){ phylobyte::messageAddNotification('Successfully deleted group.'); }
		}else{
			phylobyte::messageAddError('The group could not be deleted: '.$delete);
		}

	}

	/**
	 * Add or delete a user by ID.
	 * @param Int userID
	 * @return boolean
	 **/
	function user_delete($userID){
		//check deleteable
		if($this->user_deleteable($userID)){
			$this->pDB->exec("DELETE FROM p_users WHERE id={$_POST['u_uid']};");
			phylobyte::messageAddNotification('Successfully deleted user.');
			return true;
		}
		return false;
	}

	function group_deleteable($groupID){
		//return TRUE if safe to delete
		//otherwise, return the error

		$group = $this->group_get($groupID);
		if($group['id'] == 1){
			return 'You can not delete the Admin group.';
		}elseif($group['members'] != 0){
			return 'You can not delete a group that still has members.';
		}elseif($group['id'] == ''){
			return 'The group you are trying to delete does not exist.';
		}else{
			return true;
		}

	}

	function user_deleteable($userID){

		$result = true;
		//first, you can't delete yourself

		if($_SESSION['loginid'] == $userID){
			phylobyte::messageAddError('You can not delete yourself.');
			$result = false;
		}
		//next, you can't delete the last admin (this also ensures there is at least one user left)
		$tryDeleteUser = $this->user_get($userID);
		if($tryDeleteUser[0]['primarygroup'] == '1' && $this->group_format($this->group_get($userArray['primarygroup']), '%m%') == '1'){
			phylobyte::messageAddError('You can not delete the last administrator.');
			$result = false;
		}

		return $result;
	}

	/**
	 * Retrieve a group and its information from the database.
	 * @param String groupID pass null and a filter to search groups
	 * @return Array
	 **/
	function group_get($groupID, $groupsFilter = ''){
		//if gruoupID, return array, otherwise return multiple array
		if($groupID != null){
			$group = $this->pDB->prepare("
				SELECT *, (
					SELECT COUNT(*)
					FROM p_users
					WHERE primarygroup=p_groups.id
					) as members
				FROM p_groups WHERE id=$groupID ORDER BY name;");
			$group->execute();
			$group = $group->fetchAll();
			return $group;
		}else{
			$groups = $this->pDB->prepare("
				SELECT *, (
					SELECT COUNT(*)
					FROM p_users
					WHERE primarygroup=p_groups.id
					) as members
				FROM p_groups
				WHERE name LIKE '%$groupsFilter%' ORDER BY name;");
			$groups->execute();
			$groups = $groups->fetchAll();
			return $groups;
		}
	}

	/**
	 * Retrieve a user and its information from the database.
	 * @param String userID otherwise pass null and a filter to search users
	 * @return Array
	 **/
	function user_get($userID, $filter = '', $limit = 100, $orderBy = 'username'){
		//return array or multiple arrays

		//if userID, return array, otherwise return multiple array
		if($userID != null){
			if(ctype_digit($userID)){
				$user = $this->pDB->prepare("
					SELECT *
					FROM p_users WHERE id=$userID
					ORDER BY $orderBy
					LIMIT $limit;");
				$user->execute();
				$user = $user->fetchAll();
				return $user;
			}else{
				//we're looking for a user by name
				$userID = $this->pDB->quote($userID);
				$user = $this->pDB->prepare("
					SELECT *
					FROM p_users WHERE username=$userID
					ORDER BY $orderBy
					LIMIT $limit;");
				$user->execute();
				$user = $user->fetchAll();
				return $user;
			}
		}else{
			$users = $this->pDB->prepare("
				SELECT *
				FROM p_users
				WHERE fname LIKE '%$filter%' or
				lname LIKE '%$filter%' or email LIKE '%$filter%' or
				description LIKE '%$filter%' or username LIKE '%$filter%'
				ORDER BY $orderBy
				LIMIT $limit;");
			$users->execute();
			$users = $users->fetchAll();
			return $users;
		}

	}

	/**
	 * Format an array of group information, use with group_get
	 * @param Array groupArray use with group_get to ensure proper format
	 * @param String formatString string with formatting markers to replace with the group information
	 * %i% = id
	 * %n% = name
	 * %d% = description
	 * %m% = members
	 * @return String
	 **/
	function group_format($groupsArray, $formatString){
		//take in an array of groups, format based on the string
		// %i% = id
		// %n% = name
		// %d% = description
		// %m% = members

		$result = null;

		foreach($groupsArray as $group) {
		    $needles = array(
				'%i%',
				'%n%',
				'%d%',
				'%m%'
		    );
		    $replacements = array(
				$group['id'],
				$group['name'],
				$group['description'],
				$group['members']
		    );
		    $result.=str_replace($needles, $replacements, $formatString);
		}

		return $result;
	}

	/**
	 * Format an array of user information, use with user_get
	 * @param Array usersArray use with user_get to ensure proper format
	 * @param String formatString string with formatting markers to replace with the group information
	 * %i% = id
	 * %u% = username
	 * @param groupFormatString
	 * @return String
	 **/
	function user_format($usersArray, $formatString = '%i%, %u% <br/>', $groupFormatString = '%n%'){
		//take in an array of groups, format based on the string
		// %i% = id
		// %u% = username

		$result = null;

		foreach($usersArray as $userArray) {

			switch($userArray['status']){

				case 'active':
					$color = '#080';
				break;

				case 'disabled':
					$color = '#008';
					$status = '<span style="color: #008;">Disabled</span>';
				break;

				case 'suspended':
					$color = '#800';
					$status = '<span style="color: #800;">Suspended</span>';
				break;

				case 'flagged':
					$color = '#540';
					$status = '<span style="color: #540;">Flagged</span>';
				break;

				default:
						$color = '#444';
					if(ctype_digit($userArray['status'])){
						$color = '#080';
						$userArray['status'] = 'Reserved';
					}else{
						$userArray['status'] = ucfirst($userArray['status']);
					}
			}

		    $needles = array(
				'%i%',
				'%u%',
				'%fn%',
				'%ln%',
				'%e%',
				'%p%',
				'%P%',
				'%d%',
				'%s%',
				'%g%',
				'%G%',
				'%sC%'
		    );
		    $replacements = array(
				$userArray['id'],
				$userArray['username'],
				$userArray['fname'],
				$userArray['lname'],
				$userArray['email'],
				$userArray['personalphone'],
				$userArray['publicphone'],
				$userArray['description'],
				$userArray['status'],
				$userArray['primarygroup'],
				$this->group_format($this->group_get($userArray['primarygroup']), '%n%'),
				$color
		    );

		    $result.=str_replace($needles, $replacements, $formatString);
		}

		return $result;
	}


}

$GLOBALS['UGP'] = new ugp;

?>
