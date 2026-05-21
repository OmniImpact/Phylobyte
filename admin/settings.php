<?php
//breadcrumbs
self::$breadcrumbs.='<a href="?">Home</a> &raquo; <a href="?phylobyte=settings">Settings</a>';

//process

if(isset($_POST['db_submit']) && $_POST['db_submit'] == 'Save Configuration'){

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
			self::messageAddNotification('Successfully connected to MySQL database. Saving configuration.');
			file_put_contents(__DIR__ . '/../data/dbconfig.array', serialize(Array('dbt' => $dbt, 'dbh' => $dbh, 'dbn' => $dbn, 'dbu' => $dbu, 'dbp' => $dbp)));
			self::messageAddAlert('You need to log out for changes to take effect.');
		}catch(PDOException $e){
			self::messageAddDebug('Failed to open database: '.$e); // Removed debugging message
		}
	}else{
		self::messageAddError('Could not understand database type.');
	}
}

//are we trying to toggle a page?
if(isset($_GET['toggle'])){
	$pluginId = self::$phylobyteDB->quote($_GET['toggle']);

	//get current plugin info
	//ok, we are ready to build some navigation
	$pluginQuery = self::$phylobyteDB->prepare("
		SELECT * FROM p_plugins WHERE id=$pluginId;
	");
	$pluginQuery->execute();
	$pluginArray = $pluginQuery->fetchAll(PDO::FETCH_ASSOC);
	$pluginArray = $pluginArray[0];

	if($pluginArray['enabled'] == 'true'){
		//disable the plugin
		self::messageAddNotification('Disabling '.$pluginArray['name']);
		self::$phylobyteDB->exec("
			UPDATE p_plugins SET enabled='false' WHERE id=$pluginId;
		");
	}else{
		//enable the plugin
		self::messageAddNotification('Enabling '.$pluginArray['name']);
		self::$phylobyteDB->exec("
			UPDATE p_plugins SET enabled='true' WHERE id=$pluginId;
		");
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
$pluginDirArray = scandir(__DIR__ . '/../plugins');

$pluginsEnabledQuery = self::$phylobyteDB->prepare("
	SELECT * FROM p_plugins WHERE enabled='true' ORDER BY weight;
");
$pluginsEnabledQuery->execute();
$pluginsEnabledArray = $pluginsEnabledQuery->fetchAll(PDO::FETCH_ASSOC);

$pluginsDisabledQuery = self::$phylobyteDB->prepare("
	SELECT * FROM p_plugins WHERE enabled='false' ORDER BY weight;
");
$pluginsDisabledQuery->execute();
$pluginsDisabledArray = $pluginsDisabledQuery->fetchAll(PDO::FETCH_ASSOC);

// Initialize variables to prevent "Undefined variable" warnings
$onPluginsList = '';
$offPluginsList = '';

foreach($pluginsEnabledArray as $enabledPlugin) {
	if(is_dir(__DIR__ . '/../plugins/'.$enabledPlugin['weight'].' '.$enabledPlugin['name'].'.p') ){
		//now we make sure the plugin has the minimal requirements
		$pluginDir = $enabledPlugin['weight'].' '.$enabledPlugin['name'].'.p';
		$pluginName = $enabledPlugin['name'];
		if(is_file(__DIR__ . '/../plugins/'.$pluginDir.'/'.$pluginName.'.php')){
			//we have the minimal plugin setup, so we can now generate navigation
			$onPluginsList.='
			
			<tr><td><strong>'.$pluginName.'</strong></td>';
				//if there is just one other page, we must make subnav.
				$currentPluginDirArray = scandir(__DIR__ . '/../plugins/'.$pluginDir);
				$functionsArray = null;
				foreach($currentPluginDirArray as $possibleFunction) {
					if(substr($possibleFunction, -4) == '.php' && $possibleFunction != $pluginName.'.php'){
						if(isset($_GET['plugin']) && substr($pluginDir, 0, -3) == $_GET['plugin']) $this->pluginFunctions[] = $possibleFunction;
						$functionsArray[] = substr($possibleFunction, 0, -4);
					}
				}
				if(sizeof($functionsArray) > 0){
					$onPluginsList.='<td>';
					if(is_file(__DIR__ . '/../plugins/'.$pluginDir.'/'.$pluginName.'.dsc')){
						$onPluginsList.='<em>'.file_get_contents(__DIR__ . '/../plugins/'.$pluginDir.'/'.$pluginName.'.dsc').'</em><hr style="margin: 0; width: 100%; margin-top: .5em;"/>';
					}
					$onPluginsList.='<ul>';
					foreach($functionsArray as $function) {
						$onPluginsList.='<li>'.trim(preg_replace('#^\d+#', '', $function)).'</li>';
					}
					$onPluginsList.='</ul></td>';
				}else{
					if(is_file(__DIR__ . '/../plugins/'.$pluginDir.'/'.$pluginName.'.dsc')){
						$onPluginsList.='<td><em>'.file_get_contents(__DIR__ . '/../plugins/'.$pluginDir.'/'.$pluginName.'.dsc').'</em></td>';
					}else{
						$onPluginsList.='<td>This plugin does not provide a description.</td>';
					}
				}
			$onPluginsList.='<td style="text-align: center;">
			<a href="?phylobyte=settings&amp;toggle='.$enabledPlugin['id'].'" style="color: #800; font-weight: bold;">Toggle Off</a>
			</td>';
			//finish the list element
			$onPluginsList.='</tr>';
		}
	}
}

foreach($pluginsDisabledArray as $disabledPlugin) {
	if(is_dir(__DIR__ . '/../plugins/'.$disabledPlugin['weight'].' '.$disabledPlugin['name'].'.p')){
		//now we make sure the plugin has the minimal requirements
		$pluginDir = $disabledPlugin['weight'].' '.$disabledPlugin['name'].'.p';
		$pluginName = $disabledPlugin['name'];
		if(is_file(__DIR__ . '/../plugins/'.$pluginDir.'/'.$pluginName.'.php')){
			//we have the minimal plugin setup, so we can now generate navigation
			$offPluginsList.='
			
			<tr><td><strong>'.$pluginName.'</strong></td>';
				//if there is just one other page, we must make subnav.
				$currentPluginDirArray = scandir(__DIR__ . '/../plugins/'.$pluginDir);
				$functionsArray = null;
				foreach($currentPluginDirArray as $possibleFunction) {
					if(substr($possibleFunction, -4) == '.php' && $possibleFunction != $pluginName.'.php'){
						if(isset($_GET['plugin']) && substr($pluginDir, 0, -3) == $_GET['plugin']) $this->pluginFunctions[] = $possibleFunction;
						$functionsArray[] = substr($possibleFunction, 0, -4);
					}
				}
				if(sizeof($functionsArray) > 0){
					$offPluginsList.='<td>';
					if(is_file(__DIR__ . '/../plugins/'.$pluginDir.'/'.$pluginName.'.dsc')){
						$offPluginsList.='<em>'.file_get_contents(__DIR__ . '/../plugins/'.$pluginDir.'/'.$pluginName.'.dsc').'</em><hr style="margin: 0; width: 100%; margin-top: .5em;"/>';
					}
					$offPluginsList.='<ul>';
					foreach($functionsArray as $function) {
						$offPluginsList.='<li>'.trim(preg_replace('#^\d+#', '', $function)).'</li>';
					}
					$offPluginsList.='</ul></td>';
				}else{
					if(is_file(__DIR__ . '/../plugins/'.$pluginDir.'/'.$pluginName.'.dsc')){
						$offPluginsList.='<td><em>'.file_get_contents(__DIR__ . '/../plugins/'.$pluginDir.'/'.$pluginName.'.dsc').'</em></td>';
					}else{
						$offPluginsList.='<td>This plugin does not provide a description.</td>';
					}
				}
			$offPluginsList.='<td style="text-align: center;"><a href="?phylobyte=settings&amp;toggle='.$disabledPlugin['id'].'" style="color: #080; font-weight: bold;">Toggle On</a></td>';
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
		'. $onPluginsList .'
	</table>
	
	<h3>Disabled Plugins</h3>
	<table class="selTable">
		<tr>
			<th>Plugin Name</th><th>Description and Functions</th><th>Toggle</th>
		</tr>
		'. $offPluginsList .'
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
		<option value="'.(self::$sessionDbInfo['dbt'] ?? '').'">Keep '.(self::$sessionDbInfo['dbt'] ?? '').'</option>
		<option value="MySQL">MySQL</option>
	</select><br/>
	<label for="p_dbh">Database Host</label><input type="text" name="p_dbh" value="'.(self::$sessionDbInfo['dbh'] ?? '').'"/><br/>
	<label for="p_dbn">Database Name</label><input type="text" name="p_dbn" value="'.(self::$sessionDbInfo['dbn'] ?? '').'"/><br/>
	<label for="p_dbu">User Name</label><input type="text" name="p_dbu" value="'.(self::$sessionDbInfo['dbu'] ?? '').'"/><br/>
	<label for="p_dbp">Password</label><input type="password" name="p_dbp" value="'.(self::$sessionDbInfo['dbp'] ?? '').'"/><br/>

		<input type="submit" name="db_submit" value="Save Configuration" style="width: 14em; margin-left: 70%;" />
		<div class="ff">&nbsp;</div>
	</form>
	
</fieldset>

';

?>