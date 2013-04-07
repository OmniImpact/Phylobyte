<?php
/* PROCESS */
$IMAGEREG = new tinyRegistry;
$IMAGEREG->open('p_images_settings');
$IMAGEREG->push('last_plugin_load', time());

if($_REQUEST['unset_default_collection'] == true){
	$IMAGEREG->push('default_collection_'.$_SESSION['loginid'], '');
}

if(isset($_POST['collection_select'])){
	$IMAGEREG->push('default_collection_'.$_SESSION['loginid'], stripslashes($_POST['collection_selection']));
}

if(isset($_POST['cache_clear']) && $_POST['cache_clear'] == 'Clear Cache Now'){
	if(file_get_contents($GLOBALS['PCON']->siteURL().'/plugins/image.php?do=quickclear') == 'true'){
		$this->messageAddNotification('Successfully cleared cache.');
	}else{
		$this->messageAddError('There was a problem clearing the cache.');
	}
}

$default_collection = $IMAGEREG->pull(true, null, 'default_collection_'.$_SESSION['loginid']);
$default_collection = $default_collection[0]['value'];
$colDir = '../data/images/'.$default_collection;

if(isset($_POST['image_rename'])){
	if(is_file($colDir.'/'.$_POST['originalname'])){
		if(rename($colDir.'/'.$_POST['originalname'], $colDir.'/'.$_POST['newname'])){
			$this->messageAddNotification('Successfully renamed image.');
		}else{
			$this->messageAddError('There was a problem renaming the image.');
		}
	}else{
		$this->messageAddAlert('The image you are trying to rename does not exist.');
	}
}

if(isset($_POST['image_upload'])){
	$imageInfo = getimagesize($_FILES['image_source']['tmp_name']);
	if($imageInfo['mime'] == 'image/jpeg' || $imageInfo['mime'] == 'image/png'){
		if(@move_uploaded_file($_FILES['image_source']['tmp_name'],
			$colDir.'/'.basename($_FILES['image_source']['name'])) ){
			$this->messageAddNotification('Successfully uploaded image into collection.');
		}else{
			$this->messageAddError('There was a problem uploading your image.');
		}
	}else{
		$this->messageAddAlert('The image manager is designed to handle JPEG and PNG images.');
	}
}

if(isset($_POST['img_delete_now'])){
	if(is_file($colDir.'/'.$_POST['selectedimg'])){
		if(@unlink($colDir.'/'.$_POST['selectedimg'])){
			$this->messageAddNotification('Successfully deleted image.');
		}else{
			$this->messageAddError('There was a problem deleting the image.');
		}
	}else{
		$this->messageAddAlert('The image you are trying to delete does not exist.');
	}
}

if(isset($_POST['collection_add'])){
	$collectionToAdd = trim(strip_tags(stripslashes($_POST['add_name'])));
	if($collectionToAdd != ''){
		$GLOBALS['PCON']->images_collection_add($collectionToAdd);
	}else{
		$this->messageAddAlert('You must give your new collection a name.');
	}
}

if(isset($_POST['collection_delete'])){
	$collectionToDelete = stripslashes($_POST['collection_selection']);
	if($collectionToDelete != ''){
		//$this->messageAddDebug('Empty? '.$_POST['collection_empty']);
		if(isset($_POST['collection_empty']) && $_POST['collection_empty'] == 'empty'){
			$emptyCollection = true;
		}else{
			$emptyCollection = false;
		}
		$GLOBALS['PCON']->images_collection_delete($collectionToDelete, $emptyCollection);
	}else{
		$this->messageAddAlert('You must select a collection to delete.');
	}
}



