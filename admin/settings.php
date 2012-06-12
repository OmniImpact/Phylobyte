<?php
//breadcrumbs
$this->breadcrumbs.='<a href="?">Home</a> &raquo; <a href="?phylobyte=settings">Settings</a>';

//process

if($_POST['db_submit'] == 'Save Configuration'){

	$dbt = stripslashes($_POST['p_dbt']);
	$dbh = stripslashes($_POST['p_dbh']);
	$dbn = stripslashes($_POST['p_dbn']);
	$dbu = stripslashes($_POST['p_dbu']);
	$dbp = stripslashes($_POST['p_dbp']);
	//generate db connection string

	$connectSuccess = false;
	if($dbt == 'MySQL'){
		try{
			$testDB = new PDO('mysql:host='.$dbh.';dbname='.$dbn, $dbu, $dbp);
			$this->messageAddNotification('Successfully connected to MySQL database. Saving configuration.');
			file_put_contents('../data/dbconfig.array', serialize(Array('dbt' => $dbt, 'dbh' => $dbh, 'dbn' => $dbn, 'dbu' => $dbu, 'dbp' => $dbp)));
			$this->messageAddAlert('You need to log out for changes to take effect.');
		}catch(PDOException $e){
			$this->messageAddDebug('Failed to open database: '.$e);
		}
	}elseif($dbt == 'Sequel Server'){
	    try{
			$sysinfo = posix_uname();
			$sequelServerDriver = ($sysinfo['sysname'] == 'Linux') ? 'FreeTDS' : '{SQL Server}' ;
			$testDB =new PDO("odbc:Driver=$sequelServerDriver;Server=$dbh;Database=$dbn; Uid=$dbu;Pwd=$dbp;");
			$this->messageAddNotification('Successfully connected to Sequel Server database. Saving configuration.');
			file_put_contents('../data/dbconfig.array', serialize(Array('dbt' => $dbt, 'dbh' => $dbh, 'dbn' => $dbn, 'dbu' => $dbu, 'dbp' => $dbp)));
			$this->messageAddAlert('You need to log out for changes to take effect.');
		}catch(PDOException $e){
			$this->messageAddDebug('Failed to open database: '.$e);
		}
	}else{
		$this->messageAddError('Could not understand database type.');
	}
}

//are we trying to toggle a page?
if(isset($_GET['toggle'])){
	if(is_dir('../plugins/'.stripslashes($_GET['toggle'])) ){
		//get the last two letters to know what to toggle
		$pluginName = stripslashes($_GET['toggle']);
		$pluginStatus = substr($pluginName, -2);
		if($pluginStatus == 'on'){
			if(rename('../plugins/'.stripslashes($_GET['toggle']), '../plugins/'.substr(stripslashes($_GET['toggle']), 0 ,-2).'no' ) ){
				$this->messageAddNotification('Successfully disabled plugin');
			}else{
				$this->messageAddError('There was a problem disabling the plugin.');
			}
		}else{
			if(rename('../plugins/'.stripslashes($_GET['toggle']), '../plugins/'.substr(stripslashes($_GET['toggle']), 0 ,-2).'on' ) ){
				$this->messageAddNotification('Successfully enabled plugin'); 	
			}else{
				$this->messageAddError('There was a problem enabling the plugin.');
			}
		}
	}else{
		$this->messageAddError('Unable to toggle plugin. That plugin does not exist in the requested state.');
	}
}

//a little stye
$this->pageArea.='
<style type="text/css">
.selTable{
	width: 100%;
	margin-bottom: 1em;
	border: 1px solid black;
	background-color: #eee;
	border-spacing: 0;
}

.selTable th{
	background-color: #228c22;
	color: white;
	border-right: 1px solid black;
	border-bottom: 1px solid black;
	padding: 3pt;
	border-collapse: collapse;
}

.selTable td{
	border-right: 1px solid silver;
	border-bottom: 1px solid #888;
	padding: 4pt;
	border-collapse: collapse;
}

