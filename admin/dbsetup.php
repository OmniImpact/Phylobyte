<?php
//process

if(isset($_POST['p_submit']) && $_POST['p_submit'] == 'Save Configuration'){

	$this->directMessages = true;

	$dbt = stripslashes($_POST['p_dbt']);
	$dbh = stripslashes($_POST['p_dbh']);
	$dbn = stripslashes($_POST['p_dbn']);
	$dbu = stripslashes($_POST['p_dbu']);
	$dbp = stripslashes($_POST['p_dbp']);
	//generate db connection string

	$dbSetup = false;
	if($dbt == 'MySQL'){
		try{
			$testDB = new PDO('mysql:host='.$dbh.';dbname='.$dbn, $dbu, $dbp);
			$testDB->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION); // Set error mode for consistency
			if(file_put_contents('../data/dbconfig.array', serialize(Array('dbt' => $dbt, 'dbh' => $dbh, 'dbn' => $dbn, 'dbu' => $dbu, 'dbp' => $dbp)))){
				$dbSetup = true;
				$this->directMessages = false;
				self::messageAddNotification('Successfully connected to database and wrote configuration file.');
			}else{
				self::messageAddError('Please check that you have write access to the ../data directory.');
			}
		}catch(PDOException $e){
			self::messageAddError('Failed to open database: '.$e->getMessage());
		}
	}elseif($dbt == 'PostgreSQL'){
		try{
			$testDB = new PDO("pgsql:dbname=$dbn;host=$dbh", $dbu, $dbp);
			$testDB->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION); // Set error mode for consistency
			if(file_put_contents('../data/dbconfig.array', serialize(Array('dbt' => $dbt, 'dbh' => $dbh, 'dbn' => $dbn, 'dbu' => $dbu, 'dbp' => $dbp)))){
				$dbSetup = true;
				$this->directMessages = false;
				self::messageAddNotification('Successfully connected to database and wrote configuration file.');
			}else{
				self::messageAddError('Please check that you have write access to the ../data directory.');
			}
		}catch(PDOException $e){
			self::messageAddError('Failed to open database: '.$e->getMessage());
		}
	}elseif($dbt == 'Sequel Server'){
	    try{
			$sysinfo = posix_uname();
			$sequelServerDriver = ($sysinfo['sysname'] == 'Linux') ? 'FreeTDS' : '{SQL Server}' ;
			$testDB =new PDO("odbc:Driver=$sequelServerDriver;Server=$dbh;Database=$dbn; Uid=$dbu;Pwd=$dbp;");
			$testDB->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION); // Set error mode for consistency
			if(file_put_contents('../data/dbconfig.array', serialize(Array('dbt' => $dbt, 'dbh' => $dbh, 'dbn' => $dbn, 'dbu' => $dbu, 'dbp' => $dbp)))){
				$dbSetup = true;
				$this->directMessages = false;
				self::messageAddNotification('Successfully connected to database and wrote configuration file.');
			}else{
				self::messageAddError('Please check that you have write access to the ../data directory.');
			}
		}catch(PDOException $e){
			self::messageAddError('Failed to open database: '.$e->getMessage());
		}
	}elseif($dbt == 'SQLite'){
		$dataDirPath = '../data/'; // Relative path from dbsetup.php
		$absoluteDataDirPath = realpath(dirname(__FILE__) . '/../data/'); // Absolute path for error message

		if (!is_writable($absoluteDataDirPath)) {
			self::messageAddError('Failed to open SQLite database: The directory ' . $absoluteDataDirPath . ' is not writable. Please ensure the web server has write permissions to this directory.');
		} else {
			try{
				// For SQLite, $dbn is the path to the database file
				$testDB = new PDO('sqlite:'.$absoluteDataDirPath.'/'.$dbn);
				$testDB->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION); // Set error mode for consistency
				if(file_put_contents($absoluteDataDirPath.'/dbconfig.array', serialize(Array('dbt' => $dbt, 'dbn' => $dbn)))){
					$dbSetup = true;
					$this->directMessages = false;
					self::messageAddNotification('Successfully connected to SQLite database and wrote configuration file.');
				}else{
					self::messageAddError('Please check that you have write access to the ' . $absoluteDataDirPath . ' directory.');
				}
			}catch(PDOException $e){
				self::messageAddError('Failed to open SQLite database: '.$e->getMessage());
			}
		}
	}else{
			self::messageAddError('Could not understand database type.');
	}

	if($dbSetup == true){
		$_POST['p_username'] = 'admin';
	}

}

