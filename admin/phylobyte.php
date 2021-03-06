<?php

class phylobyte{

	static $messageArea;
	static $navigationArea;
	static $mobileNav;
	static $breadcrumbs;
	static $pageArea;
	static $docArea;
	static $headArea;
	static $pageTitle;
	static $pluginFunctions;
	
	static $sessionUserInfo;
	static $sessionDbInfo;

	static $directMessages = false;
	
	static $phylobyteDB;

	function __construct(){

		$this->pageTitle = 'Phylobyte CMS';
		$GLOBALS['MESSAGESTAMPBASE'] = microtime(true);
		try{
			if(count($_SESSION['dbinfo']) < 2){
				if(is_file('../data/dbconfig.array')){
					$_SESSION['dbinfo'] = unserialize(file_get_contents('../data/dbconfig.array'));
				}else{
					session_destroy();
					session_start();
					include('dbsetup.php');
					if(is_file('../data/dbconfig.array')){
						$_SESSION['dbinfo'] = unserialize(file_get_contents('../data/dbconfig.array'));
						$this->sessionDbInfo = $_SESSION['dbinfo'];
					}else{
						return false;
					}
				}
			}else{
				$this->sessionDbInfo = $_SESSION['dbinfo'];
			}
			
			if($this->sessionDbInfo['dbt'] == 'MySQL'){
				try{
					$this->phylobyteDB = new PDO('mysql:host='.$this->sessionDbInfo['dbh'].';dbname='.$this->sessionDbInfo['dbn'], $this->sessionDbInfo['dbu'], $this->sessionDbInfo['dbp']);
				}catch(PDOException $e){
					$this->messageAddDebug('Failed to open database: '.$e);
				}
			}
			$GLOBALS['PHYLOBYTEDB'] = $this->phylobyteDB;
		}catch(PDOException $e){
			$this->messageAddDebug('Failed to connect to the database.');
		}
		try{
			$this->phylobyteDB->exec("
				CREATE TABLE IF NOT EXISTS p_groups(
					id INTEGER PRIMARY KEY AUTO_INCREMENT,
					name TEXT,
					description TEXT
				);");
			$this->phylobyteDB->exec("
				CREATE TABLE IF NOT EXISTS p_users(
					id INTEGER PRIMARY KEY AUTO_INCREMENT,
					username TEXT,
					name TEXT,
					passwordhash TEXT,
					status TEXT,
					statusvalue TEXT,
					super TEXT,
					email TEXT
				);");
			try{
			$this->phylobyteDB->exec("
				CREATE TABLE IF NOT EXISTS p_gattributes(
					id INTEGER PRIMARY KEY AUTO_INCREMENT,
					gid INTEGER,
					attribute TEXT,
					defaultvalue TEXT,
						CONSTRAINT FOREIGN KEY (gid) REFERENCES p_groups(id)
				);");
			}catch(Exception $e){
				$this->messageAddDebug($e);
			}
			$this->phylobyteDB->exec("
				CREATE TABLE IF NOT EXISTS p_uattributes(
					id INTEGER PRIMARY KEY AUTO_INCREMENT,
					uid INTEGER,
					aid INTEGER,
					value TEXT,
						CONSTRAINT FOREIGN KEY (uid) REFERENCES p_users(id),
						CONSTRAINT FOREIGN KEY (aid) REFERENCES p_gattributes(id)
				);");
			$this->phylobyteDB->exec("
				CREATE TABLE IF NOT EXISTS p_memberships(
					id INTEGER PRIMARY KEY AUTO_INCREMENT,
					userid INTEGER,
					groupid INTEGER,
					lastused TEXT,
					joined TEXT,
						CONSTRAINT FOREIGN KEY (userid) REFERENCES p_users(id),
						CONSTRAINT FOREIGN KEY (groupid) REFERENCES p_groups(id)
				);");
			$this->phylobyteDB->exec("
				CREATE TABLE IF NOT EXISTS p_plugins(
					id INTEGER PRIMARY KEY AUTO_INCREMENT,
					name VARCHAR (256) NOT NULL,
					weight INTEGER,
					enabled TEXT,
					available TEXT,
						UNIQUE KEY `name` (`name`)
				);");
			$this->phylobyteDB->exec("
				CREATE TABLE IF NOT EXISTS p_pluginaccess(
					id INTEGER PRIMARY KEY AUTO_INCREMENT,
					pid INTEGER,
					gid INTEGER,
					uid INTEGER,
						CONSTRAINT FOREIGN KEY (pid) REFERENCES p_plugins(id) ON DELETE CASCADE,
						CONSTRAINT FOREIGN KEY (gid) REFERENCES p_groups(id) ON DELETE CASCADE,
						CONSTRAINT FOREIGN KEY (uid) REFERENCES p_users(id) ON DELETE CASCADE 
				);");
			$getRows = $this->phylobyteDB->prepare("SELECT COUNT(*) FROM p_groups");
			$getRows->execute();
			$numRows = $getRows->fetchAll();
			if($numRows[0][0] == 0){
			if($this->phylobyteDB->exec("
				INSERT INTO p_groups (name, description)
				VALUES ('admin', 'Phylobyte default administrator group');") &&
				$this->phylobyteDB->exec("
				INSERT INTO p_users (username, status, name)
				VALUES ('admin', 'override', 'Administrator');") &&
				$this->phylobyteDB->exec("
				INSERT INTO p_memberships (userid, groupid)
				VALUES ('1', '1');")
				){
					$this->messageAddDebug('Initialized Phylobyte User Tables');
				}
			}
		}catch(PDOException $e){
			echo($e);
		}
		$this->updatePlugins();
		if($this->login()){
			if($this->checkAdmin()){
				$this->pageBuild();
				$this->navBuild();
			}else{
				//you're not an admin!
				header('Location: /');
				$this->pageUser();
				$this->navUser();
			}
		}
	}

	function messageStamp(){
		$GLOBALS['MESSAGESTAMPITR']++;
		//this needs to use messageStampBase, and not the time directly
		return $GLOBALS['MESSAGESTAMPBASE'].':'.$GLOBALS['MESSAGESTAMPITR']++;
	}
	
	function updatePlugins(){
		$this->phylobyteDB->exec("
				UPDATE p_plugins
				SET available='false';");
		
		//scan the plugins
		$pluginDirArray = scandir('../plugins');

		foreach($pluginDirArray as $possiblePlugin) {
			if(substr($possiblePlugin, -2) == '.p'){
			
				$pluginName = trim(preg_replace('#^\d+#', '', substr($possiblePlugin, 0, -2)));
				$pluginNumbers = Array();
				preg_match('#^\d+#', $possiblePlugin, $pluginNumbers);
				$pluginNumber = trim($pluginNumbers[0]);
				
				$name = $this->phylobyteDB->quote($pluginName);
				$weight = $this->phylobyteDB->quote($pluginNumber);
				
				$this->phylobyteDB->exec("
					INSERT INTO p_plugins (name, weight, enabled, available)
					VALUES ($name, $weight, 'true', 'true') ON DUPLICATE key
					UPDATE name=$name, weight=$weight, available='true';");
				
			}
		}
		
		//delete any still unavailable
		$this->phylobyteDB->exec("
				DELETE FROM p_plugins
				WHERE available='false';");
	}

	function login(){
		//do logout
		if($_REQUEST['phylobyte'] == 'logout'){
			session_destroy();
			session_start();
			self::messageAddNotification('You are now logged out');
		}else{
			//do login
			if(isset($_SESSION['loginid'])){
				$userquery = $this->phylobyteDB->prepare("SELECT * FROM p_users WHERE id='{$_SESSION['loginid']}';");
				$userquery->execute();
				$queryResults = $userquery->fetchAll();
				$this->sessionUserInfo = $queryResults[0];
			}
		}

		if(!isset($_SESSION['loginid']) || $this->sessionUserInfo['status'] == 'override'){
			include('loginform.php');
			if($accountverify == 'success'){
				return true;
			}else{
				return false;
			}
		}else{
			//credentials OK for now
			//query database and get user info. store to $sessionUserInfo
			$userquery = $this->phylobyteDB->prepare("SELECT * FROM p_users WHERE id='{$_SESSION['loginid']}';");
			$userquery->execute();
			$queryResults = $userquery->fetchAll();
			$this->sessionUserInfo = $queryResults[0];
			return true;
		}
	}
	
	function checkAdmin(){
		$adminquery = $this->phylobyteDB->prepare("SELECT * FROM p_memberships WHERE userid='{$_SESSION['loginid']}' AND groupid='1';");
		$adminquery->execute();
		$adminqueryArray = $adminquery->fetchAll();
			if(count($adminqueryArray) > 0){
				return true;
			}
		return false;
	}
	
	function navBuild(){
		$MS = new oi_mobilesupport;
		$this->navigationArea.='
		<ul>';

		if(!$MS->useMobile()){
			$this->navigationArea.='<li><a href="?">Home</a></li>';
		}

		//ok, we are ready to build some navigation
		$pluginQuery = $this->phylobyteDB->prepare("
			SELECT * FROM p_plugins WHERE enabled='true' ORDER BY weight;
		");
		$pluginQuery->execute();
		$pluginArray = $pluginQuery->fetchAll(PDO::FETCH_ASSOC);
		
		foreach($pluginArray as $plugin) {
		    if(is_dir('../plugins/'.$plugin['weight'].' '.$plugin['name'].'.p') ){
				//now we make sure the plugin has the minimal requirements
				$pluginDir = $plugin['weight'].' '.$plugin['name'].'.p';
				$pluginName = $plugin['name'];
				if(is_file('../plugins/'.$pluginDir.'/'.$pluginName.'.php')){
					//we have the minimal plugin setup, so we can now generate navigation
					$this->navigationArea.='<li><a href="?plugin='.substr($pluginDir, 0, -2).'">'.$pluginName;
						//if there is just one other page, we must make subnav.
						$currentPluginDirArray = scandir('../plugins/'.$pluginDir);
						$functionsArray = null;
						foreach($currentPluginDirArray as $possibleFunction) {
							if(substr($possibleFunction, -4) == '.php' && $possibleFunction != $pluginName.'.php'){
								$functionsArray[] = substr($possibleFunction, 0, -4);
							}
						}
						if(sizeof($functionsArray) > 0){
							$this->navigationArea.='&hellip;</a>';
							$this->navigationArea.='<ul>';
							foreach($functionsArray as $function) {
							    $this->navigationArea.='<li><a href="?plugin='.substr($pluginDir, 0, -2).'&amp;function='.str_replace('&', '%26', $function).'">'.trim(preg_replace('#^\d+#', '', $function)).'</a></li>';
							}
							$this->navigationArea.='</ul>';
						}else{
							$this->navigationArea.='</a>';
						}
						
					//finish the list element
					$this->navigationArea.='</li>';
				}
			}
		}
		
		
		if(!$MS->useMobile()){
			if($_SERVER['QUERY_STRING'] == ''){
				$logoutQueryString = '?phylobyte=logout';
			}else{
				$logoutQueryString = '?'.$_SERVER['QUERY_STRING'].'&phylobyte=logout';
			}
			$this->navigationArea.='
			</ul>
			<ul style="float: right;">
				<li>
					<a href="../">View Website</a>
				</li>
				<li><a style="min-width: 10em; text-align: center;">Welcome, '.$this->sessionUserInfo['name'].' '.$this->sessionUserInfo['lname'].'</a>
					<ul style="float: right; min-width: 100%;">
						<li><a href="?phylobyte=account">My Account</a></li>
						<li><a href="?phylobyte=settings">Settings</a></li>
						<li><a href="'.$logoutQueryString.'">Log Out</a></li>
					</ul>
				</li>
			</ul>
			';
		}else{
			$this->mobileNav = '
				<div class="breadcrumbs" style="text-align: center; background-color: white; padding: 4pt;">
					<a href="?" style="margin-top: 4pt;">Home</a>
					<a href="../" style="margin-top: 4pt;">View Website</a>
					<a href="?phylobyte=account" style="margin-top: 4pt;">My Account</a>
					<a href="?phylobyte=settings" style="margin-top: 4pt;">Settings</a>
					<a href="?phylobyte=logout" style="margin-top: 4pt;">Log Out</a>
				</div>
			';
		}
		return true;
	}
	
	function navUser(){
		$MS = new oi_mobilesupport;
		$this->navigationArea.='
		<ul>';

		if(!$MS->useMobile()){
			$this->navigationArea.='<li><a href="?">Home</a></li>';
		}

		if(!$MS->useMobile()){
			if($_SERVER['QUERY_STRING'] == ''){
				$logoutQueryString = '?phylobyte=logout';
			}else{
				$logoutQueryString = '?'.$_SERVER['QUERY_STRING'].'&phylobyte=logout';
			}
			$this->navigationArea.='
			</ul>
			<ul style="float: right;">
				<li>
					<a href="../">View Website</a>
				</li>
				<li><a style="min-width: 10em; text-align: center;">Welcome, '.$this->sessionUserInfo['name'].' '.$this->sessionUserInfo['lname'].'</a>
					<ul style="float: right; min-width: 100%;">
						<li><a href="?phylobyte=account">My Account</a></li>
						<li><a href="'.$logoutQueryString.'">Log Out</a></li>
					</ul>
				</li>
			</ul>
			';
		}else{
			$this->mobileNav = '
				<div class="breadcrumbs" style="text-align: center; background-color: white; padding: 4pt;">
					<a href="?" style="margin-top: 4pt;">Home</a>
					<a href="../" style="margin-top: 4pt;">View Website</a>
					<a href="?phylobyte=account" style="margin-top: 4pt;">My Account</a>
					<a href="?phylobyte=logout" style="margin-top: 4pt;">Log Out</a>
				</div>
			';
		}
		return true;
	}

	function plugin_autoIndex($return = '
	<hr/><br/>
	
	<h3>
		<a href="?plugin=%P%&amp;function=%F%" style="font-size: 130%;">%Fn%</a>
	</h3>
	<p>%Fd%</p>

	<br/>
	', $header = '
	<h3 style="font-size: 160%; color: #666; font-weight: bold;">
		%Pn% Function List
	</h3><br/>
	'){
		//index the functions
		$pluginDir = stripslashes($_GET['plugin'].'.p');
		$pluginDirArray = scandir('../plugins/'.$pluginDir);
		$pluginName = substr(trim(preg_replace('#^\d+#', '', $pluginDir)), 0, -2);
		
		foreach($pluginDirArray as $possibleFunction) {
			if(substr($possibleFunction, -4) == '.php' 
			&& $possibleFunction != $pluginName.'.php'){
				$this->pluginFunctions[] = $possibleFunction;
			}
		}
		
		if($return == 'array'){
			return $this->pluginFunctions;
		}else{
			foreach($this->pluginFunctions as $function){
				$functionName = substr(trim(preg_replace('#^\d+#', '', stripslashes($function))), 0, -4);

				$item = str_replace('%F%', str_replace('&', '%26',substr($function, 0, -4)), $return);
				$item = str_replace('%Fn%', $functionName, $item);
				if(is_file('../plugins/'.stripslashes($_GET['plugin']).'.p/'.substr($function, 0, -3).'dsc')){
					$item = str_replace('%Fd%', stripslashes(file_get_contents('../plugins/'.stripslashes($_GET['plugin']).'.p/'.substr($function, 0, -3).'dsc')),$item);
				}else{
					$item = str_replace('%Fd%', '',$item);
				}
				$item = str_replace('%P%', $_GET['plugin'], $item);
				$item = str_replace('%Pn%', trim(preg_replace('#^\d+#', '', stripslashes($_GET['plugin']))), $item);
				$item = str_replace('<p></p>', '',$item);
				$autoIndex.=$item;
			}
			$autoHeader = str_replace('%P%', $_GET['plugin'], $header);
			$autoHeader = str_replace('%Pn%', trim(preg_replace('#^\d+#', '', stripslashes($_GET['plugin']))), $autoHeader);
			$this->pageArea.=$autoHeader.$autoIndex;
		}
	}
	
	function pageBuild(){
		//build the page based on the link, if nothing else, include home.
		if(!isset($_GET['plugin']) && !isset($_GET['phylobyte'])){
			include('home.php');
		}elseif($_GET['phylobyte'] == 'account'){
			include('account.php');
			return true;
		}elseif($_GET['phylobyte'] == 'settings'){
			//check "admin" first
			include('settings.php');
			return true;
		}else{
			//we have selected a plugin. Lets pull the name
			$pluginDir = stripslashes($_GET['plugin']).'.p';
			$pluginName = trim(preg_replace('#^\d+#', '', stripslashes($_GET['plugin'])));
			//include the init file
			if(is_file('../plugins/'.$pluginDir.'/'.$pluginName.'.init')){
				include_once('../plugins/'.$pluginDir.'/'.$pluginName.'.init');
			}
			//possibly include the css
			if(is_file('../plugins/'.$pluginDir.'/'.$pluginName.'.css')){
				$this->headArea.='
		<link href="../plugins/'.$pluginDir.'/'.$pluginName.'.css" rel="stylesheet" type="text/css" />
				';
			}
			
			//modify the title
			$this->pageTitle.=' | '.$pluginName;

			//make some breadcrumbs
			$this->breadcrumbs.='<a href="?">Home</a> &raquo; <a href="?plugin='.substr($pluginDir, 0, -2).'">'.$pluginName.'</a>';

			
			
			//check the function
			if(!isset($_GET['function'])){
				//we include the default
				$includePlugin = '../plugins/'.$pluginDir.'/'.$pluginName.'.php';

				if(is_file('../plugins/'.$pluginDir.'/'.$pluginName.'.php')){
					$includePlugin = '../plugins/'.$pluginDir.'/'.$pluginName.'.php';
					include_once($includePlugin);
				}
				
				if(is_file('../plugins/'.$pluginDir.'/'.$pluginName.'.html')){
					$this->docArea.=stripslashes(file_get_contents('../plugins/'.$pluginDir.'/'.$pluginName.'.html'));
				}
				
			}else{
				//we include the function
				$function = stripslashes($_GET['function']);
				$this->pageTitle.=' | '.trim(preg_replace('#^\d+#', '', $function));
				$this->breadcrumbs.=' &raquo; <a href="?plugin='.substr($pluginDir, 0, -2).'&amp;function='.str_replace('&', '%26', $function).'">'.trim(preg_replace('#^\d+#', '', $function)).'</a>';
				
				if(is_file('../plugins/'.$pluginDir.'/'.$function.'.php')){
					$includeFunction = '../plugins/'.$pluginDir.'/'.$function.'.php';
					include_once($includeFunction);
				}
				
				if(is_file('../plugins/'.$pluginDir.'/'.$function.'.html')){
					$this->docArea=null;
					$this->docArea.=stripslashes(file_get_contents('../plugins/'.$pluginDir.'/'.$function.'.html'));
				}
			}
			
			
		}
		
		
		return true;
	}
	
	function pageUser(){
		//build the page based on the link, if nothing else, include home.
		if(!isset($_GET['plugin']) && !isset($_GET['phylobyte'])){
			include('homelimited.php');
		}elseif($_GET['phylobyte'] == 'account'){
			include('account.php');
			return true;
		}
		return true;
	}
	
	function build_finish(){
		if($this->directMessages != true){
			$this->messageArea = $GLOBALS['MESSAGES']->pullquery('%v%', "SELECT * FROM __REGISTRY____pmessages WHERE mykey LIKE '{$GLOBALS['MESSAGESTAMPBASE']}%';");
		}
		$this->messageArea = str_replace('#e.', '<div class="error">', $this->messageArea);
		$this->messageArea = str_replace('#a.', '<div class="alert">', $this->messageArea);
		$this->messageArea = str_replace('#n.', '<div class="notification">', $this->messageArea);
		$this->messageArea = str_replace('#d.', '<div class="debug">', $this->messageArea);
		
		$this->messageArea = str_replace('##.', '</div>', $this->messageArea);
	}
	
	static function messageAddAlert($alert){
		$GLOBALS['MESSAGES']->push(phylobyte::messageStamp(), '#a.'.$alert.'##.');
	}
	static function messageAddError($error){
		$GLOBALS['MESSAGES']->push(phylobyte::messageStamp(), '#e.'.$error.'##.');
	}
	static function messageAddNotification($notice){
		$GLOBALS['MESSAGES']->push(phylobyte::messageStamp(), '#n.'.$notice.'##.');
	}
	static function messageAddDebug($debug){
		$GLOBALS['MESSAGES']->push(phylobyte::messageStamp(), '#d.'.$debug.'##.');
	}
}

?>