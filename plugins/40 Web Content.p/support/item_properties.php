<?php


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

/**
 * Process
 */

if(stristr($_POST['item_action'], 'save') ||
	stristr($_POST['item_action'], 'update')){
	//the information is requested to be updated

	$updateId = $GLOBALS['PCON']->pDB->quote($_POST['item_selected_id']);
	$updateName = $GLOBALS['PCON']->pDB->quote($_POST['item_name']);
	$updateWeight = $GLOBALS['PCON']->pDB->quote($_POST['item_weight']);
	$updateCType = $GLOBALS['PCON']->pDB->quote($_POST['item_content']);
	if($_POST['item_section'] == ''){
		$updateSection = 'NULL';
	}else{
		$updateSection = $GLOBALS['PCON']->pDB->quote($_POST['item_section']);
	}
	$updateVisible = $GLOBALS['PCON']->pDB->quote($_POST['item_visible']);

	$updateQuery = "
		UPDATE pc_items SET i_weight=$updateWeight,i_name=$updateName,i_content_type=$updateCType,i_is_visible=$updateVisible,i_in_sec=$updateSection WHERE id=$updateId;
	";

	$updateItem = $GLOBALS['PCON']->pDB->prepare($updateQuery);
	$updateItem->execute();

	phylobyte::messageAddNotification('Item properties updated.');
}

if(stristr($_POST['item_action'], 'save')){
	$_POST['item_selected_id'] = false;
	return;
}

//The item has now been updated if requested


/**
 * Get the item from the database
 */

$itemId = $_POST['item_selected_id'];

$item = $GLOBALS['PCON']->item_get($itemId);

//now we update the tags if requested

if(stristr($_POST['item_action'], 'add')){
	//the information is requested to be updated

	$tag = $GLOBALS['PCON']->pDB->quote($_POST['tag_name']);
	$sectionItem = $item['id'];

	$updateQuery = "
		INSERT INTO pc_tags (t_tag, t_section) VALUES ($tag, $sectionItem);
	";

	$updateItem = $GLOBALS['PCON']->pDB->prepare($updateQuery);
	$updateItem->execute();

	phylobyte::messageAddNotification('Added tag to section properties.');
}

if(stristr($_POST['item_action'], 'rename')){
	//the information is requested to be updated

	$tag = $GLOBALS['PCON']->pDB->quote($_POST['tag_name']);
	$tagId = $GLOBALS['PCON']->pDB->quote($_POST['tag_id']);
	$sectionItem = $item['id'];

	$updateQuery = "
		UPDATE pc_tags SET t_tag=$tag WHERE id=$tagId;
	";

	$updateItem = $GLOBALS['PCON']->pDB->prepare($updateQuery);
	$updateItem->execute();

	phylobyte::messageAddNotification('Updating tag in section properties.');
}

if(stristr($_POST['item_action'], 'remove')){
	//the information is requested to be updated

	$tagId = $GLOBALS['PCON']->pDB->quote($_POST['tag_id']);

	$updateQuery = "
		DELETE FROM pc_tags WHERE id=$tagId;
	";

	$updateItem = $GLOBALS['PCON']->pDB->prepare($updateQuery);
	$updateItem->execute();

	phylobyte::messageAddNotification('Deleting tag from properties.');
}



//get sections for dropdown

$sectionQuery = "SELECT * FROM pc_items WHERE i_type='item_pages' OR i_type='item_entries' ORDER BY i_name";
$sections = $GLOBALS['PCON']->pDB->prepare($sectionQuery);
$sections->execute();
$sections = $sections->fetchAll();

$selectSections = '<option value="">None</option>';
foreach($sections as $section){
	$selectSections.="<option value=\"{$section['id']}\">{$section['i_name']}</option>";
}

/**
 * How properties work;
 * PAGE, Page in Section, Section:
 * 	Name:
 * 	Data type: Formatted Text, HTML Editor, Source Code, (if not Collection, then also Link)
 * 	Weight:
 *	Is Visible?
 * (if not collection, in-section selector)
 *
 * Entries Area:
 * 	Name:
 * 	Weight:
 * 	Tag Editor:
 *
 */


$this->docArea = '
<h3>
    <span style="font-size: 80%;">Web Content Tools</span><br>
    Item Properties
</h3>

<p>
    Each item in the Basic Page Manager has certain properties that can affect
    the way it functions.
</p>

';



$this->breadcrumbs.=' &raquo; Item Properties &raquo; '.$item['i_name'];



if($item['i_is_visible']){
	$itemVisible = 'checked="checked"';
	$itemNotVisible = '';
}else{
	$itemVisible = '';
	$itemNotVisible = 'checked="checked"';
}


