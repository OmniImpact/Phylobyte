<?php

//back to root if requested
if(isset($_POST['fileaction_goroot'])){
	if(isset($_SESSION['fm_location_stack'])){
		unset($_SESSION['fm_location_stack']);
	}
}

//back up a folder if requested
if(isset($_POST['fileaction_goup'])){
	if(isset($_SESSION['fm_location_stack']) && count($_SESSION['fm_location_stack']) > 0){
		array_pop($_SESSION['fm_location_stack']);
	}
}

//back up a folder if requested
if(isset($_POST['fileaction_uptofolder'])){
	for($i = 0; $i < $_POST['fileaction_uptofolder']; $i++){
		array_pop($_SESSION['fm_location_stack']);
	}
}

//if there are no folders in the stack, start it out with the current one
if(!isset($_SESSION['fm_location_stack']) || count($_SESSION['fm_location_stack']) == 0){
	$_SESSION['fm_location_stack']=null;
	$_SESSION['fm_location_stack'][] = '../data/files';
}

//go into a folder
if(isset($_POST['fileaction_gofolder'])){
	$_SESSION['fm_location_stack'][] = $_POST['fileaction_gofolder'];
}

$deleteFolder = null;
if(isset($_POST['fileaction_deletefolder'])){
	$deleteFolder = array_pop($_SESSION['fm_location_stack']);
}

$currentLocation = null;
foreach($_SESSION['fm_location_stack'] as $key => $folder){
	if(is_dir($currentLocation.$folder.'/')){
		$currentLocation.=$folder.'/';
	}else{
		unset($_SESSION['fm_location_stack'][$key]);
	}
}

if($deleteFolder != null){
	if(@rmdir($currentLocation.'/'.$deleteFolder)){
		$this->messageAddNotification('Successfully deleted folder.');
	}else{
		$this->messageAddError('There was a problem deleting the specified folder.');
	}
}

if(isset($_POST['fileaction_rename'])){
	if(is_file($currentLocation.$_POST['originalname'])){
		if(rename($currentLocation.$_POST['originalname'], $currentLocation.$_POST['newname'])){
			$this->messageAddNotification('Successfully renamed file.');
		}else{
			$this->messageAddError('There was a problem renaming the file.');
		}
	}else{
		$this->messageAddAlert('The file you are trying to rename does not exist.');
	}
}

if(isset($_POST['fileaction_delete'])){
	if(is_file($currentLocation.$_POST['selectedfile'])){
		if(@unlink($currentLocation.$_POST['selectedfile'])){
			$this->messageAddNotification('Successfully deleted file.');
		}else{
			$this->messageAddError('There was a problem deleting the file.');
		}
	}else{
		$this->messageAddAlert('The file you are trying to delete does not exist.');
	}
}

if(isset($_POST['fileaction_upload'])){
	if(@move_uploaded_file($_FILES['uploadedfile']['tmp_name'],
		$currentLocation.'/'.basename($_FILES['uploadedfile']['name'])) ){
		$this->messageAddNotification('Successfully uploaded file.');
	}else{
		$this->messageAddError('There was a problem uploading your file.');
	}
}

if(isset($_POST['fileaction_newfolder'])){
	if(@mkdir($currentLocation.$_POST['name']) && chmod($currentLocation.$_POST['name'], 0777) ){
		$this->messageAddNotification('Successfully created new folder.');
	}elseif(is_dir($currentLocation.$_POST['name'])){
		$this->messageAddAlert('That folder already exists.');
	}else{
		$this->messageAddError('There was a problem creating a new folder.');
	}
}

$currentLocationFiles = scandir($currentLocation);

$currentLocationPathList = '';
$currentLocationFoldersArray = null;
$currentLocationFoldersList = null;

$currentLocationPathList.='
<i class="icon-folder-open">&nbsp;</i>
<button type="submit" class="clearbutton" name="fileaction_goroot"><strong>Files Root</strong></button>
';

