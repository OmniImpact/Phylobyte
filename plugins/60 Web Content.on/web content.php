<?php

class pcontent{

	static $pDB;

	/**
	 * Constructor for phylobyte Users and Groups class.
	 **/
	function __construct(){
		$this->pDB = $GLOBALS['PHYLOBYTEDB'];
	}

	
}

$GLOBALS['PCONTENT'] = new pcontent;

?>
