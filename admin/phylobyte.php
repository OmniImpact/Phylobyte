<?php

class phylobyte{

	public $messageStampBase;
	public $messageStampItr = 0;
	public $messageArea;
	public $navigationArea;
	public $mobileNav;
	public $breadcrumbs;
	public $pageArea;
	public $docArea;
	public $headArea;
	public $pageTitle = 'Phylobyte CMS';
	public $pluginFunctions;
	
	static $sessionUserInfo;
	
	static $phylobyteDB;

	function __construct(){
		$this->messageStampBase = time();
		try{
			include('../data/database.vars.php');
			$this->phylobyteDB = new PDO('mysql:host=localhost;dbname='.$DBMASTERDB, $DBUSER, $DBPASSWORD);
		}catch(PDOException $e){
			$this->messageAddDebug('Failed to open database: '.$e);
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
					password TEXT,
					passwordtype TEXT,
					status TEXT,
					statusvalue TEXT,
					super TEXT,
					email TEXT,
					primarygroup TEXT,
					passwordhash TEXT,
					fname TEXT,
					lname TEXT,
					personalphone TEXT,
					publicphone TEXT,
					description TEXT
				);");
				//soon i will delete everything after statusvalue
			$this->phylobyteDB->exec("
				CREATE TABLE IF NOT EXISTS p_userinfo(
					id INTEGER PRIMARY KEY AUTO_INCREMENT,
					uid TEXT,
					fname TEXT,
					mname TEXT,
					lname TEXT,
					nickname TEXT,
					email TEXT,
					personalnum TEXT,
					publicnum TEXT,
					description TEXT,
					joindate TEXT,
					lastused TEXT,
					adminnote TEXT
				);");
			$this->phylobyteDB->exec("
				CREATE TABLE IF NOT EXISTS p_memberships(
					id INTEGER PRIMARY KEY AUTO_INCREMENT,
					userid TEXT,
					groupid TEXT,
					lastused TEXT,
					joined TEXT
				);");
			$this->phylobyteDB->exec("
				CREATE TABLE IF NOT EXISTS p_plugins(
					id INTEGER PRIMARY KEY AUTO_INCREMENT,
					groupid TEXT,
					pluginname TEXT
				);");
			$getRows = $this->phylobyteDB->prepare("SELECT COUNT(*) FROM p_groups");
			$getRows->execute();
			$numRows = $getRows->fetchAll();
			if($numRows[0][0] == 0){
			if($this->phylobyteDB->exec("
				INSERT INTO p_groups (name, description)
				VALUES ('admin', 'Phylobyte default administrator group');") &&
				$this->phylobyteDB->exec("INSERT INTO p_users (username, status, primarygroup, fname)
				VALUES ('admin', 'override', '1', 'Administrator');")){
					$this->messageAddDebug('Initialized Phylobyte User Tables');
				}
			}
		}catch(PDOException $e){}
		if($this->login()){
			$this->pageBuild();
			$this->navBuild();
		}
	}

	function messageStamp(){
		$this->messageStampItr++;
		return $this->messageStampBase.':'.$this->messageStampItr;
	}

	function login(){
		//do logout
		if($_REQUEST['phylobyte'] == 'logout'){
			unset($_SESSION['loginid']);
			$this->messageAddNotification('You are now logged out');
		}
		//do login
		if(isset($_SESSION['loginid'])){
			$userquery = $this->phylobyteDB->prepare("SELECT * FROM p_users WHERE id='{$_SESSION['loginid']}';");
			$userquery->execute();
			$queryResults = $userquery->fetchAll();
			$this->sessionUserInfo = $queryResults[0];
		}
		if(!isset($_SESSION['loginid']) || $this->sessionUserInfo['status'] == 'override'){
			include('loginform.php');
			if($accountverify == 'success') return true;
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
	
	function navBuild(){
		$MS = new oi_mobilesupport;
		$this->navigationArea.='
		<ul>';

		if(!$MS->useMobile()){
			$this->navigationArea.='<li><a href="?">Home</a></li>';
		}

		//ok, we are ready to build some navigation
		$pluginDirArray = scandir('../plugins');

		foreach($pluginDirArray as $possiblePlugin) {
			if(is_dir('../plugins/'.$possiblePlugin) && substr($possiblePlugin, -3) == '.on'){
				//now we make sure the plugin has the minimal requirements
				$pluginDir = $possiblePlugin;
				$pluginName = trim(preg_replace('#^\d+#', '', substr($possiblePlugin, 0, -3)));
				if(is_file('../plugins/'.$pluginDir.'/'.strtolower($pluginName).'.php') && is_file('../plugins/'.$pluginDir.'/'.$pluginName.'.php')){
					//we have the minimal plugin setup, so we can now generate navigation
					$this->navigationArea.='<li><a href="?plugin='.substr($pluginDir, 0, -3).'">'.$pluginName;
						//if there is just one other page, we must make subnav.
						$currentPluginDirArray = scandir('../plugins/'.$pluginDir);
						$functionsArray = null;
						foreach($currentPluginDirArray as $possibleFunction) {
							if(substr($possibleFunction, -4) == '.php' && $possibleFunction != $pluginName.'.php' && $possibleFunction != strtolower($pluginName).'.php'){
								$functionsArray[] = substr($possibleFunction, 0, -4);
							}
						}
						if(sizeof($functionsArray) > 0){
							$this->navigationArea.='&hellip;</a>';
							$this->navigationArea.='<ul>';
							foreach($functionsArray as $function) {
							    $this->navigationArea.='<li><a href="?plugin='.substr($pluginDir, 0, -3).'&amp;function='.$function.'">'.trim(preg_replace('#^\d+#', '', $function)).'</a></li>';
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
			$this->navigationArea.='
			</ul>
			<ul style="float: right;">
				<li>
					<a href="../">View Website</a>
				</li>
				<li><a style="min-width: 10em; text-align: center;">Welcome, '.$this->sessionUserInfo['fname'].' '.$this->sessionUserInfo['lname'].'</a>
					<ul style="float: right; min-width: 100%;">
						<li><a href="?phylobyte=account">My Account</a></li>
						<li><a href="?phylobyte=settings">Settings</a></li>
						<li><a href="?phylobyte=logout">Log Out</a></li>
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
		$pluginDir = stripslashes($_GET['plugin'].'.on');
		$pluginDirArray = scandir('../plugins/'.$pluginDir);
		$pluginName = substr(trim(preg_replace('#^\d+#', '', $pluginDir)), 0, -3);
		
		foreach($pluginDirArray as $possibleFunction) {
			if(substr($possibleFunction, -4) == '.php' 
			&& $possibleFunction != $pluginName.'.php'
			&& $possibleFunction != strtolower($pluginName).'.php'){
				$this->pluginFunctions[] = $possibleFunction;
			}
		}
		
		if($return == 'array'){
			return $this->pluginFunctions;
		}else{
			foreach($this->pluginFunctions as $function){
				$functionName = substr(trim(preg_replace('#^\d+#', '', stripslashes($function))), 0, -4);

				$item = str_replace('%F%', substr($function, 0, -4), $return);
				$item = str_replace('%Fn%', $functionName, $item);
				if(is_file('../plugins/'.stripslashes($_GET['plugin']).'.on/'.substr($function, 0, -3).'dsc')){
					$item = str_replace('%Fd%', stripslashes(file_get_contents('../plugins/'.stripslashes($_GET['plugin']).'.on/'.substr($function, 0, -3).'dsc')),$item);
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
			$pluginDir = stripslashes($_GET['plugin']).'.on';
			$pluginName = trim(preg_replace('#^\d+#', '', stripslashes($_GET['plugin'])));
			//include the class
			include_once('../plugins/'.$pluginDir.'/'.strtolower($pluginName).'.php');
			//possibly include the css
			if(is_file('../plugins/'.$pluginDir.'/'.strtolower($pluginName).'.css')){
				$this->headArea.='
		<link href="../plugins/'.$pluginDir.'/'.strtolower($pluginName).'.css" rel="stylesheet" type="text/css" />
				';
			}
			
			//modify the title
			$this->pageTitle.=' | '.$pluginName;

			//make some breadcrumbs
			$this->breadcrumbs.='<a href="?">Home</a> &raquo; <a href="?plugin='.substr($pluginDir, 0, -3).'">'.$pluginName.'</a>';

			
			
			//check the function
			if(!isset($_GET['function'])){
				//we include the default
				$includePlugin = '../plugins/'.$pluginDir.'/'.$pluginName.'.php';
				if(is_file('../plugins/'.$pluginDir.'/'.$pluginName.'.html')){
					$this->docArea.=stripslashes(file_get_contents('../plugins/'.$pluginDir.'/'.$pluginName.'.html'));
				}
			}else{
				//we include the function
				$function = stripslashes($_GET['function']);
				$this->pageTitle.=' | '.trim(preg_replace('#^\d+#', '', $function));
				$this->breadcrumbs.=' &raquo; <a href="?plugin='.substr($pluginDir, 0, -3).'&amp;function='.$function.'">'.trim(preg_replace('#^\d+#', '', $function)).'</a>';
				$includePlugin = '../plugins/'.$pluginDir.'/'.$function.'.php';
				if(is_file('../plugins/'.$pluginDir.'/'.$function.'.html')){
					$this->docArea=null;
					$this->docArea.=stripslashes(file_get_contents('../plugins/'.$pluginDir.'/'.$function.'.html'));
				}
			}
			
			include_once($includePlugin);
		}
		
		
		return true;
	}
	
	function build_finish(){
		$this->messageArea = $GLOBALS['MESSAGES']->pullquery('%v%', "SELECT * FROM __REGISTRY____pmessages WHERE mykey LIKE '{$this->messageStampBase}%';");
		$this->messageArea = str_replace('#e.', '<div class="error">', $this->messageArea);
		$this->messageArea = str_replace('#a.', '<div class="alert">', $this->messageArea);
		$this->messageArea = str_replace('#n.', '<div class="notification">', $this->messageArea);
		$this->messageArea = str_replace('#d.', '<div class="debug">', $this->messageArea);
		
		$this->messageArea = str_replace('##.', '</div>', $this->messageArea);
	}
	
	function messageAddAlert($alert){
		$GLOBALS['MESSAGES']->push($this->messageStamp(), '#a.'.$alert.'##.');
	}
	function messageAddError($error){
		$GLOBALS['MESSAGES']->push($this->messageStamp(), '#e.'.$error.'##.');
	}
	function messageAddNotification($notice){
		$GLOBALS['MESSAGES']->push($this->messageStamp(), '#n.'.$notice.'##.');
	}
	function messageAddDebug($debug){
		$GLOBALS['MESSAGES']->push($this->messageStamp(), '#d.'.$debug.'##.');
	}
}

?>