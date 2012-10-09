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

		if(!ctype_alnum($groupArray['name'])){
			phylobyte::messageAddAlert('Group names must be alphanumeric.');
			return false;
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
			if($userArray['username'] == null || strlen($userArray['password']) < 4){
				phylobyte::messageAddError('You need to provide at least a user name and password to create a new user.');
				return false;
			}
		}

		//now, regardless of whether a user id is supplied, we need to make sure
		//the user name is available
		$userNameArray = $this->user_get($userArray['username']);
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

		//write changes to database, keepind in mind:
		//if the password is null, don't touch it
		//if the user ID is null, don't privide it
		//id, description, passwordhash are optional

		if($userArray['id'] != null){
			$optionalKeys.= ' id,';
			$optionalVals.= $this->pDB->quote($userArray['id']).', ';
		}
		if(trim($userArray['password']) != ''){
			$optionalKeys.= ' passwordhash,';
			$quotedPassword = $this->pDB->quote(sha1($userArray['password']));
			$optionalVals.= $quotedPassword.', ';
			$optionalUpdate = "passwordhash=$quotedPassword,";
		}

		$username = $this->pDB->quote($userArray['username']);
		$status = $this->pDB->quote($userArray['status']);
		$email = $this->pDB->quote($userArray['email']);
		$name = $this->pDB->quote($userArray['name']);

		$query = "
			INSERT INTO p_users ($optionalKeys username, status, email, name)
			VALUES ($optionalVals $username, $status, $email, $name)
			ON DUPLICATE KEY UPDATE
			$optionalUpdate name=$name, email=$email, status=$status, username=$username;
			";
		$query = $this->pDB->prepare($query);
		if($query->execute()){
			phylobyte::messageAddNotification('Successfully updated user.');
			return true;
		}else{
			phylobyte::messageAddError('Error updating user.');
			return false;
		}
	}

	function user_getattrs($uid, $gid){

		//return false if the user is NOT a member of that group
		$userid = $this->pDB->quote($uid);
		$groupid = $this->pDB->quote($gid);

		$checkResults = $this->pDB->prepare("
			SELECT id
			FROM p_memberships WHERE userid=$userid AND groupid=$groupid;");
		$checkResults->execute();
		$checkResults = $checkResults->fetchAll();

		if(count($checkResults) > 0){
			//great! the user is a member of the group, so let's get the attrs
			$getAttrs = $this->pDB->prepare("
			SELECT DISTINCT p_gattributes.*,
			(SELECT value FROM p_uattributes WHERE uid=$userid AND p_uattributes.aid = p_gattributes.id) AS value
			FROM p_gattributes, p_uattributes WHERE p_gattributes.gid=$groupid
			");
			$getAttrs->execute();
			$getAttrs = $getAttrs->fetchAll();
			return $getAttrs;
		}else{
			return false;
		}

	}

	function user_putattr($uid, $aid, $value){
	
		$userid = $this->pDB->quote($uid);
		$attrid = $this->pDB->quote($aid);

		if($value == null || $value == ''){
			$this->pDB->exec("
			DELETE FROM p_uattributes
			WHERE uid=$userid AND aid=$attrid;
			");
			phylobyte::messageAddNotification('Cleared attribute from database.');
			return true;
		}

		$value = $this->pDB->quote($value);

		//value is not null, so insert or update

		$checkResults = $this->pDB->prepare("
			SELECT id
			FROM p_uattributes
			WHERE uid=$userid AND aid=$attrid;
		");
		$checkResults->execute();
		$checkResults = $checkResults->fetchAll();

		if(count($checkResults) > 0){
			//update
			if($this->pDB->exec("
			UPDATE p_uattributes
			SET value=$value WHERE
			uid=$userid AND aid=$attrid;
			")){
			phylobyte::messageAddNotification('Successfully updated attribute.');
			}else{
			phylobyte::messageAddError('There was a problem updating the attribute.');
			}
			return true;
		}else {
		    //insert
		    if($this->pDB->exec("
		    INSERT INTO p_uattributes (uid, aid, value)
		    VALUES ($userid, $attrid, $value);
		    ")){
			phylobyte::messageAddNotification('Successfully added attribute.');
		    }else {
		    phylobyte::messageAddError('There was a problem adding the attribute.');
		    }
		    return true;
		}
	}

	function user_formatattr($attributeArray, $attributeTemplate){

	}

	function membership_add($uid, $gid){

		$userid = $this->pDB->quote($uid);
		$groupid = $this->pDB->quote($gid);

		$checkResults = $this->pDB->prepare("
			SELECT id
			FROM p_memberships WHERE userid=$userid AND groupid=$groupid;");
		$checkResults->execute();
		$checkResults = $checkResults->fetchAll();

		if(count($checkResults) > 0){
			phylobyte::messageAddError('The user is already a member of that group.');
		}else{
			if(
			$this->pDB->exec("
				INSERT INTO p_memberships (userid, groupid) VALUES ($userid, $groupid);
			")
			){
			phylobyte::messageAddNotification('Successfully added or updated membership.');

			}else{
			phylobyte::messageAddError('There was a problem adding the user to a group');
			}
		}


	}

	/**
	 * Delete a membership.
	 * @param int membershipId
	 * @return boolean
	 * TODO make sure to delete any attributes as well
	 **/
	function membership_remove($mid){
			$this->pDB->quote($mid);
			if($this->pDB->exec("
				DELETE FROM p_memberships
				WHERE id=$mid;")
			){
			phylobyte::messageAddNotification('Successfully removed membership from group.');
			return true;
			}else{
			phylobyte::messageAddError('There was a problem deleting that membership.');
			return false;
			}
	}
	
	/**
	 * Delete a group.
	 * @param int groupID
	 * @return boolean
	 * TODO add second perameter for deleting attribute
	 **/
	function group_delete($groupID){

		$delete = $this->group_deleteable($groupID);
		if($delete === true){
			$this->pDB->quote($groupID);
			if($this->pDB->exec("
				DELETE FROM p_groups
				WHERE id=$groupID;") &&
				$this->pDB->exec("
				DELETE FROM p_uattributes
				WHERE aid=ANY(
					SELECT id FROM p_gattributes
					WHERE gid=$groupID
				);") &&
				$this->pDB->exec("
				DELETE FROM p_gattributes
				WHERE gid=$groupID;")
			){ phylobyte::messageAddNotification('Successfully deleted group.'); }
		}else{
			phylobyte::messageAddError('The group '.$groupID.' could not be deleted: '.$delete);
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
			$this->pDB->exec("DELETE FROM p_memberships WHERE userid={$_POST['u_uid']};");
			$this->pDB->exec("DELETE FROM p_uattributes WHERE uid={$_POST['u_uid']};");
			phylobyte::messageAddNotification('Successfully deleted user.');
			return true;
		}
		return false;
	}

	function group_deleteable($groupID){
		//return TRUE if safe to delete
		//otherwise, return the error

		$group = $this->group_get($groupID);
		if($group[0]['id'] == 1){
			return 'You can not delete the Admin group.';
		}elseif($group[0]['members'] != 0){
			return 'You can not delete a group that still has members.';
		}elseif($group[0]['id'] == ''){
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
	function group_get($groupID, $groupsFilter = '', $user = false, $reverse = false){

		if(ctype_digit($user)){
			//this is a little different; we need to get only the groups a user is a member of
			$userid = $this->pDB->quote($user);
			if($reverse == false){
				$groups = $this->pDB->prepare("
					SELECT p_memberships.id AS mid, p_groups.*, (
							SELECT COUNT(*)
							FROM p_memberships
							WHERE groupid=p_groups.id
							) AS members
					FROM p_memberships
					JOIN p_groups ON p_memberships.groupid = p_groups.id
					WHERE userid=$userid;
				");
			}else{
				$groups = $this->pDB->prepare("
					SELECT DISTINCT * FROM p_groups WHERE NOT EXISTS
					(SELECT * FROM p_memberships WHERE p_memberships.groupid = p_groups.id
					AND p_memberships.userid = $userid)
				");
			}

			$groups->execute();
			$groups = $groups->fetchAll();
			return $groups;	
		}else{
			//if gruoupID, return array, otherwise return multiple array
			if($groupID != null){
				$group = $this->pDB->prepare("
					SELECT *, (
						SELECT COUNT(*)
						FROM p_memberships
						WHERE groupid=p_groups.id
						) as members
					FROM p_groups WHERE id=$groupID ORDER BY name;");
				$group->execute();
				$group = $group->fetchAll();
				return $group;
			}else{
				$groups = $this->pDB->prepare("
					SELECT *, (
						SELECT COUNT(*)
						FROM p_memberships
						WHERE groupid = p_groups.id
						) as members
					FROM p_groups
					WHERE name LIKE '%$groupsFilter%' ORDER BY name;");
				$groups->execute();
				$groups = $groups->fetchAll();
				return $groups;
			}
		}

	}

	/**
	 * Retrieve a user and its information from the database.
	 * @param String userID otherwise pass null and a filter to search users
	 * @return Array
	 * TODO include the user's attributeArray, key is attribute group, value is attributeArray
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
				WHERE username LIKE '%$filter%' OR
				email LIKE '%$filter%' OR name LIKE '%$filter%'
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
	 * %mi% = mid
	 * @return String
	 * TODO add format string for groups attributes
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
				'%m%',
				'%mi%'
		    );
		    $replacements = array(
				$group['id'],
				$group['name'],
				$group['description'],
				$group['members'],
				$group['mid']
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
	 * TODO since group_format now accepts attribute format, allow it here too
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
				'%n%',
				'%e%',
				'%s%',
				'%sC%'
		    );
		    $replacements = array(
				$userArray['id'],
				$userArray['username'],
				$userArray['name'],
				$userArray['email'],
				$userArray['status'],
				$color
		    );

		    $result.=str_replace($needles, $replacements, $formatString);
		}

		return $result;
	}

	function group_attributeAdd($gid, $attribute, $default){
		if(!ctype_alnum($attribute)){
			phylobyte::messageAddAlert('Attribute names must be alphanumeric.');
			return false;
		}

		$attribute = $this->pDB->quote($attribute);
		$gid = $this->pDB->quote($gid);
		//check if the attribute exists
		$group = $this->pDB->prepare("
			SELECT id
			FROM p_gattributes WHERE gid=$gid AND attribute=$attribute;");
		$group->execute();
		$group = $group->fetchAll();

		if(count($group) >= 1){
			phylobyte::messageAddError('Failed to update group.');
			phylobyte::messageAddAlert('An attribute of that name already exists within this group.');
			return false;
		}

		$default = $this->pDB->quote($default);
		$query = $this->pDB->prepare("
			INSERT INTO p_gattributes (gid, attribute, defaultvalue)
			VALUES ($gid, $attribute, $default); ");
		if($query->execute()){
			phylobyte::messageAddNotification('Successfully added attribute.');
			return true;
		}else{
			phylobyte::messageAddError('Error updating group.');
			return false;
		}
	}

	/**
	 * Retrieve an attribute and its information from the database.
	 * @param gid=String/Integer groupID, null returns all
	 * @param filter=null/String filter attributes by name and default value (union), if true, return by attribute ID, false, delete
	 * @return Array
	 **/
	function group_attributesGet($gid, $filter = ''){

		if($filter === false){
			//if this is the case, $gid is actually the attribute id
			$id = $this->pDB->quote($gid);
			$query = $this->pDB->prepare("
			DELETE FROM p_gattributes WHERE id=$id;
			");
			$query2 = $this->pDB->prepare("
			DELETE FROM p_uattributes WHERE aid=$id;
			");
			if($query->execute() && $query2->execute()){
				phylobyte::messageAddNotification('Successfully deleted attribute.');
				return true;
			}else{
				phylobyte::messageAddError('Error deleting attribute.');
				return false;
			}
		}else{
			$gid = $this->pDB->quote($gid);
			$filter = $this->pDB->quote('%'.$filter.'%');
			$attributes = $this->pDB->prepare("
				SELECT *
				FROM p_gattributes WHERE gid=$gid AND attribute LIKE $filter OR gid=$gid AND defaultvalue LIKE $filter;");
			$attributes->execute();
			$attributes = $attributes->fetchAll();

			return $attributes;
		}
		
	}

	/**
	 * Format an array of attribute information, use with group_attributesGet()
	 * @param attributeArray=Array attributesArray use with group_attributesGet() to ensure proper format
	 * @param formatTemplage=String string with formatting markers to replace with the attribute information
	 * %i% = id
	 * %g% = groupId
	 * %G% = groupName (not yet implemented)
	 * %a% = attribute
	 * %d% = default
	 * @return String
	 **/
	function group_attributesFormat($attributeArray, $template){
	
		$result = null;

		foreach($attributeArray as $attribute) {
		    $needles = array(
				'%i%',
				'%g%',
				'%a%',
				'%d%'
		    );
		    $replacements = array(
				$attribute['id'],
				$attribute['gid'],
				$attribute['attribute'],
				$attribute['defaultvalue']
		    );
		    $result.=str_replace($needles, $replacements, $template);
		}

		return $result;

	}

}

$GLOBALS['UGP'] = new ugp;

?>
