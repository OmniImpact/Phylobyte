<?php
session_start();

function siteURL(){
	$protocol = ((!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] != 'off') || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
	$domainName = $_SERVER['HTTP_HOST'];
	return $protocol.$domainName.substr($_SERVER['PHP_SELF'], 0, strpos($_SERVER['PHP_SELF'], "/plugins"));
}

if(isset($_SESSION['loginid'])){
	if(isset($_POST['image']) && $_POST['image'] != ''){
	
		$filePath = '../../../data/images/'.$_POST['image'];
		$imageInfo = getimagesize($filePath);
		
		function filesize_formatted($path){
			$size = filesize($path);
			$units = array( 'B', 'KB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB');
			$power = $size > 0 ? floor(log($size, 1024)) : 0;
			return number_format($size / pow(1024, $power), 2, '.', ',') . ' ' . $units[$power];
		}
		
		echo('
		<fieldset style="width: 100%;">
			<legend>Image Options</legend>
		<form style="text-align: center; margin: -1px;">
		<div title="'.basename($filePath).'">
		<div class="imagetitle" style="width: 100%; overflow: hidden;">
		'.basename($filePath).'</div>
		<img src="'.siteURL().'/plugins/image.php?source='.siteURL().'/data/images/'.$_POST['image'].'&amp;size=460" class="paneleft-image-preview-thumb" />
		</div>
		<div style="text-align: left;">
		
			<hr style="margin: 1em; margin-left: .5em;"/>
			
			<div style="font-size: 90%;">
				Image Type: '.$imageInfo['mime'].'<br/>
				Original Width: '.$imageInfo[0].' pixels<br/>
				Original Height: '.$imageInfo[1].' pixels<br/>
				Last Modified: '.date('m-d-Y', filemtime($filePath)).'<br/>
				Size: '.filesize_formatted($filePath).'<br/>
			</div>
			
			<hr style="margin: 1em; margin-left: .5em;"/>
			
			<div style="line-height: 140%;">
				<a href="'.siteURL().'/data/images/'.$_POST['image'].'"
				title="'.basename($filePath).'" target="_blank">
				Original</a>&nbsp;<i class="icon-external-link" style="font-size: 80%;">&nbsp;</i><br/>
				
				<a href="'.siteURL().'/plugins/image.php?source='.siteURL().'/data/images/'.$_POST['image'].'&amp;size=90"
				title="'.basename($filePath).'" target="_blank">Thumb -
				Small&nbsp;(90)</a>&nbsp;<i class="icon-external-link" style="font-size: 80%;">&nbsp;</i><br/>
				
				<a href="'.siteURL().'/plugins/image.php?source='.siteURL().'/data/images/'.$_POST['image'].'&amp;size=160"
				title="'.basename($filePath).'" target="_blank">Thumb -
				Medium&nbsp;(160)</a>&nbsp;<i class="icon-external-link" style="font-size: 80%;">&nbsp;</i><br/>
				
				<a href="'.siteURL().'/plugins/image.php?source='.siteURL().'/data/images/'.$_POST['image'].'&amp;size=280"
				title="'.basename($filePath).'" target="_blank">Embed -
				Small&nbsp;(280)</a>&nbsp;<i class="icon-external-link" style="font-size: 80%;">&nbsp;</i><br/>
				
				<a href="'.siteURL().'/plugins/image.php?source='.siteURL().'/data/images/'.$_POST['image'].'&amp;size=460"
				title="'.basename($filePath).'" target="_blank">Embed -
				Medium&nbsp;(460)</a>&nbsp;<i class="icon-external-link" style="font-size: 80%;">&nbsp;</i><br/>
				
				<a href="'.siteURL().'/plugins/image.php?source='.siteURL().'/data/images/'.$_POST['image'].'&amp;size=920"
				title="'.basename($filePath).'" target="_blank">Embed -
				Large&nbsp;(920)</a>&nbsp;<i class="icon-external-link" style="font-size: 80%;">&nbsp;</i><br/>
			</div>
			
		</div>
		</form>
		</fieldset>
			
		<fieldset style="width: 100%; margin-top: .5em;">
		<legend>Rename Image</legend>
		<form style="font-size: 90%; padding: .5em; margin: -1px;" action="?'.$_POST['TARGET_QUERY_STRING'].'" method="POST">
			<input type="hidden" name="originalname" value="'.basename($filePath).'" />
			<input type="text" name="newname" value="'.basename($filePath).'" style="width:90%;"/>
			<input type="submit" name="image_rename" style="margin: 0;" value="Apply"/>
			<div class="ff">&nbsp;</div>
		</form>
		</fieldset>
		
		');
		
	}else{
		echo('
		<fieldset style="width: 100%;">
			<legend>Image Options</legend>
		<form style="text-align: center; margin: -1px;">
		No image selected.<br/>
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