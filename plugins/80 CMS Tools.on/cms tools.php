<?php

class ugp{

 function __construct(){
		//$PHYLOBYTE = new phylobyte;
		phylobyte::messageAddDebug('Constructed UGP');
	}

	function group_put($groupArray, $overwrite = false){
		//take in a group array, write it to the database
		//if the group exists, by default, fail
		//if overwrite is true, update the group
	}

	function group_isLastAdmin($groupID){
		//return TRUE if last Admin group

	}

	function group_get($groupID, $delete = false){
		//if delete is true, delete the group,
		//otherwise, simply read the group, and return an array
		if($delete === false){
				//try to delete group
				//delete if not the last admin group else, fail
		}
	}

	function groups_get($groupID, $groupsFilter){
		//if gruoupID, returh array, otherwise return multiple array
	}

	function group_format($groupID, $formatString){
		//take in a string to format based on 
	}

}

$GLOBALS['UGP'] = new ugp;

// function genRandomString($characters = '0123456789abcdefghijklmnopqrstuvwxyz', $length = 10) {
// 		$string = '';
// 		for ($p = 0; $p < $length; $p++) {
// 			$string .= $characters[mt_rand(0, strlen($characters))];
// 		}
// 		return $string;
// 	}
?> 
