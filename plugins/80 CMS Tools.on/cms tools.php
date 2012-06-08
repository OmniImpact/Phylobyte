<?php

class ugp{

	static $pDB;

	function __construct(){
		$this->pDB = $GLOBALS['PHYLOBYTEDB'];
	}

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


	function  user_put($userArray){
		//must have a unique user name
		//check valid email
		//generate password if requested
		//dont change group if last admin or self
		
		return  true;
	}

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

	function user_delete($userID){
		//check deleteable
		
		return true;
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
		//can't delete last admin or self
		return true;
	}

	function group_get($groupID, $groupsFilter = ''){
		//if gruoupID, return array, otherwise return multiple array
		if($groupID != null){
			$group = $this->pDB->prepare("
				SELECT *, (
					SELECT COUNT(*)
					FROM p_users
					WHERE primarygroup=p_groups.id
					) as members
				FROM p_groups WHERE id=$groupID;");
			$group->execute();
			$group = $group->fetch();
			return $group;
		}else{
			$groups = $this->pDB->prepare("
				SELECT *, (
					SELECT COUNT(*)
					FROM p_users
					WHERE primarygroup=p_groups.id
					) as members
				FROM p_groups
				WHERE name LIKE '%$groupsFilter%';");
			$groups->execute();
			$groups = $groups->fetchAll();
			return $groups;
		}
	}

	function user_get($userID, $filter = ''){
		//return array or multiple arrays
		return true;
	}

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

	function user_format($usersArray, $formatString, $groupFormatString = '%n%'){
		//format the user array
		return true;
	}


}

$GLOBALS['UGP'] = new ugp;

?> 