.selTable .table_row_normal{
	background-color: #eee;
}
.selTable .table_row_highlight{
 	background-color: #eee28c;
}
</style>';

$this->docArea.='

<h3>Phylobyte Settings</h3>

<p>Use this panel to control Phylobyte\'s settings. Right now, you can turn plugins on and off, additional settings will be added later as Phylobyte matures.</p>

<h3>Plugins</h3>

<p>Phylobyte supports a simple plugin architecture. In the <em>Plugins</em> section, you can toggle plugins on and off by clicking the <strong>Toggle Plugin</strong> link next to the plugin name. Each plugin is listed by name, and with the functions that it provides.</p>
';

//get a list of the available plugins
$pluginDirArray = scandir('../plugins');

foreach($pluginDirArray as $possiblePlugin) {
	if(is_dir('../plugins/'.$possiblePlugin) && substr($possiblePlugin, -3) == '.on'){
		//now we make sure the plugin has the minimal requirements
		$pluginDir = $possiblePlugin;
		$pluginName = trim(preg_replace('#^\d+#', '', substr($possiblePlugin, 0, -3)));
		if(is_file('../plugins/'.$pluginDir.'/'.$pluginName.'.php')){
			//we have the minimal plugin setup, so we can now generate navigation
			$onPluginsList.='
			
			<tr><td><strong>'.$pluginName.'</strong></td>';
				//if there is just one other page, we must make subnav.
				$currentPluginDirArray = scandir('../plugins/'.$pluginDir);
				$functionsArray = null;
				foreach($currentPluginDirArray as $possibleFunction) {
					if(substr($possibleFunction, -4) == '.php' && $possibleFunction != $pluginName.'.php' && $possibleFunction != strtolower($pluginName).'.php'){
						if(substr($pluginDir, 0, -3) == $_GET['plugin']) $this->pluginFunctions[] = $possibleFunction;
						$functionsArray[] = substr($possibleFunction, 0, -4);
					}
				}
				if(sizeof($functionsArray) > 0){
					$onPluginsList.='<td>';
					if(is_file('../plugins/'.$pluginDir.'/'.strtolower($pluginName).'.dsc')){
						$onPluginsList.='<em>'.file_get_contents('../plugins/'.$pluginDir.'/'.strtolower($pluginName).'.dsc').'</em><hr style="margin: 0; width: 100%; margin-top: .5em;"/>';
					}
					$onPluginsList.='<ul>';
					foreach($functionsArray as $function) {
						$onPluginsList.='<li>'.trim(preg_replace('#^\d+#', '', $function)).'</li>';
					}
					$onPluginsList.='</ul></td>';
				}else{
					if(is_file('../plugins/'.$pluginDir.'/'.strtolower($pluginName).'.dsc')){
						$onPluginsList.='<td><em>'.file_get_contents('../plugins/'.$pluginDir.'/'.strtolower($pluginName).'.dsc').'</em></td>';
					}else{
						$onPluginsList.='<td>This plugin does not provide a description.</td>';
					}
				}
			$onPluginsList.='<td style="text-align: center;">
			<a href="?phylobyte=settings&amp;toggle='.$possiblePlugin.'" style="color: #800; font-weight: bold;">Toggle Off</a>
			</td>';
			//finish the list element
			$onPluginsList.='</tr>';
		}
	}
}

