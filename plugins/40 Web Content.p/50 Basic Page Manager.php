<?php

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


/**
 * Initialize
 */

if(!isset($_SESSION['item_filter_term'])){
	$_SESSION['item_filter_term'] = '';
}

if(!isset($_SESSION['item_filter_in'])){
	$_SESSION['item_filter_in'] = 'top';
}


/**
 * PROCESS
 */

if(stristr($_POST['item_action'], 'properties')){
	include('support/item_properties.php');
	return;
}

if(stristr($_POST['item_action'], 'edit')){
	include('support/item_editor.php');
	return;
}


if(isset($_POST['item_create'])){
	$name = $_POST['item_name'];
	$type = $_POST['item_type'];
	if(substr($type, 0,8) == 'in_item_'){
		$GLOBALS['PCON']->items_add($name, 'item_page', substr($type, 8));
	}else{
		$GLOBALS['PCON']->items_add($name, $type);
	}
}

if(stristr($_POST['item_action'], 'delete')){
	$GLOBALS['PCON']->item_delete($_POST['item_selected_id']);
}


if($_POST['item_action'] == 'Filter'){
	$_SESSION['item_filter_term'] = $_POST['item_filter_term'];
	$_SESSION['item_filter_in'] = $_POST['item_filter_in'];
}

$items = $GLOBALS['PCON']->items_array();
$filteredItems = $GLOBALS['PCON']->items_array($_SESSION['item_filter_term'], $_SESSION['item_filter_in']);

$itemsListRows = '';
$collectionsOptions = '';
$collectionsSelect = '';

foreach($items as $itemArray){
	if($itemArray['i_type'] == 'item_pages' || $itemArray['i_type'] == 'item_entries'){
		$collectionsOptions.='
			<option value="in_item_'.$itemArray['id'].'">Item in '.$itemArray['i_name'].'</option>
		';
		$collectionsSelect.='
			<option value="'.$itemArray['id'].'">In Collection '.$itemArray['i_name'].'</option>
		';
	}
}


foreach($filteredItems as $itemArray){
	$cssRule = '';

	if($itemArray['i_in_sec']){
		$parent = $itemArray['i_parent_name'];
	}else{
		$parent = '';
	}

	if($parent != ''){
		$indenter = '&nbsp; &nbsp; &nbsp; <i class="icon-file-alt">&nbsp;</i> &nbsp; ';
	}else{
		if($itemArray['i_type'] == 'item_entries' || $itemArray['i_type'] == 'item_pages'){
			$indenter = '<i class="icon-folder-open">&nbsp;</i> ';
			$cssRule = 'style="font-weight: bold;"';
		}else{
			$indenter = '<i class="icon-file">&nbsp;</i> ';
		}
	}

	$type = '';
	switch($itemArray['i_type']){
		case 'item_pages':
			$type = 'Collection';
			break;

		case 'item_entries':
			$type = 'Entries';
			break;

		default:
			if($itemArray['i_in_sec'] != null){
				$type = 'Item';
			}else{
				$type = 'Page';
			}
	}

	$itemsListRows.='
		<tr id="item_row_'.$itemArray['id'].'" class="table_row_normal" '.$cssRule.'>
			<td style="text-align: center;"><input type="radio" name="item_selected_id" value="'.$itemArray['id'].'"
			onchange="changeLast(\'table_row_normal\', \'table_row_highlight\');changeClass(\'item_row_'.$itemArray['id'].'\', \'table_row_normal\', \'table_row_highlight\');"
			/></td>
			<td>'.$indenter.$itemArray['i_name'].'</td>
			<td>'.$parent.'</td>
			<td>'.$type.'</td>
			<td>'.$itemArray['i_weight'].'</td>
		</tr>
	';
}

/**
 * BUILD
 */

$this->pageArea.='<h3>Basic Page Manager</h3>';

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

$this->pageArea.= '

	<fieldset>
		<legend>Create Page Item</legend>
	<form action="?'.$_SERVER['QUERY_STRING'].'" method="POST">
		<label for="item_name">Item Name</label><input type="text" name="item_name" value=""/><br/>
		<label for="item_type">Select Type</label>
		<select name="item_type">
			<option value="item_page">Page</option>
			<option value="item_pages">Collection</option>
			<option value="item_entries">Entries</option>
			'.$collectionsOptions.'
		</select>
		<br/>
		<label for="item_create">&nbsp;</label><input type="submit" name="item_create" value="Add Item" />
			<div class="ff">&nbsp;</div>
	</form>
	</fieldset>

	<fieldset>
		<legend>Existing Items</legend>
	<form action="?'.$_SERVER['QUERY_STRING'].'" method="POST">

	<input name="item_filter_term" style="width: 40%;" value="'.$_SESSION['item_filter_term'].'"/>

	<select style="width: 40%; margin-left: 1em;" id="item_filter_in" name="item_filter_in">
		<option value="top">Top-Level Items</option>
		<option value="all">All Items</option>
		'.$collectionsSelect.'
	</select>

	<script>
	document.getElementById(\'item_filter_in\').value = "'.$_SESSION['item_filter_in'].'";
	</script>

	<input type="submit" name="item_action" value="Filter" style="width: 10%; float: right; margin: 0;"/>

	<hr />

		<table class="selTable">
			<tr>
				<th>Select</th><th>Item Name</th><th>Parent</th><th>Type</th><th>Weight</th>
			</tr>
			'.$itemsListRows.'
		</table>

		<div style="display: block; text-align: right;">
			<input type="submit" name="item_action" value="Edit Item"/>
			<input type="submit" name="item_action" value="Item Properties"/>

			<div class="ff">&nbsp;</div>

			<div class="destructive">
			<label for="item_delete"></label><input type="submit" name="item_action" value="Delete Item"/>
			<div class="ff">&nbsp;</div>
			</div>

			<div class="ff">&nbsp;</div>
		</div>

	</form>
	</fieldset>

';


?>