if($item['i_type'] != 'item_entries'){
	$selectContentType = '
		<label for="item_type">Content Type</label>
		&nbsp;<select id="id_item_content" name="item_content">
			<option value="wysiwyg">Formatted Text</option>
			<option value="code">HTML Code</option>
			<option value="source">Source Code</option>
			<option value="link">Link</option>
		</select>

		<script>
		document.getElementById(\'id_item_content\').value = "'.$item['i_content_type'].'";
		</script>
		';
}else{
	$selectContentType = '<input type="hidden" name="item_content" value="" />';
}

if($item['i_type'] != 'item_entries' && $item['i_type'] != 'item_pages'){
	$selectInSection = '<br/>
		<label for="item_section">In Section</label>
		&nbsp;<select id="id_item_section" name="item_section">
			'.$selectSections.'
		</select>

		<script>
		document.getElementById(\'id_item_section\').value = "'.$item['i_in_sec'].'";
		</script>
		';
}else{
	$selectInSection = '<input type="hidden" name="item_section" value="'.$item['i_in_sec'].'" />';
}


$this->pageArea.= '

	<fieldset>
		<legend>Properties for "'.$item['i_name'].'"</legend>
	<form action="?'.$_SERVER['QUERY_STRING'].'" method="POST">
		<input type="hidden" name="item_selected_id" value="'.$item['id'].'" />

		<label for="item_name">Item Name</label><input type="text" name="item_name" value="'.$item['i_name'].'"/><br/>
		<label for="item_weight">Weight</label><input type="text" name="item_weight" value="'.$item['i_weight'].'"/><br/>

		'.$selectContentType.'

		'.$selectInSection.'

		<label for="item_visible">&nbsp;</label>
		Hidden: <input type="radio" name="item_visible" value="0" style="margin: 0; width: 2em;" '.$itemNotVisible.'>
		&nbsp; &nbsp; &nbsp;
		Published: <input type="radio" name="item_visible" value="1" style="margin: 0; width: 2em;" '.$itemVisible.'>

		<br/>
		<br/>
		<label for="item_action">&nbsp;</label><input type="submit" name="item_action" value="Update Properties" />
		<label for="item_action">&nbsp;</label><input type="submit" name="item_action" value="Save Properties" />
			<hr/>
		<label for="item_cancel">&nbsp;</label><input type="submit" name="item_cancel" value="Cancel" />
			<div class="ff">&nbsp;</div>
	</form>
	</fieldset>

';


if($item['i_type'] == 'item_entries'){

	/**
	 * This part of the properties is just for entries categories.
	 * It allows you to create tags which will be used to organize the entries.
	 */

	//get the tags

	$tagQuery = "SELECT * FROM pc_tags WHERE t_section='{$item['id']}' ORDER BY t_tag";
	$tagsArray = $GLOBALS['PCON']->pDB->prepare($tagQuery);
	$tagsArray->execute();
	$tagsArray = $tagsArray->fetchAll();

$tagsRows = '';
foreach($tagsArray as $tagArray){
	$tagsRows.='
		<tr id="item_row_'.$tagArray['id'].'" class="table_row_normal">
			<td style="text-align: center;"><input type="radio" name="tag_id" value="'.$tagArray['id'].'"
			onchange="changeLast(\'table_row_normal\', \'table_row_highlight\');changeClass(\'item_row_'.$tagArray['id'].'\', \'table_row_normal\', \'table_row_highlight\');"
			onclick="document.getElementById(\'tag_name\').value = \''.addslashes($tagArray['t_tag']).'\';"
			/></td>
			<td>'.$tagArray['t_tag'].'</td>
		</tr>
	';
}

	$this->docArea.= '
<h3>
    Entry Tags
</h3>

<p>
    Unlike a regular page collection, Entries are organized by tags. Manage the tags within an Entries item
    to control the navigation and organization for the section. Within each tag, Entries will be ordered by date.
</p>

';

$this->pageArea.= '

	<fieldset>
		<legend>Edit Tags for Entries</legend>
	<form action="?'.$_SERVER['QUERY_STRING'].'" method="POST">
		<input type="hidden" name="item_selected_id" value="'.$item['id'].'" />

		<table class="selTable">
			<tr>
				<th>Select</th><th>Tag Name</th>
			</tr>
			'.$tagsRows.'
		</table>

		<div class="ff">&nbsp;</div>

		<label for="tag_name">Tag Name</label><input type="text" name="tag_name" id="tag_name" value=""/><br/>
		<label for="item_action">&nbsp;</label><input type="submit" name="item_action" value="Add Tag to Properties" />
		<label for="item_action">&nbsp;</label><input type="submit" name="item_action" value="Rename Tag in Properties" />

		<div class="ff">&nbsp;</div>

		<div class="destructive">
		<label for="item_action">&nbsp;</label><input type="submit" name="item_action" value="Remove Tag from Properties" />
		<div class="ff">&nbsp;</div>
		</div>

			<div class="ff">&nbsp;</div>
	</form>
	</fieldset>

';

}

?>