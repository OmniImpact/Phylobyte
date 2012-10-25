<?php
//process

if($_POST['p_submit'] == 'Save Configuration'){

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
			if(file_put_contents('../data/dbconfig.array', serialize(Array('dbt' => $dbt, 'dbh' => $dbh, 'dbn' => $dbn, 'dbu' => $dbu, 'dbp' => $dbp)))){
				$dbSetup = true;
				$this->directMessages = false;
				$this->messageAddNotification('Successfully connected to database and wrote configuration file.');
			}else{
				$this->messageArea ='#e.Please check that you have write access to the ../data directory.##.';
			}
		}catch(PDOException $e){
			$this->messageArea.='#e.Failed to open database: '.$e.'##.';
		}
	}elseif($dbt == 'PostgreSQL'){
		try{
			$testDB = new PDO("pgsql:dbname=$dbn;host=$dbh", $dbu, $dbp);
			if(file_put_contents('../data/dbconfig.array', serialize(Array('dbt' => $dbt, 'dbh' => $dbh, 'dbn' => $dbn, 'dbu' => $dbu, 'dbp' => $dbp)))){
				$dbSetup = true;
			}else{
				$this->pageArea.='<h2>Please check that you have write access to the ../data directory.</h2>';
			}
		}catch(PDOException $e){
			$this->pageArea.='<p>Failed to open database: '.$e.'</p>';
		}
	}elseif($dbt == 'Sequel Server'){
	    try{
			$sysinfo = posix_uname();
			$sequelServerDriver = ($sysinfo['sysname'] == 'Linux') ? 'FreeTDS' : '{SQL Server}' ;
			$testDB =new PDO("odbc:Driver=$sequelServerDriver;Server=$dbh;Database=$dbn; Uid=$dbu;Pwd=$dbp;");
			if(file_put_contents('../data/dbconfig.array', serialize(Array('dbt' => $dbt, 'dbh' => $dbh, 'dbn' => $dbn, 'dbu' => $dbu, 'dbp' => $dbp)))){
				$dbSetup = true;
			}else{
				$this->pageArea.='<h2>Please check that you have write access to the ../data directory.</h2>';
			}
		}catch(PDOException $e){
			$this->pageArea.='<p>Failed to open database: '.$e.'</p>';
		}
	}else{
			$this->pageArea.='<p>Could not understand database type.</p>';
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

$_POST['p_dbt'] = ($_POST['p_dbt'] != null) ? $_POST['p_dbt'] : 'MySQL'; 

$this->pageArea.= '
<div style="display: block; text-align: center;">
	<img src="gfx/logo_color_md.png" /><br/>
	<h2>Welcome! Before we begin, you need to configure a database.</h2>
	<p>Once the database is configured, you will need to activate the default administrator account. If you are not directed there automatically, you can enter "admin" in the "User Name" field of the log in form, and setup will continue.</p>
</div>

<div class="floatfix">&nbsp;</div>

<fieldset>
	<legend>Database Configuration</legend>
	<form method="post" action="?'.$_SERVER['QUERY_STRING'].'">

	<p>Phylobyte was unable to detect a database configuration file.</p>

	<label for="p_dbt">Database Type</label>
	<select name="p_dbt">
		<option value="'.$_POST['p_dbt'].'">Keep '.$_POST['p_dbt'].'</option>
		<option value="MySQL">MySQL</option>
		<!--
		<option value="PostgreSQL">PostgreSQL</option>
		<option value="Sequel Server">Sequel Server</option>
		-->
	</select><br/>
	<label for="p_dbh">Database Host</label><input type="text" name="p_dbh" value="'.$_POST['p_dbh'].'"/><br/>
	<label for="p_dbn">Database Name</label><input type="text" name="p_dbn" value="'.$_POST['p_dbn'].'"/><br/>
	<label for="p_dbu">User Name</label><input type="text" name="p_dbu" value="'.$_POST['p_dbu'].'"/><br/>
	<label for="p_dbp">Password</label><input type="password" name="p_dbp" value="'.$_POST['p_dbp'].'"/><br/>

		<input type="submit" name="p_submit" value="Save Configuration" style="width: 14em; margin-left: 70%;" />
		<div class="ff">&nbsp;</div>

	</form>

</fieldset>
';

?>
