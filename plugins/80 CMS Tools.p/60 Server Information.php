<?php

if($_GET['phpinfo'] != true){

	$sysinfo = posix_uname();

	$maxpost = ini_get('post_max_size');
	$maxup = ini_get('upload_max_filesize');
	if($maxup <= $maxpost){$maxsize = $maxup;}else{$maxsize = $maxpost;}

	foreach(get_loaded_extensions() as $extension){
		$extensions.=$extension.', ';
	}
	$extensions = substr($extensions, 0, -2);


	$config['remoteurlopen'] = (ini_get('allow_url_fopen')) ? 'Yes' : 'No';
	$config['remoteurlinclude'] = (ini_get('allow_url_include')) ? 'Yes' : 'No';
	$config['sessionlife'] = ini_get('session.gc_maxlifetime')/60;

	$queryString = urldecode($_SERVER['QUERY_STRING']);

	$this->pageArea.="

	<table style=\"width: 100%; border-spacing: 10px; overflow: hide;\">
	<tr style=\"background-color: #ddd;\">
		<th style=\"width: 35%; padding: 4pt;\">Attribute</th><th>Value</th>
	</tr>
	<tr>
		<td><b>Server</b></td><td></td>
	</tr>
	<tr>
		<td>Server Name</td><td>{$sysinfo['nodename']}</td>
	</tr>
	<tr>
		<td>Operating System</td><td>{$sysinfo['sysname']}</td>
	</tr>
	<tr>
		<td>OS Version</td><td>{$sysinfo['release']}</td>
	</tr>
	<tr>
		<td>Server Type</td><td>{$sysinfo['machine']}</td>
	</tr>
	<tr>
		<td>Server Software</td><td>{$_SERVER['SERVER_SOFTWARE']}</td>
	</tr>
	<tr>
		<td><b>Headers</b></td><td></td>
	</tr>
	<tr>
		<td>Detected Browser</td><td>{$_SERVER['HTTP_USER_AGENT']}</td>
	</tr>
	<tr>
		<td>Location</td><td>{$_SERVER['HTTP_HOST']}</td>
	</tr>
	<tr>
		<td>Query String</td><td>{$_SERVER['QUERY_STRING']}</td>
	</tr>
	<tr>
		<td>Decoded Query</td><td>$queryString</td>
	</tr>
	<tr>
		<td>Detected IP</td><td>{$_SERVER['REMOTE_ADDR']}</td>
	</tr>
	<tr>
		<td><b>Configuration</b></td><td></td>
	</tr>
	<tr>
		<td>PHP Version</td><td>".phpversion()."</td>
	</tr>
	<tr>
		<td>Loaded Extensions</td><td>{$extensions}</td>
	</tr>
	<tr>
		<td>Maximum Upload</td><td>{$maxsize}</td>
	</tr>
	<tr>
		<td>Session Timeout</td><td>{$config['sessionlife']} minutes</td>
	</tr>
	<tr>
		<td>Remote URL Open/Include</td><td>{$config['remoteurlopen']}/{$config['remoteurlinclude']}</td>
	</tr>

	<tr>
		<td><b>View Advanced Information</b></td><td><a href=\"?{$_SERVER['QUERY_STRING']}&amp;phpinfo=true\">Open phpinfo()</a></td>
	</tr>

	</table>

	";
}elseif ($_GET['phpinfo'] == true) {

	$this->messageAddAlert('The phpinfo() function provides a lot of information. Some of it could be used to attack your website. Please be careful who you give access to this information.');
    ob_start();
		phpinfo();
		$php_info = ob_get_contents();
	ob_end_clean();

	$this->breadcrumbs.=' &raquo; phpinfo()';

	$this->pageArea.='<a href="?plugin=80%20CMS%20Tools&function=60%20Server%20Information">
	&larr; Back to Basic Information</a><br/><br/><hr/><br/><br/>';

	$this->pageArea.='
		<div id="phpinfo" style="width: 80%; overflow: hidden; margin-left: 10%; line-height: 160%;">
		'.str_replace("module_Zend Optimizer", "module_Zend_Optimizer", preg_replace('%^.*<body>(.*)</body>.*$%ms', '$1', $php_info)).'
		</div>';
}

?>