foreach($pluginDirArray as $possiblePlugin) {
	if(is_dir('../plugins/'.$possiblePlugin) && substr($possiblePlugin, -3) == '.no'){
		//now we make sure the plugin has the minimal requirements
		$pluginDir = $possiblePlugin;
		$pluginName = trim(preg_replace('#^\d+#', '', substr($possiblePlugin, 0, -3)));
		if(is_file('../plugins/'.$pluginDir.'/'.$pluginName.'.php')){
			//we have the minimal plugin setup, so we can now generate navigation
			$offPluginsList.='
			
			<tr><td><strong>'.$pluginName.'</strong></td>';
				//if there is just one other page, we must make subnav.
				$currentPluginDirArray = scandir('../plugins/'.$pluginDir);
				$functionsArray = null;
				foreach($currentPluginDirArray as $possibleFunction) {
					if(substr($possibleFunction, -4) == '.php' && $possibleFunction != $pluginName.'.php' && $possibleFunction != strtolower($pluginName).'.php'){
						if(substr($pluginDir, 0, -3) == $_GET['plugin']) $this->pluginFunctions[] = $possibleFunction;
						$functionsArray[] = substr($possibleFunction, 0, -4);
					}
				}
				if(sizeof($functionsArray) > 0){
					$offPluginsList.='<td>';
					if(is_file('../plugins/'.$pluginDir.'/'.strtolower($pluginName).'.dsc')){
						$offPluginsList.='<em>'.file_get_contents('../plugins/'.$pluginDir.'/'.strtolower($pluginName).'.dsc').'</em><hr style="margin: 0; width: 100%; margin-top: .5em;"/>';
					}
					$offPluginsList.='<ul>';
					foreach($functionsArray as $function) {
						$offPluginsList.='<li>'.trim(preg_replace('#^\d+#', '', $function)).'</li>';
					}
					$offPluginsList.='</ul></td>';
				}else{
					if(is_file('../plugins/'.$pluginDir.'/'.strtolower($pluginName).'.dsc')){
						$offPluginsList.='<td><em>'.file_get_contents('../plugins/'.$pluginDir.'/'.strtolower($pluginName).'.dsc').'</em></td>';
					}else{
						$offPluginsList.='<td>This plugin does not provide a description.</td>';
					}
				}
			$offPluginsList.='<td style="text-align: center;"><a href="?phylobyte=settings&amp;toggle='.$possiblePlugin.'" style="color: #080; font-weight: bold;">Toggle On</a></td>';
			//finish the list element
			$offPluginsList.='</tr>';
		}
	}
}


$this->pageArea.='
<fieldset>
	<legend>Manage Plugins</legend>
	<form method="post" action="?'.$_SERVER['QUERY_STRING'].'">
	
	<h3>Enabled Plugins</h3>
	<table class="selTable">
		<tr>
			<th>Plugin Name</th><th>Description and Functions</th><th>Toggle</th>
		</tr>
		'.$onPluginsList.'
	</table>
	
	<h3>Disabled Plugins</h3>
	<table class="selTable">
		<tr>
			<th>Plugin Name</th><th>Description and Functions</th><th>Toggle</th>
		</tr>
		'.$offPluginsList.'
	</table>
	
	</form>
</fieldset>

<fieldset>
	<legend>Database Configuration</legend>
	<form method="post" action="?'.$_SERVER['QUERY_STRING'].'">

	<p>The database configuration will take effect when you log out.
	Please note that if you change to a new database, you will be prompted to create a new admin user.</p>

	<label for="p_dbt">Database Type</label>
	<select name="p_dbt">
		<option value="'.$this->sessionDbInfo['dbt'].'">Keep '.$this->sessionDbInfo['dbt'].'</option>
		<option value="MySQL">MySQL</option>
		<option value="Sequel Server">Sequel Server</option>
	</select><br/>
	<label for="p_dbh">Database Host</label><input type="text" name="p_dbh" value="'.$this->sessionDbInfo['dbh'].'"/><br/>
	<label for="p_dbn">Database Name</label><input type="text" name="p_dbn" value="'.$this->sessionDbInfo['dbn'].'"/><br/>
	<label for="p_dbu">User Name</label><input type="text" name="p_dbu" value="'.$this->sessionDbInfo['dbu'].'"/><br/>
	<label for="p_dbp">Password</label><input type="password" name="p_dbp" value="'.$this->sessionDbInfo['dbp'].'"/><br/>

		<input type="submit" name="db_submit" value="Save Configuration" style="width: 14em; margin-left: 70%;" />

	</form>
	
</fieldset>

';

?>