//build
$this->pageTitle.=' | Database Setup';

$this->docArea = '
<h3>Thank you for choosing <br/> Phylobyte CMS!</h3>
<p>
Please tell Phylobyte how to connect to a database. Phylobyte needs a database to be able to store the information that allows you to grow and manage your website.
</p>';

$_POST['p_dbt'] = (isset($_POST['p_dbt']) && $_POST['p_dbt'] != null) ? $_POST['p_dbt'] : 'SQLite';

$sqliteSelected = ($_POST['p_dbt'] == 'SQLite') ? 'selected' : '';
$mysqlSelected = ($_POST['p_dbt'] == 'MySQL') ? 'selected' : '';

$dbhValue = isset($_POST['p_dbh']) ? $_POST['p_dbh'] : '';
$dbnValue = isset($_POST['p_dbn']) ? $_POST['p_dbn'] : 'phylobyte.sqlite';
$dbuValue = isset($_POST['p_dbu']) ? $_POST['p_dbu'] : '';
$dbpValue = isset($_POST['p_dbp']) ? $_POST['p_dbp'] : '';

$this->pageArea.= <<<EOT
<div style="display: block; text-align: center;">
	<img src="gfx/logo_color_md.png" /><br/>
	<h2>Welcome! Before we begin, you need to configure a database.</h2>
	<p>Once the database is configured, you will need to activate the default administrator account. If you are not directed there automatically, you can enter "admin" in the "User Name" field of the log in form, and setup will continue.</p>
</div>

<div class="floatfix">&nbsp;</div>

<fieldset>
	<legend>Database Configuration</legend>
	<form method="post" action="?{$_SERVER['QUERY_STRING']}">

	<p>Phylobyte was unable to detect a database configuration file.</p>

	<label for="p_dbt">Database Type</label>
	<select name="p_dbt" id="p_dbt_select">
		<option value="SQLite" {$sqliteSelected}>SQLite</option>
		<option value="MySQL" {$mysqlSelected}>MySQL</option>
		<!--
		<option value="PostgreSQL">PostgreSQL</option>
		<option value="Sequel Server">Sequel Server</option>
		-->
	</select><br/>
	<div id="db_host_row">
		<label for="p_dbh">Database Host</label><input type="text" name="p_dbh" id="p_dbh_input" value="{$dbhValue}"/><br/>
	</div>
	<label for="p_dbn">Database Name/Path</label><input type="text" name="p_dbn" id="p_dbn_input" value="{$dbnValue}"/><br/>
	<div id="db_user_row">
		<label for="p_dbu">User Name</label><input type="text" name="p_dbu" id="p_dbu_input" value="{$dbuValue}"/><br/>
	</div>
	<div id="db_pass_row">
		<label for="p_dbp">Password</label><input type="password" name="p_dbp" id="p_dbp_input" value="{$dbpValue}"/><br/>
	</div>

		<input type="submit" name="p_submit" value="Save Configuration" style="width: 14em; margin-left: 70%;" />
		<div class="ff">&nbsp;</div>

	</form>

</fieldset>

<script type="text/javascript">
	function toggleDbFields() {
		var dbType = document.getElementById("p_dbt_select").value;
		var dbHostRow = document.getElementById("db_host_row");
		var dbUserRow = document.getElementById("db_user_row");
		var dbPassRow = document.getElementById("db_pass_row"); // Corrected selector
		var dbNameLabel = document.querySelector("label[for=\\'p_dbn\\']"); // Escaped single quotes
		var dbNameInput = document.getElementById("p_dbn_input");

		if (dbType === "SQLite") {
			dbHostRow.style.display = "none";
			dbUserRow.style.display = "none";
			dbPassRow.style.display = "none";
			dbNameLabel.textContent = "Database Path";
			if (dbNameInput.value === "" || dbNameInput.value === "phylobyte") { // Change default if it's the old MySQL default
				dbNameInput.value = "phylobyte.sqlite";
			}
		} else {
			dbHostRow.style.display = "block";
			dbUserRow.style.display = "block";
			dbPassRow.style.display = "block";
			dbNameLabel.textContent = "Database Name";
			if (dbNameInput.value === "phylobyte.sqlite") { // Change default back if it's the SQLite default
				dbNameInput.value = "phylobyte";
			}
		}
	}

	document.addEventListener("DOMContentLoaded", function() {
		document.getElementById("p_dbt_select").addEventListener("change", toggleDbFields);
		toggleDbFields(); // Call on page load to set initial state
	});
</script>
EOT;