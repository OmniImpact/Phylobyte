<?php
session_start();

function siteURL(){
	$protocol = ((!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] != 'off') || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
	$domainName = $_SERVER['HTTP_HOST'];
	return $protocol.$domainName.substr($_SERVER['PHP_SELF'], 0, strpos($_SERVER['PHP_SELF'], "/admin/index.php"));
}
function filesize_formatted($path){
	$size = filesize($path);
	$units = array( 'B', 'KB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB');
	$power = $size > 0 ? floor(log($size, 1024)) : 0;
	return number_format($size / pow(1024, $power), 2, '.', ',') . ' ' . $units[$power];
}

if(isset($_SESSION['loginid'])){
	if(isset($_POST['file']) && $_POST['file'] != ''){
	
		$filePath = '../../'.$_POST['file'];
		$fileStat = stat($filePath);
		$fileType = mime_content_type($filePath);
		
		echo('
		<fieldset style="width: 100%;">
			<legend>File Options</legend>
		<form style="text-align: center; margin: -1px; overflow: hidden;">
		<div>
			<img src="'.$_POST['iconURL'].'" />
		</div>
		<strong>'.basename($filePath).'</strong><br/>
		<span style="font-size: 70%; color: #555;"
		title="'.dirname($_POST['file']).'">'.dirname($_POST['file']).'</span>
		<hr/>
		<p style="font-size: 80%; text-align: left;">
		<span title="'.$fileType.'" style="font-size: 100%;">Type: '.$fileType.'</span><br/>
		File Size: '.filesize_formatted($filePath).'<br/>
		Last Modified: '.date('m-d-Y', $fileStat['mtime']).'<br/>
		</p>
		</form>
		</fieldset>
		
		<fieldset style="width: 100%; margin-top: .5em;">
		<legend>Rename File</legend>
		<form style="font-size: 90%; padding: .5em; margin: -1px;" action="?'.$_POST['TARGET_QUERY_STRING'].'" method="POST">
			<input type="hidden" name="originalname" value="'.basename($filePath).'" />
			<input type="text" name="newname" value="'.basename($filePath).'" style="width:90%;"/>
			<input type="submit" name="fileaction_rename" style="margin: 0;" value="Apply"/>
			<div class="ff">&nbsp;</div>
		</form>
		</fieldset>
		
		<fieldset style="width: 100%; margin-top: .5em;">
		<legend>Delete File</legend>
		<form style="font-size: 90%; padding: .5em; margin: -1px;" action="?'.$_POST['TARGET_QUERY_STRING'].'" method="POST">
			<input type="hidden" name="selectedfile" value="'.basename($filePath).'" />
			<div class="destructive" style="text-align: center;">
			This action can not<br/> be reversed.
			<div class="ff">&nbsp;</div><br/>
			<input type="submit" name="fileaction_delete" style="margin: 0 1em; width: 50%;" value="Delete Now"/>
			<div class="ff">&nbsp;</div>
			</div>
		</form>
		</fieldset>
		
		
		');
		
	}else{
		echo('
		<fieldset style="width: 100%;">
			<legend>File Options</legend>
		<form style="text-align: center; margin: -1px;">
		No file selected.<br/>
		<i class="icon-info-sign" style="font-size: 300%;"></i>
		<br/>Click the icon to show further information.
		</form>
		</fieldset>
		');
	}
}else{
	die();
}
?>