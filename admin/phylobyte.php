<?php

class phylobyte{

	public $messageArea;
	static $navigationArea;
	static $mobileNav;
	static $breadcrumbs;
	public $pageArea; // Changed from static to public
	public $docArea; // Changed from static to public
	static $headArea;
	public $pluginFunctions; // Changed from static to public
	public $pageTitle; // Declared pageTitle property

	static $sessionUserInfo;
	static $sessionDbInfo;

	public $directMessages = false;

	static $phylobyteDB;

	function __construct(){
		// AIDO: hook into PHP's error handling so that errors, warnings, and deprecations are are caught and output into the message "pile"
		// Set custom error handler
		set_error_handler([$this, 'handle_error']);
		error_reporting(E_ALL);
		ini_set('display_errors', 0);


		$this->pageTitle = 'Phylobyte CMS';
		$this->messageArea = ''; // Initialize messageArea to an empty string
		$GLOBALS['MESSAGESTAMPBASE'] = microtime(true);
		$GLOBALS['MESSAGESTAMPITR'] = 0; // Initialize the global variable here
		try{
			if(!isset($_SESSION['dbinfo']) || count($_SESSION['dbinfo']) < 2){
				if(is_file('../data/dbconfig.array')){
					$_SESSION['dbinfo'] = unserialize(file_get_contents('../data/dbconfig.array'));
				}else{
					session_destroy();
					session_start();
					include('dbsetup.php');
					if(is_file('../data/dbconfig.array')){
						$_SESSION['dbinfo'] = unserialize(file_get_contents('../data/dbconfig.array'));
						self::$sessionDbInfo = $_SESSION['dbinfo'];
					}else{
						// If dbconfig.array is still not found here, it means dbsetup.php is handling the display.
						// The constructor should exit here to prevent further errors.
						return;
					}
				}
			}else{
				self::$sessionDbInfo = $_SESSION['dbinfo'];
			}

			if(isset(self::$sessionDbInfo['dbt']) && self::$sessionDbInfo['dbt'] == 'MySQL'){
				try{
					self::$phylobyteDB = new PDO('mysql:host='.self::$sessionDbInfo['dbh'].';dbname='.self::$sessionDbInfo['dbn'], self::$sessionDbInfo['dbu'], self::$sessionDbInfo['dbp']);
					self::$phylobyteDB->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
				}catch(PDOException $e){
					self::messageAddDebug('Failed to open database: '.$e->getMessage());
					self::$phylobyteDB = null; // Ensure it's null on failure
				}
			} elseif(isset(self::$sessionDbInfo['dbt']) && self::$sessionDbInfo['dbt'] == 'SQLite'){
				try{
					$absoluteDataDirPath = realpath(dirname(__FILE__) . '/../data/');
					self::$phylobyteDB = new PDO('sqlite:'.$absoluteDataDirPath.'/'.self::$sessionDbInfo['dbn']);
					self::$phylobyteDB->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
				}catch(PDOException $e){
					self::messageAddDebug('Failed to open SQLite database: '.$e->getMessage());
					self::$phylobyteDB = null; // Ensure it's null on failure
				}
			}
			$GLOBALS['PHYLOBYTEDB'] = self::$phylobyteDB;
		}catch(PDOException $e){
			self::messageAddDebug('Failed to connect to the database: '.$e->getMessage());
			self::$phylobyteDB = null; // Ensure it's null on failure
		}

		// Check if database connection was successful before proceeding with table creation and plugin updates
		if (self::$phylobyteDB !== null) {
			$autoIncrementKeyword = '';
			if (isset(self::$sessionDbInfo['dbt']) && self::$sessionDbInfo['dbt'] == 'MySQL') {
				$autoIncrementKeyword = 'AUTO_INCREMENT';
			}
			// For SQLite, INTEGER PRIMARY KEY implicitly handles auto-increment,
			// so no explicit AUTO_INCREMENT keyword is needed or desired for basic use.

			try{
				self::$phylobyteDB->exec("
					CREATE TABLE IF NOT EXISTS p_groups(
						id INTEGER PRIMARY KEY " . $autoIncrementKeyword . ",
						name TEXT,
						description TEXT
					);");
				self::$phylobyteDB->exec("
					CREATE TABLE IF NOT EXISTS p_users(
						id INTEGER PRIMARY KEY " . $autoIncrementKeyword . ",
						username TEXT,
						name TEXT,
						passwordhash TEXT,
						status TEXT,
						statusvalue TEXT,
						super TEXT,
						email TEXT
					);");
				try{
				self::$phylobyteDB->exec("
					CREATE TABLE IF NOT EXISTS p_gattributes(
						id INTEGER PRIMARY KEY " . $autoIncrementKeyword . ",
						gid INTEGER,
						attribute TEXT,
						defaultvalue TEXT,
							FOREIGN KEY (gid) REFERENCES p_groups(id)
					);");
				}catch(Exception $e){
					self::messageAddDebug($e->getMessage());
				}
				self::$phylobyteDB->exec("
					CREATE TABLE IF NOT EXISTS p_uattributes(
						id INTEGER PRIMARY KEY " . $autoIncrementKeyword . ",
						uid INTEGER,
						aid INTEGER,
						value TEXT,
							FOREIGN KEY (uid) REFERENCES p_users(id),
							FOREIGN KEY (aid) REFERENCES p_gattributes(id)
					);");
				self::$phylobyteDB->exec("
					CREATE TABLE IF NOT EXISTS p_memberships(
						id INTEGER PRIMARY KEY " . $autoIncrementKeyword . ",
						userid INTEGER,
						groupid INTEGER,
						lastused TEXT,
						joined TEXT,
							FOREIGN KEY (userid) REFERENCES p_users(id),
							FOREIGN KEY (groupid) REFERENCES p_groups(id)
					);");
				self::$phylobyteDB->exec("
					CREATE TABLE IF NOT EXISTS p_plugins(
						id INTEGER PRIMARY KEY " . $autoIncrementKeyword . ",
						name VARCHAR (256) NOT NULL UNIQUE,
						weight INTEGER,
						enabled TEXT,
						available TEXT
					);");
				self::$phylobyteDB->exec("
					CREATE TABLE IF NOT EXISTS p_pluginaccess(
						id INTEGER PRIMARY KEY " . $autoIncrementKeyword . ",
						pid INTEGER,
						gid INTEGER,
						uid INTEGER,
							FOREIGN KEY (pid) REFERENCES p_plugins(id) ON DELETE CASCADE,
							FOREIGN KEY (gid) REFERENCES p_groups(id) ON DELETE CASCADE,
							FOREIGN KEY (uid) REFERENCES p_users(id) ON DELETE CASCADE 
					);");
				$getRows = self::$phylobyteDB->prepare("SELECT COUNT(*) FROM p_groups");
				$getRows->execute();
				$numRows = $getRows->fetchAll();
				if($numRows[0][0] == 0){
				if(self::$phylobyteDB->exec("
					INSERT INTO p_groups (name, description)
					VALUES ('admin', 'Phylobyte default administrator group');") &&
					self::$phylobyteDB->exec("
					INSERT INTO p_users (username, status, name)
					VALUES ('admin', 'override', 'Administrator');") &&
					self::$phylobyteDB->exec("
					INSERT INTO p_memberships (userid, groupid)
					VALUES ('1', '1');")
					){
						self::messageAddDebug('Initialized Phylobyte User Tables');
					}
				}
			}catch(PDOException $e){
				self::messageAddError('Database table creation failed: '.$e->getMessage());
			}
			$this->updatePlugins(); // Only update plugins if DB is connected
		} else {
			self::messageAddError('Database not connected. Cannot update plugins or perform other DB operations.');
		}

		// Now handle login and page building based on login status
		$loggedIn = $this->login(); // Call login and store its result

		if($loggedIn){
			if($this->checkAdmin()){
				$this->pageBuild();
				$this->navBuild();
			}else{
				//you're not an admin!
				// header('Location: /'); // Commenting out redirect for debugging
				$this->pageUser();
				$this->navUser();
				self::messageAddError('You do not have administrative privileges to access this section.');
			}
		} else {
			// Login failed. loginform.php has already set $this->pageArea and $this->docArea.
			// We need to ensure navArea is also set, perhaps to a minimal state or empty.
			// If loginform.php is included, it doesn't set navigationArea.
			// So, we should explicitly set it to an empty string or a minimal nav.
			self::$navigationArea = ''; // Or a minimal navigation for non-logged-in users
			self::$breadcrumbs = ''; // Clear breadcrumbs if not logged in
			// The pageArea and docArea are already populated by loginform.php
		}
		self::messageAddDebug('__construct() finished. pageArea length: ' . strlen((string)$this->pageArea) . ', docArea length: ' . strlen((string)$this->docArea) . ', navigationArea length: ' . strlen((string)self::$navigationArea));
	}

	public function handle_error($errno, $errstr, $errfile, $errline) {
		$error_message = "<b>Error:</b> [$errno] $errstr<br><b>File:</b> $errfile<br><b>Line:</b> $errline";
		switch ($errno) {
			case E_USER_ERROR:
			case E_ERROR:
			case E_RECOVERABLE_ERROR:
				self::messageAddError($error_message);
				break;
			case E_USER_WARNING:
			case E_WARNING:
				self::messageAddAlert($error_message);
				break;
			case E_USER_NOTICE:
			case E_NOTICE:
			case E_USER_DEPRECATED:
			case E_DEPRECATED:
				self::messageAddAlert($error_message);
				break;
			default:
				self::messageAddDebug($error_message);
				break;
		}
		/* Don't execute PHP internal error handler */
		return true;
	}

	static function messageStamp(){
		//this needs to use messageStampBase, and not the time directly
		return $GLOBALS['MESSAGESTAMPBASE'].':'.++$GLOBALS['MESSAGESTAMPITR'];
	}

	function updatePlugins(){
		if (self::$phylobyteDB === null) { // Added null check
			self::messageAddDebug('Database not connected, cannot update plugins.');
			return;
		}
		self::$phylobyteDB->exec("
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

				$name = self::$phylobyteDB->quote($pluginName);
				$weight = self::$phylobyteDB->quote($pluginNumber);

				self::$phylobyteDB->exec("
					INSERT INTO p_plugins (name, weight, enabled, available)
					VALUES ($name, $weight, 'true', 'true') ON CONFLICT(name) DO UPDATE SET weight=$weight, available='true';");

			}
		}

		//delete any still unavailable
		self::$phylobyteDB->exec("
				DELETE FROM p_plugins
				WHERE available='false';");
	}

	function login(){
		//do logout
		if(isset($_REQUEST['phylobyte']) && $_REQUEST['phylobyte'] == 'logout'){
			session_destroy();
			session_start();
			self::messageAddNotification('You are now logged out');
		}

		// Attempt to load user info if session loginid exists and DB is connected
		if(isset($_SESSION['loginid']) && self::$phylobyteDB !== null){
			try {
				$userquery = self::$phylobyteDB->prepare("SELECT * FROM p_users WHERE id='{$_SESSION['loginid']}';");
				$userquery->execute();
				$queryResults = $userquery->fetchAll();
				if (!empty($queryResults)) {
					self::$sessionUserInfo = $queryResults[0];
				} else {
					// User ID from session not found in DB, invalidate session
					unset($_SESSION['loginid']);
					self::messageAddError('Session user not found in database. Please log in again.');
				}
			} catch (PDOException $e) {
				self::messageAddError('Database error during session verification: '.$e->getMessage());
				unset($_SESSION['loginid']); // Invalidate session if DB error
			}
		} elseif (isset($_SESSION['loginid']) && self::$phylobyteDB === null) {
			// If session loginid exists but DB is not connected, invalidate session
			unset($_SESSION['loginid']);
			self::messageAddError('Database not connected. Cannot verify existing session. Please log in.');
		}


		// If not logged in (or session invalidated), or status is 'override', include loginform.php
		if(!isset($_SESSION['loginid']) || (isset(self::$sessionUserInfo['status']) && self::$sessionUserInfo['status'] == 'override')){
			include('loginform.php');
			self::messageAddDebug('loginform.php included. pageArea length: ' . strlen((string)$this->pageArea) . ', docArea length: ' . strlen((string)$this->docArea));
			if(isset($accountverify) && $accountverify == 'success'){
				self::messageAddDebug('Login method returning true (accountverify success).');
				return true;
			}else{
				self::messageAddDebug('Login method returning false (accountverify not success).');
				return false;
			}
		}else{
			// If we reach here, it means $_SESSION['loginid'] is set, DB is connected,
			// and user info was successfully loaded (not override status).
			self::messageAddDebug('Login method returning true (session loginid exists and verified).');
			return true;
		}
	}

	function checkAdmin(){
		if (self::$phylobyteDB === null) { // Added null check
			self::messageAddDebug('Database not connected, cannot check admin status.');
			return false;
		}
		if(!isset($_SESSION['loginid'])) return false;
		$adminquery = self::$phylobyteDB->prepare("SELECT * FROM p_memberships WHERE userid='{$_SESSION['loginid']}' AND groupid='1';");
		$adminquery->execute();
		$adminqueryArray = $adminquery->fetchAll();
			if(count($adminqueryArray) > 0){
				return true;
			}
		return false;
	}

	function navBuild(){
		if (self::$phylobyteDB === null) { // Added null check
			self::messageAddDebug('Database not connected, cannot build navigation.');
			return false;
		}
		$MS = new oi_mobilesupport;
		self::$navigationArea = ''; // Initialize to empty string to prevent any potential accumulation

		if(!$MS->useMobile()){
			// Left-aligned navigation items (Home, Plugins)
			self::$navigationArea.='<ul class="left-nav">'; // Added a class for potential styling
			self::$navigationArea.='<li><a href="?">Home</a></li>';

			// Plugin generation loop
			$pluginQuery = self::$phylobyteDB->prepare("
				SELECT * FROM p_plugins WHERE enabled='true' ORDER BY weight;
			");
			$pluginQuery->execute();
			$pluginArray = $pluginQuery->fetchAll(PDO::FETCH_ASSOC);

			foreach($pluginArray as $plugin) {
				if(is_dir('../plugins/'.$plugin['weight'].' '.$plugin['name'].'.p') ){
					$pluginDir = $plugin['weight'].' '.$plugin['name'].'.p';
					$pluginName = $plugin['name'];
					if(is_file('../plugins/'.$pluginDir.'/'.$pluginName.'.php')){
						self::$navigationArea.='<li><a href="?plugin='.substr($pluginDir, 0, -2).'">'.$pluginName;
							$currentPluginDirArray = scandir('../plugins/'.$pluginDir);
							$functionsArray = null;
							foreach($currentPluginDirArray as $possibleFunction) {
								if(substr($possibleFunction, -4) == '.php' && $possibleFunction != $pluginName.'.php'){
									$functionsArray[] = substr($possibleFunction, 0, -4);
								}
							}
							if(is_array($functionsArray) && sizeof($functionsArray) > 0){
								self::$navigationArea.='&hellip;</a>';
								self::$navigationArea.='<ul>';
								foreach($functionsArray as $function) {
									self::$navigationArea.='<li><a href="?plugin='.substr($pluginDir, 0, -2).'&amp;function='.str_replace('&', '%26', $function).'">'.trim(preg_replace('#^\d+#', '', $function)).'</a></li>';
								}
								self::$navigationArea.='</ul>';
							}else{
								self::$navigationArea.='</a>';
							}
						self::$navigationArea.='</li>';
					}
				}
			}
			self::$navigationArea.='</ul>'; // Close the left-aligned UL

			// Right-aligned navigation items (View Website, Welcome)
			if($_SERVER['QUERY_STRING'] == ''){
				$logoutQueryString = '?phylobyte=logout';
			}else{
				$logoutQueryString = '?'.$_SERVER['QUERY_STRING'].'&phylobyte=logout';
			}
			self::$navigationArea.='
			<ul class="right-nav" style="float: right;">
				<li>
					<a href="../">View Website</a>
				</li>
				<li><a style="min-width: 10em; text-align: center;">Welcome, '.(isset(self::$sessionUserInfo['name']) ? self::$sessionUserInfo['name'] : '').' '.(isset(self::$sessionUserInfo['lname']) ? self::$sessionUserInfo['lname'] : '').'</a>
					<ul style="float: right; min-width: 100%;">
						<li><a href="?phylobyte=account">My Account</a></li>
						<li><a href="?phylobyte=settings">Settings</a></li>
						<li><a href="'.$logoutQueryString.'">Log Out</a></li>
					</ul>
				</li>
			</ul>
			';
		}else{
			// Mobile navigation
			self::$mobileNav = '
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
		if (self::$phylobyteDB === null) { // Added null check
			self::messageAddDebug('Database not connected, cannot build user navigation.');
			return false;
		}
		$MS = new oi_mobilesupport;
		self::$navigationArea = ''; // Initialize to empty string

		if(!$MS->useMobile()){
			// Left-aligned navigation items (Home)
			self::$navigationArea.='<ul class="left-nav">'; // Added a class for potential styling
			self::$navigationArea.='<li><a href="?">Home</a></li>';
			self::$navigationArea.='</ul>'; // Close the left-aligned UL

			// Right-aligned navigation items (View Website, Welcome)
			if($_SERVER['QUERY_STRING'] == ''){
				$logoutQueryString = '?';
			}else{
				$queryString = '?'.$_SERVER['QUERY_STRING'];
			}
			self::$navigationArea.='
			<ul class="right-nav" style="float: right;">
				<li>
					<a href="../">View Website</a>
				</li>
				<li><a style="min-width: 10em; text-align: center;">Welcome, '.(isset(self::$sessionUserInfo['name']) ? self::$sessionUserInfo['name'] : '').' '.(isset(self::$sessionUserInfo['lname']) ? self::$sessionUserInfo['lname'] : '').'</a>
					<ul style="float: right; min-width: 100%;">
						<li><a href="?phylobyte=account">My Account</a></li>
						<li><a href="'.$logoutQueryString.'&phylobyte=logout">Log Out</a></li>
					</ul>
				</li>
			</ul>
			';
		}else{
			// Mobile navigation
			self::$mobileNav = '
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
		$pluginName = trim(preg_replace('#^\d+#', '', substr($pluginDir, 0, -2)));

		foreach($pluginDirArray as $possibleFunction) {
			if(substr($possibleFunction, -4) == '.php'
			&& $possibleFunction != $pluginName.'.php'){
				$this->pluginFunctions[] = $possibleFunction;
			}
		}

		if($return == 'array'){
			return $this->pluginFunctions;
		}else{
			$autoIndex = ''; // Initialize $autoIndex here
			if(is_array($this->pluginFunctions)){
				foreach($this->pluginFunctions as $function){
					$functionName = substr(trim(preg_replace('#^\d+#', '', stripslashes($function))), 0, -4);

					$item = str_replace('%F%', str_replace('&', '%26',substr($function, 0, -4)), $return);
					$item = str_replace('%Fn%', $functionName, $item);
					if(is_file('../plugins/'.stripslashes($_GET['plugin']).'.p/'.substr($function, 0, -3).'dsc')){
						$item = str_replace('%Fd%', stripslashes(file_get_contents('../plugins/'.$pluginDir.'/'.substr($function, 0, -3).'dsc')),$item);
					}else{
						$item = str_replace('%Fd%', '',$item);
					}
					$item = str_replace('%P%', $_GET['plugin'], $item);
					$item = str_replace('%Pn%', trim(preg_replace('#^\d+#', '', stripslashes($_GET['plugin']))), $item);
					$item = str_replace('<p></p>', '',$item);
					$autoIndex.=$item;
				}
			}
			$autoHeader = str_replace('%P%', $_GET['plugin'], $header);
			$autoHeader = str_replace('%Pn%', trim(preg_replace('#^\d+#', '', stripslashes($_GET['plugin']))), $autoHeader);
			$this->pageArea.=$autoHeader.(isset($autoIndex) ? $autoIndex : '');
		}
	}

	function pageBuild(){
		//build the page based on the link, if nothing else, include home.
		if(!isset($_GET['plugin']) && !isset($_GET['phylobyte'])){
			include('home.php');
		}elseif(isset($_GET['phylobyte']) && $_GET['phylobyte'] == 'account'){
			include('account.php');
			return true;
		}elseif(isset($_GET['phylobyte']) && $_GET['phylobyte'] == 'settings'){
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
				self::$headArea.='
		<link href="../plugins/'.$pluginDir.'/'.$pluginName.'.css" rel="stylesheet" type="text/css" />
				';
			}

			//modify the title
			$this->pageTitle.=' | '.$pluginName;

			//make some breadcrumbs
			self::$breadcrumbs.='<a href="?">Home</a> &raquo; <a href="?plugin='.substr($pluginDir, 0, -2).'">'.$pluginName.'</a>';



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
				self::$breadcrumbs.=' &raquo; <a href="?plugin='.substr($pluginDir, 0, -2).'&amp;function='.str_replace('&', '%26', $function).'">'.trim(preg_replace('#^\d+#', '', $function)).'</a>';

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
		}elseif(isset($_GET['phylobyte']) && $_GET['phylobyte'] == 'account'){
			include('account.php');
			return true;
		}
		return true;
	}

	function build_finish(){
		if($this->directMessages != true){
			$messages = $GLOBALS['MESSAGES']->pullquery('%v%', "SELECT * FROM __REGISTRY____pmessages WHERE mykey LIKE '{$GLOBALS['MESSAGESTAMPBASE']}:%' AND value NOT LIKE '#d.%';");
			if ($messages !== false) { // Check if pullquery returned a valid result
				$this->messageArea = $messages;
			}
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