$level = 0;
if(count($_SESSION['fm_location_stack']) > 1){
	
	/*$currentLocationFoldersList = '
		<div style="border-left: 1px solid gray; margin-left: .5em; padding-left: .5em;">
		<i class="icon-double-angle-up" style="margin-left: 2pt;">&nbsp;</i>
		<button type="submit" class="clearbutton" name="fileaction_goup">Up A Level</button>
		</div>
	';*/
	
	foreach($_SESSION['fm_location_stack'] as $folder){
		$level++;
		if($level > 1){
			$currentLocationPathList.='
			<div><i class="icon-folder-open-alt">&nbsp;</i>
				<button type="submit" class="clearbutton" name="fileaction_uptofolder" value="'.
				(count($_SESSION['fm_location_stack'])-$level).'">
					<strong>'.$folder.'</strong>
				</button>
			</div>
			';
		}
	}
}

foreach($currentLocationFiles as $possibleFolder) {
	if(is_dir($currentLocation.$possibleFolder) && $possibleFolder != '.' && $possibleFolder != '..'){
		$currentLocationFoldersArray[] = $possibleFolder;
		$currentLocationFoldersList.='
		<div style="border-left: 1px solid gray; margin-left: .5em; padding-left: .5em;">
		<i class="icon-folder-close-alt">&nbsp;</i>
		<button type="submit" class="clearbutton" name="fileaction_gofolder" value="'.$possibleFolder.'">
			'.$possibleFolder.'
		</button>
		</div>
		';
	}
}

$currentLocationFilesList = '';
$currentLocationFilesArray = null;

foreach($currentLocationFiles as $possibleFile){
	if(is_file($currentLocation.$possibleFile) && $possibleFile != '.' && $possibleFile != '..'){
		$currentLocationFilesArray[] = $possibleFile;
		$pathInfo = pathinfo($currentLocation.$possibleFile);
		$iconName = $GLOBALS['PCON']->file_get_simple_type($pathInfo['extension']).'.png';
		$iconURL = $GLOBALS['PCON']->siteURL().'/iconsets/filetypes/'.$iconName;
		$lmtime = date('F jS, Y' , filemtime($currentLocation.$possibleFile));
		$fsize = $GLOBALS['PCON']->filesize_formatted($currentLocation.$possibleFile);
		$currentLocationFilesList.='
		<tr>
			<td style="text-align: center; width: 3em;">
				<img src="'.$iconURL.'" style="max-width: 100%;">
			</td>
			<td style="line-height: 100%;">
			<a style="font-size: 75%; font-weight: bold; color: black;"
			href="'.$currentLocation.$possibleFile.'">'.$possibleFile.'</a>
			</td>
			<td style="font-size: 80%; width: 5em; text-align: center;">'.$lmtime.'
			<hr style="margin: 0 5% 0 5%;"/>'.$fsize.'</td>
			<td style="text-align: center;" class="infolinktd">
				<a href="javascript:getFileInfo(\''.addslashes($currentLocation.$possibleFile).'\', \''.addslashes($iconURL).'\');">
					<i class="icon-info-sign">&nbsp;</i>
				</a>
			</td>
		</tr>
		';
	}
}

if(count($currentLocationFilesArray) > 0){
	$currentLocationFilesList = '
	<table class="selTable" style="table-layout:fixed;">
		<tr>
			<th style="width: 3em;">Icon</th><th>Name</th>
			<th style="width: 5em;">Modified
			<hr style="margin: 0 5% 0 5%;"/>
			Size
			</th>
			<th style="width: 3em;">Info</th>
		</tr>
	'.$currentLocationFilesList.'
	</table>
	';
}

