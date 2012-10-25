<?php

class equatec{

	static $pDB;

	function __construct(){
		$this->pDB = $GLOBALS['PHYLOBYTEDB'];

		$this->pDB->exec("
				CREATE TABLE IF NOT EXISTS equatec_projects(
					id INTEGER PRIMARY KEY AUTO_INCREMENT,
					name TEXT,
					description TEXT
				);");
		$this->pDB->exec("
				CREATE TABLE IF NOT EXISTS equatec_features(
					id INTEGER PRIMARY KEY AUTO_INCREMENT,
					project_id TEXT,
					name TEXT,
					description TEXT
				);");
		$this->pDB->exec("
				CREATE TABLE IF NOT EXISTS equatec_cases(
					id INTEGER PRIMARY KEY AUTO_INCREMENT,
					feature_id TEXT,
					name TEXT,
					description TEXT,
					type TEXT
				);");

		$this->pDB->exec("
				CREATE TABLE IF NOT EXISTS equatec_results(
					id INTEGER PRIMARY KEY AUTO_INCREMENT,
					feature_id TEXT,
					timestamp TEXT,
					data TEXT,
					result TEXT,
					user TEXT
				);");
	}

}

$GLOBALS['EQUATEC'] = new equatec;

?>