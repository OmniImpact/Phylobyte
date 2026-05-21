<?php
class tinyRegistry{

	private $registry = null;
	private $dbObject;

	function __construct(){
		if(!isset($_SESSION['dbinfo']) || count($_SESSION['dbinfo']) < 2){
			if(is_file('../data/dbconfig.array')){
				$_SESSION['dbinfo'] = unserialize(file_get_contents('../data/dbconfig.array'));
			}else{
				$this->dbObject = null; // Set dbObject to null to indicate failure
				return; // Exit constructor
			}
		}
		try{
			if($_SESSION['dbinfo']['dbt'] == 'MySQL'){
				try{
					$this->dbObject = new PDO('mysql:host='.$_SESSION['dbinfo']['dbh'].';dbname='.$_SESSION['dbinfo']['dbn'], $_SESSION['dbinfo']['dbu'], $_SESSION['dbinfo']['dbp']);
				}catch(PDOException $e){echo $e;}
			}elseif($_SESSION['dbinfo']['dbt'] == 'Sequel Server'){
				try{
					$this->dbObject = new PDO("odbc:Driver={SQL Server};Server={$_SESSION['dbinfo']['dbh']};Database={$_SESSION['dbinfo']['dbn']}; Uid={$_SESSION['dbinfo']['dbu']};Pwd={$_SESSION['dbinfo']['dbp']};");
				}catch(PDOException $e){echo $e;}
			}elseif($_SESSION['dbinfo']['dbt'] == 'SQLite'){
				try{
					$absoluteDataDirPath = realpath(dirname(__FILE__) . '/../data/');
					$this->dbObject = new PDO('sqlite:'.$absoluteDataDirPath.'/'. $_SESSION['dbinfo']['dbn']);
				}catch(PDOException $e){echo $e;}
			}
		}catch(PDOException $e){echo $e;}
	}

	/**
	 * Open the specified registry, set the private identifiying variable if it does not exist
	 * @param string $registry Name of Registry to open
	 * @return boolean
	 **/
	function open($registry){
		if($this->dbObject == null){
			return false;
		}
		$this->registry = $registry;
		try{
			$autoIncrementKeyword = '';
			if (isset($_SESSION['dbinfo']['dbt']) && $_SESSION['dbinfo']['dbt'] == 'MySQL') {
				$autoIncrementKeyword = 'AUTO_INCREMENT';
			}
			// For SQLite, INTEGER PRIMARY KEY implicitly handles auto-increment,
			// so no explicit AUTO_INCREMENT keyword is needed or desired for basic use.

			if($_SESSION['dbinfo']['dbt'] == 'Sequel Server'){
				$this->dbObject->exec("
				IF NOT EXISTS (SELECT * FROM sysobjects WHERE name='__REGISTRY__{$this->registry}')
				CREATE TABLE __REGISTRY__{$this->registry} (
					id INTEGER PRIMARY KEY IDENTITY,
					mykey TEXT,
					value TEXT
				);");
			}else{
			$this->dbObject->exec("CREATE TABLE IF NOT EXISTS __REGISTRY__{$this->registry}(id INTEGER PRIMARY KEY " . $autoIncrementKeyword . ", mykey TEXT, value TEXT);");
			}
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
		if($this->dbObject == null){
			return false;
		}
		$sql = "";
		$dbType = $_SESSION['dbinfo']['dbt'];

		if($dbType == 'Sequel Server'){
			$sql = "SELECT name FROM sys.Tables WHERE name LIKE '__REGISTRY__%';";
		} elseif ($dbType == 'MySQL') {
			$sql = "SHOW TABLES LIKE '__REGISTRY__%';";
		} elseif ($dbType == 'SQLite') {
			$sql = "SELECT name FROM sqlite_master WHERE type='table' AND name LIKE '__REGISTRY__%';";
		} else {
			// Default or error case, perhaps return empty array or throw exception
			return [];
		}

		$list = $this->dbObject->prepare($sql);
		$list->execute();
		$currentResults = $list->fetchAll(PDO::FETCH_COLUMN); // Fetch only the column values
		$results = []; // Initialize as an empty array

		if (!empty($currentResults)) { // Check if there are any results
			foreach($currentResults as $tableName) { // $tableName will directly be the table name string
				// The substr(..., 12) assumes the prefix is exactly '__REGISTRY__'.
				// We should ensure $tableName is a string before substr.
				if (is_string($tableName) && strlen($tableName) >= 12) {
					$results[]['name'] = substr($tableName, 12);
				}
			}
		}
		return $results;
	}

	/**
	 * Drop a registry by name
	 * @param string $registry Name of Registry to Drop
	 * @return boolean
	 **/
	function registrydrop($registryname){
		if($this->dbObject == null){
			return false;
		}
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
		if($this->dbObject == null){
			return false;
		}
		$key = $this->dbObject->quote($key);
		$value = $this->dbObject->quote($value);
		if($this->registry == null) return false;

		$statement = $this->dbObject->prepare("SELECT * FROM __REGISTRY__{$this->registry} WHERE mykey=$key;");
		$statement->execute();
		$existingRecord = $statement->fetchAll();

		// Check if a record was found
		$foundRecord = null;
		if (!empty($existingRecord)) {
			$foundRecord = $existingRecord[0];
		}

		if($overwrite === false){ //check if there is already a value
			if($foundRecord !== null){ // If a record was found, do not overwrite
				return false;
			}else{ // No record found, insert new
				$this->dbObject->exec("INSERT INTO __REGISTRY__{$this->registry} (mykey, value) VALUES ($key,$value);");
				return true;
			}
		}else{ // Overwrite is true, or no record exists
			if($foundRecord !== null){ // Record found, update it
				$this->dbObject->exec("UPDATE __REGISTRY__{$this->registry} SET value=$value WHERE mykey=$key;");
				return true;
			}else{ // No record found, insert new
				$this->dbObject->exec("INSERT INTO __REGISTRY__{$this->registry} (mykey, value) VALUES ($key, $value);");
				return true;
			}
		}
	}

	function pull($result = true, $id=null, $filter='%', $order='DESC', $limit='500'){
		if($this->dbObject == null){
			return false;
		}
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
				if($statement->execute()){
					return true;
				}else{
					return false;
				}
			}
		}else{
			$returnString = null;
			foreach($results as $resultItem){
				$current = str_replace('%i%', $resultItem['id'], $result);
				$current = str_replace('%k%', $resultItem['mykey'], $current);
				$current = str_replace('%v%', $resultItem['value'], $current);
				$returnString.= $current;
			}
			return $returnString ?? ''; // Ensure a string is always returned
		}
	}

	function pullquery($result = true, $query = null){
		if($this->dbObject == null){
			return false;
		}
		if($this->registry == null) return false;
		//if id is null, use filter otherwise use provided id
		//result true returns array, false, deletes items that match, string returns template
		$pull = $this->dbObject->prepare($query);
		$pull->execute();

		$currentResults = $pull->fetchAll();
		$results = []; // Initialize $results as an empty array

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
			return $returnString ?? ''; // Ensure a string is always returned
		}
	}


}
?>