$deleteForm = '';
if(count($currentLocationFilesArray) == 0 && count($currentLocationFoldersArray) == 0 ){
	$deleteForm = '
	<div class="alertable" style="text-align: center; margin-bottom: .5em;">
	This folder is empty.
	</div>
	<div class="destructive" style="text-align: center;">
	Delete the current folder?
	<div class="ff">&nbsp;</div>
	<br/>
	<input type="submit" name="fileaction_deletefolder" value="Delete Folder Now" style="min-width: 12em;"/>
	<div class="ff">&nbsp;</div>
	</div>
	';
}

$noFiles = '';
if(count($currentLocationFilesArray) == 0 && count($currentLocationFoldersArray) > 0 ){
	$noFiles = '
	<div class="alertable" style="text-align: center;">
	This folder has no files.<br/>
	If you want to delete this folder<br/>
	first remove the sub-folders.
	</div>
	';
}

$noFolders = '';
if(count($currentLocationFoldersArray) == 0 ){
	$noFolders = '
	<div class="alertable" style="text-align: center; margin-top: .5em;">
	There are no further folders.
	</div>
	';
}

$fileInfoScript = "
<script>

var getFileInfo = function(fileName, iconURL){
	if(typeof fileName == 'undefined'){
		fileName = '';
	}
	if(typeof iconURL == 'undefined'){
		iconURL = '';
	}
	jQuery('#file_info_area').fadeOut('fast', function(){
		jQuery.ajax( {
			url: '" . $GLOBALS['PCON']->siteURL() . "/plugins/40 Web Content.p/ajax/get_file_info.php', 
			async: true,
			data:
			{
				'file': fileName,
				'TARGET_QUERY_STRING': '{$_SERVER['QUERY_STRING']}',
				'iconURL' : iconURL
			},
			type: 'POST',
			dataType: 'html',
			success: function(data) { 
				jQuery('#file_info_area').html(data);
				jQuery('#file_info_area').fadeIn('fast');
			}
		});
	});
}
getFileInfo();

</script>
";

$this->pageArea.='
<div class="fmleft">
	<div class="fmlefti">
		<fieldset style="width: 100%;">
		<legend>Create New Folder</legend>
		<form action="?'.$_SERVER['QUERY_STRING'].'" method="POST">
			<input type="text" name="name" value="'.basename($filePath).'" style="width:90%;"/>
			<input type="submit" name="fileaction_newfolder" style="margin: 0;" value="Create"/>
			<div class="ff">&nbsp;</div>
		</form>
		
		<fieldset style="width: 100%;">
		<legend>Location</legend>
		<form action="?'.$_SERVER['QUERY_STRING'].'" method="POST"
			style="padding: .5em; margin: .5em -1px .5em -1px;" >
			'.$currentLocationPathList.'
			'.$currentLocationFoldersList.'
			<div class="ff">&nbsp;</div>
			'.$noFolders.'
			<div class="ff">&nbsp;</div>
		</form>
		</fieldset>
		
		</fieldset>
	</div>
</div>
<div class="fmcenter">
	<div class="fmcenteri">
		<fieldset style="width: 100%;">
		<legend>File Management</legend>
		<form action="?'.$_SERVER['QUERY_STRING'].'" method="POST" enctype="multipart/form-data"
			style="padding: .5em;">
			<input type="file" name="uploadedfile" value=""
				style="width:64%; margin: 0;"/>
			<input type="submit" name="fileaction_upload"
				style="margin: 0; width: 30%; padding: 3px;" value="Upload Here"/>
			<div class="ff">&nbsp;</div>
			<div class="ff" style="margin-top: .5em;">&nbsp;</div>
			<hr/>
			'.$currentLocationFilesList.'
			'.$deleteForm.'
			'.$noFiles.'
		</form>
		</fieldset>
	</div>
</div>
<div class="fmright">
	<div class="fmrighti" id="file_info_area">
		<fieldset style="width: 100%;">
			<legend>File Options</legend>
		<form action="">
			Loading...
		</form>
		</fieldset>
	</div>
</div>

'.$fileInfoScript;
?>