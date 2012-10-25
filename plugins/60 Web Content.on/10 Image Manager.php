<?php
/* PROCESS */
$IMAGEREG = new tinyRegistry;
$IMAGEREG->open('p_images_settings');
$IMAGEREG->push('last_plugin_load', time());

if($_REQUEST['unset_default_collection'] == true){
	$IMAGEREG->push('default_collection', '');
}

if(isset($_POST['collection_select'])){
	$IMAGEREG->push('default_collection', stripslashes($_POST['collection_selection']));
}

$default_collection = $IMAGEREG->pull(true, null, 'default_collection');
$default_collection = $default_collection[0]['value'];
$this->messageAddDebug($default_collection);

if(isset($_POST['collection_add'])){
	$collectionToAdd = trim(strip_tags(stripslashes($_POST['add_name'])));
	if($collectionToAdd != ''){
		$GLOBALS['PCON']->images_collection_add($collectionToAdd);
	}else{
		$this->messageAddError('You must give your new collection a name.');
	}
}

if(isset($_POST['collection_delete'])){
	$collectionToDelete = stripslashes($_POST['collection_selection']);
	if($collectionToDelete != ''){
		$GLOBALS['PCON']->images_collection_delete($collectionToDelete);
	}else{
		$this->messageAddError('You must select a collection to delete.');
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
</script>
';

$collectionsTemplate = '
<tr id="collections_row_**num" class="table_row_normal">
	<td style="width: 4em;">
		<input type="radio" name="collection_selection" value="**Collection name" style="width: 100%; cursor: pointer;"
		onchange="changeLast(\'table_row_normal\', \'table_row_highlight\');changeClass(\'collections_row_**num\', \'table_row_normal\', \'table_row_highlight\');"
		style="cursor: pointer;"/>
	</td>
	<td>**Collection name</td>
	<td>**Number of images</td>
</tr>
';

$collectionsHtml = $GLOBALS['PCON']->images_collection_list($collectionsTemplate);
if($collectionsHtml == '') $collectionsHtml = '<tr><td colspan="3" style="text-align: center;">Please create a collection to start uploading images.</td></tr>';

$this->pageArea.='
<fieldset>
	<legend>Image Manager</legend>
<form action="?'.$_SERVER['QUERY_STRING'].'" method="POST">
	<h3>Manage image collections</h3>

	<table class="selTable">
		<tr>
			<th>Select</th><th>Collection Name</th><th>Images</th>
		</tr>
		'.$collectionsHtml.'
	</table>

	<label for="collection_select"></label><input type="submit" name="collection_select" value="Select Collection" />
	<hr style="margin-left: 37%; width: 60%;" />
	<label for="collection_delete"></label><input type="submit" name="collection_delete" value="Delete Collection" style="width: 40%; border-color: #800;"/>
	<label for="collection_keep" style="width: 15%; padding-right: .5em;">Keep Images?</label>
	<input type="checkbox" name="collection_keep"  style="width: 1em; text-align: left;"/>
	
	<hr />
	<h3>Create a new image collection</h3>

	<label for="add_name">New Collection Name</label><input type="text" name="add_name" value="" />
	<label for="collection_add"></label><input type="submit" name="collection_add" value="Add New Collection" />
</form>
</fieldset>
';
}else{
$this->breadcrumbs.=' &raquo; <a href="?'.$_SERVER['QUERY_STRING'].'&unset_default_collection=true">Mange Collections</a> &raquo; '.$default_collection;

}
?>