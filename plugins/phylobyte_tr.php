<?php
class tinyRegistry{

	private $registry = null;
	private $dbObject;
	
	function __construct(){
		try{
			include('../data/database.vars.php');
			$this->dbObject = new PDO('mysql:host=localhost;dbname='.$DBMASTERDB, $DBUSER, $DBPASSWORD);
		}catch(PDOException $e){
			echo("Caught Exception: $e");
		}
	}

	/**
	 * Open the specified registry, set the private identifiying variable if it does not exist
	 * @param string $registry Name of Registry to open
	 * @return boolean
	 **/
	function open($registry){
		$this->registry = $registry;
		try{
			$this->dbObject->exec("CREATE TABLE IF NOT EXISTS __REGISTRY__{$this->registry}(id INTEGER PRIMARY KEY AUTO_INCREMENT, mykey TEXT, value TEXT);");
			return true;
		}catch(PDOException $e){
			echo("Caught Exception: $e");
			return false;
		}
	}

	/**
	 * Return an array of existing registries
	 * @return array
	 **/
	function registrylist(){
		$list = $this->dbObject->prepare("SHOW TABLES LIKE '__REGISTRY__%';"); //" WHERE name LIKE '__REGISTRY__%' ORDER BY name;");
		$list->execute();
		$currentResults = $list->fetchAll();
		$results = null;
		foreach($currentResults as $currentResult) {
			$results[]['name'] = substr($currentResult[0], 12);
		}
		return $results;
	}

	/**
	 * Drop a registry by name
	 * @param string $registry Name of Registry to Drop
	 * @return boolean
	 **/
	function registrydrop($registryname){
		$statement = $this->dbObject->exec("DROP TABLE __REGISTRY__$registryname;");
		return true;
	}

	/**
	 * Push a value into the open registry
	 * @param string $key Item Key to open or update
	 * @param string $value Item target value
	 * @param boolean $overwrite=true whether or not to overwrite value if it already exists
	 * @return boolean
	 **/
	function push($key, $value, $overwrite = true){
		$key = $this->dbObject->quote($key);
		$value = $this->dbObject->quote($value);
		if($this->registry == null) return false;
			$statement = $this->dbObject->prepare("SELECT * FROM __REGISTRY__{$this->registry} WHERE mykey=$key;");
			$statement->execute();
			$resultsArray = $statement->fetchAll();
			$resultsArray = $resultsArray[0];
		if($overwrite === false){ //check if there is already a value
			if($resultsArray['id'] != null){
				return false;
			}else{
				$this->dbObject->exec("INSERT INTO __REGISTRY__{$this->registry} (mykey, value) VALUES ($key,$value);");
				return true;
			}
		}else{
			if($resultsArray['id'] != null){
				$this->dbObject->exec("UPDATE __REGISTRY__{$this->registry} SET value=$value WHERE mykey=$key;");
				return true;
			}else{
				$this->dbObject->exec("INSERT INTO __REGISTRY__{$this->registry} (mykey, value) VALUES ($key, $value);");
				return true;
			} 
		}
	}

	function pull($result = true, $id=null, $filter='%', $order='DESC', $limit='500'){
		if($this->registry == null) return false;
		//if id is null, use filter otherwise use provided id
		//result true returns array, false, deletes items that match, string returns template		
		if(ctype_digit($id)){
			$pull = $this->dbObject->prepare("SELECT * FROM __REGISTRY__{$this->registry} WHERE id='$id' LIMIT $limit;");
		}else{
			$pull = $this->dbObject->prepare("SELECT * FROM __REGISTRY__{$this->registry} WHERE mykey LIKE '$filter' UNION SELECT * FROM __REGISTRY__{$this->registry} WHERE value LIKE '$filter' ORDER BY mykey,value $order LIMIT $limit;");
		}
		$pull->execute();
		$results = $pull->fetchAll();
		if($result === true){
			return $results;
		}elseif($result === false){
			if(ctype_digit($id)){
				$statement = $this->dbObject->prepare("DELETE FROM __REGISTRY__{$this->registry} WHERE id=$id;");
				$statement->execute(); return true;
			}
		}else{
			$returnString = null;
			foreach($results as $resultItem){
				$current = str_replace('%i%', $resultItem['id'], $result);
				$current = str_replace('%k%', $resultItem['mykey'], $current);
				$current = str_replace('%v%', $resultItem['value'], $current);
				$returnString.= $current;
			}
			return $returnString;
		}
	}

	function pullquery($result = true, $query = null){
		if($this->registry == null) return false;
		//if id is null, use filter otherwise use provided id
		//result true returns array, false, deletes items that match, string returns template
		$pull = $this->dbObject->prepare($query);
		$pull->execute();

		$currentResults = $pull->fetchAll();

		foreach($currentResults as $currentResult) {
			$results[] = $currentResult;
		}
		if($result === true){
			return $results;
		}else{
			$returnString = null;
			if(is_array($results)){
				foreach($results as $resultItem){
					$current = str_replace('%i%', $resultItem['id'], $result);
					$current = str_replace('%k%', $resultItem['mykey'], $current);
					$current = str_replace('%v%', $resultItem['value'], $current);
					$returnString.= $current;
				}
			}
			return $returnString;
		}
	}


}
?>