/* BUILD */
if($default_collection == ''){


$this->breadcrumbs.=' &raquo; Manage Collections';

//a little javascript
$this->pageArea.= '
<script type="text/javascript">
	var lastChangedName = null;

	function changeClass(targetID, classA, classB){
		var node = document.getElementById(targetID);
		lastChangedName = targetID;
		var currentClasses = node.className.split(\' \');
		var currentFirstClass = currentClasses[0];
		if(currentFirstClass == classA){
			currentClasses[0] = classB;
		}else if(currentFirstClass == classB){
			currentClasses[0] = classA;
		}
		node.className = currentClasses.join(\' \');
	}

	function changeLast(classA, classB){
		if(lastChangedName != null){
			changeClass(lastChangedName, classA, classB);
		}
	}
	
	function changeAll(fromClass, toClass){
		jQuery("."+fromClass).each(function(){jQuery(this).toggleClass(fromClass+" "+toClass)})
	}
</script>
';

$collectionsTemplate = '
<tr id="collections_row_**NUM" class="table_row_normal">
	<td style="width: 4em; text-align: center;">
		<input type="radio" name="collection_selection" value="**CollectionName"
		onchange="changeAll(\'table_row_highlight\', \'table_row_normal\');
		changeClass(\'collections_row_**NUM\', \'table_row_normal\', \'table_row_highlight\');
		changeClass(\'collections_prow_**NUM\', \'table_row_normal\', \'table_row_highlight\');"
		style="cursor: pointer;"/>
	</td>
	<td>**CollectionName</td>
	<td style="width: 8em; text-align: center;">**NumberOfImages</td>
</tr>
<tr id="collections_prow_**NUM" class="table_row_normal">
	<td colspan="3" style="text-align: center; border-bottom: 1px solid black;">
	<div style="display: inline-block;">
	**SixSmPreviews
	</div>
	</td>
</tr>
';

$collectionsHtml = $GLOBALS['PCON']->images_collection_list($collectionsTemplate);
if($collectionsHtml == '') $collectionsHtml = '<tr><td colspan="3" style="text-align: center;">Please create a collection to start uploading images.</td></tr>';

//get the number of cached items and directory size
$directory = '../data/imgcache/';
$directory_size_output = exec('du -sk ' . $directory);
$directory_size_kb = trim(str_replace($directory, '', $directory_size_output));
$directory_size_mb = $directory_size_kb/1024;
$numCached = count(scandir($directory))-3;
if($numCached < 1){
	$numCached = 0;
}

$this->pageArea.='
<h3>Image Collections</h3>

<p>To help you keep your images organized, you can sort them into collections.
Images managed through this plugin are stored as files on the server with their original names,
and are suitable for embedding and direct linking.</p>

<p>
You can not delete a collection that still has images in it. If you want to do so, check the box
that says "Empty collection before delete".
</p>

<fieldset>
	<legend>
		Create Collection
	</legend>
<form action="?'.$_SERVER['QUERY_STRING'].'" method="POST">
	<label for="add_name">New Collection Name</label><input type="text" name="add_name" value="" />
	<label for="collection_add"></label><input type="submit" name="collection_add" value="Add New Collection" />
	<div class="ff">&nbsp;</div>
</form>
</fieldset>

<fieldset>
	<legend>Select and Delete Collections</legend>
<form action="?'.$_SERVER['QUERY_STRING'].'" method="POST">
	<table class="selTable">
		<tr>
			<th>Select</th><th>Collection Name</th><th>Total Images</th>
		</tr>
		'.$collectionsHtml.'
	</table>

	<label for="collection_select"></label><input type="submit" name="collection_select" value="Select Collection" />
	
	<div class="ff">&nbsp;</div>
	
	<div class="destructive">
	<label for="collection_delete"></label><input type="submit" name="collection_delete" value="Delete Collection"/>
	<div class="ff">&nbsp;</div>
	<label for="collection_empty" style="width: 60%;">Empty collection before delete?</label>
	<input type="checkbox" name="collection_empty" value="empty" style="width: 2em; text-align: center;"/>
	<div class="ff">&nbsp;</div>
	</div>
</form>
</fieldset>

<div class="alertable">
<p style="text-align: center;">Phylobyte uses a script to generate thumbnail images.<br/>
When that script executes, it maintains a cache so that next time an image is requested,
it can use the thumbnail it generated the first time. Sometimes, that cache can get too big.</p>
<p style="text-align: center;">
You can safely clear the cache at any time to free up space and it will not break anything.
You may, however, notice a decrease in performance the first time thumbnails are generated again.
In time, as the cache is rebuilt, performance will return to normal.
</p>
<p style="text-align: center;">
There are currently <strong>'.$numCached.'</strong> cached images, totalling
<strong>'.$directory_size_mb.' MiB</strong> in size.
</p>
<form action="?'.$_SERVER['QUERY_STRING'].'" method="POST" style="border: none;">
<label for="cache_clear"></label>
<input type="submit" name="cache_clear" value="Clear Cache Now"/>
	<div class="ff">&nbsp;</div>
</form>
</div>

';
}else{
	
//This is the code for the collection. it needs to show all images in the collection.
	
$this->breadcrumbs.=' &raquo; Mange Collection "'.$default_collection.'"';

$previewHTML = null;
$previewSize = 120;
if(is_dir($colDir)){
	$possibleImages = scandir($colDir);
	$i = 0;
	$j = 0;
	while($j <= count($possibleImages) ){
		if(is_file($colDir.'/'.$possibleImages[$j])){
			$previewHTML.='
			<div class="collection-thumbtile collection-thumbtile-'.$previewSize.'">
			<div class="delete">&times;</div>
			<div class="collection-thumbtile-preview collection-thumbtile-preview-'.$previewSize.'"
			style="background-image: url(\''.$GLOBALS['PCON']->siteURL().'/plugins/image.php?source='.
				$GLOBALS['PCON']->siteURL().'/data/images/'.$default_collection.'/'.
				$possibleImages[$j].'&size='.$previewSize.'&method=ofit\');">
			
			<div class="imagetile-infolink">
			<a href="javascript:getImageInfo(\''.$default_collection.'/'.$possibleImages[$j].'\');"><i class="icon-info-sign">&nbsp;</i></a>
			</div>
			
			<div class="deleteconfirm" style="display: none;">
				Are you sure<br/>
				you want to delete<br/>
				this image?<br/>
				<form class="inlineform" action="?'.$_SERVER['QUERY_STRING'].'" method="POST">
					<input type="hidden" name="selectedimg" value="'.$possibleImages[$j].'" />
					<input type="submit" class="hidedeletedialogs" value="No"/>
					<input type="submit" name="img_delete_now" class="submitform" value="Yes" onclick="submitNow = true;">
				</form>
			</div>
			
			'.'
			</div>
			<a href="'.$GLOBALS['PCON']->siteURL().'/data/images/'.
			$default_collection.'/'.$possibleImages[$j].'" target="_blank">
			'.$possibleImages[$j].'</a>
			</div>
			';
			$i++;
		}
		$j++;
	}
	if($i == 0){
		$previewHTML = $noPreviews;
	}
}else{
	$previewHTML = 'Error generating previews.';
}
	
	
$imgUploadForm = '
<fieldset>
	<legend>
		Collection Management
	</legend>
<form action="?'.$_SERVER['QUERY_STRING'].'" method="POST" enctype="multipart/form-data">
	<label for="image_source">Select Image</label><input type="file" name="image_source"/>
	<label for="image_upload"></label><input type="submit" name="image_upload" value="Upload To Collection" />
	<div class="ff">&nbsp;</div>
	<hr/>
	<label for="unset_default_collection"></label>
	<input type="submit" name="unset_default_collection" value="View All Collections" />
	<div class="ff">&nbsp;</div>
</form>
</fieldset>
';

$imageInfo = '
<fieldset style="width: 100%; margin: -1px;">
	<legend>Image Options</legend>
Loading...
</fieldset>
';

$deleteImgScript = "

<script>
var submitNow = false;

jQuery('.delete').on('click', function(){
	jQuery('.deleteconfirm').fadeOut();
	jQuery(this).parent().find('.deleteconfirm').fadeIn();
});

jQuery('.hidedeletedialogs').on('click', function(){
	jQuery('.deleteconfirm').fadeOut();
});

jQuery('.inlineform').submit(function(event){
	if(!submitNow){
		event.preventDefault();
	}
});

</script>

";

$infoImgScript = "
<script>

var getImageInfo = function(imageName){
	if(typeof imageName == 'undefined'){
		imageName = '';
	}
	jQuery('#image_info_area').fadeOut('fast', function(){
		jQuery.ajax( {
			url: '" . $GLOBALS['PCON']->siteURL() . "/plugins/40 Web Content.p/ajax/get_image_info.php', 
			async: true,
			data:
			{
				'image': imageName,
				'TARGET_QUERY_STRING': '{$_SERVER['QUERY_STRING']}'
			},
			type: 'POST',
			dataType: 'html',
			success: function(data) { 
				jQuery('#image_info_area').html(data);
				jQuery('#image_info_area').fadeIn('fast');
			}
		});
	});
}
getImageInfo();

</script>
";

$this->docArea='
<h3>
	<span style="font-size: 80%;">Web Content Tools</span><br/>
	Image Manager
</h3>

<p>
The image management plugin for Phylobyte helps you organize libraries of images in either JPEG or PNG format.
</p>

<p>
Now that you have selected a collection, you can upload images in either JPEG or PNG format for use on your website.
Your selected collection will be remembered between sessions. To view the rest of your collections, click the
"View All Collections" button. Click the <i class="icon-info-sign"></i> icon to
get additional information about the image, a larger preview, and links to different sizes.
</p>

';

$this->pageArea.=$imgUploadForm.'
	<div class="panecontainer">
		<div class="fmcenter" style="width: 75%;">
			<div class="fmcenteri">
		
			<fieldset style="padding-top: .5em; border: 1px solid gray; width: 100%;">
				<legend>Images in Collection "'.$default_collection.'"</legend>
			'.$previewHTML.'
			<div class="ff">&nbsp;</div>
			</fieldset>
			
			</div>
		</div>
		<div class="fmright">
			<div class="fmrighti" id="image_info_area">
			
			'.$imageInfo.'
			
			</div>
		</div>
		<div class="ff">&nbsp;</div>
	</div>
	<div class="ff">&nbsp;</div>
	'.$deleteImgScript.$infoImgScript;

}
?>