<?php

//Process

if(stristr($_POST['item_action'], 'update') || stristr($_POST['item_action'], 'save') ){

	$updateId = $GLOBALS['PCON']->pDB->quote($_POST['item_selected_id']);
	$updateName = $GLOBALS['PCON']->pDB->quote($_POST['item_name']);
	$updateDescription = $GLOBALS['PCON']->pDB->quote($_POST['item_description']);
	$updateContent = $GLOBALS['PCON']->pDB->quote($_POST['item_content']);
	$updateDate = $GLOBALS['PCON']->pDB->quote( date( 'Y-m-d H:i:s' , strtotime($_POST['item_date']) ) );

	$updateQuery = "
		UPDATE pc_items SET i_name=$updateName,i_date=$updateDate,i_description=$updateDescription,i_content=$updateContent WHERE id=$updateId;
	";

	$updateItem = $GLOBALS['PCON']->pDB->prepare($updateQuery);
	$updateItem->execute();

	phylobyte::messageAddNotification('Saved item edits.');
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

$showTags = false;
if($item['i_in_sec']){
	$parent = $GLOBALS['PCON']->item_get($item['i_in_sec']);
	if($parent['i_type'] == 'item_entries'){
		$showTags = true;
	}
}

$itemDate = date("F j, Y", strtotime($item['i_date']));

//Build

$this->breadcrumbs.=' &raquo; Edit Item';

//switch on the content type
$editor = '';
switch($item['i_content_type']){

	case 'wysiwyg':
		$editor.='

<form action="?'.$_SERVER['QUERY_STRING'].'" method="POST">
<input type="hidden" name="item_selected_id" value="'.$item['id'].'" />

<label for="item_name">Item Name</label><input type="text" name="item_name" value="'.$item['i_name'].'"/><br/>
<label for="item_description">Item Description</label><input type="text" name="item_description" value="'.$item['i_description'].'"/><br/>
<label for="item_date">Publish Date</label><input type="text" name="item_date" value="'.$itemDate.'" id="item_date_input"/><br/>

<textarea style="height: 520px;" name="item_content">
'.$item['i_content'].'
</textarea>
<br/>
<input type="submit" name="item_action" value="Update With Edits"/>
<input type="submit" name="item_action" value="Save Edits"/>

	<hr/>
<label for="item_cancel">&nbsp;</label><input type="submit" name="item_cancel" value="Cancel" />


<div class="ff">&nbsp;</div>
</form>

<script>
tinymce.init({
    selector: "textarea",
    theme: "modern",
    plugins: [
         "advlist autolink link image lists charmap print preview hr anchor spellchecker",
         "searchreplace code fullscreen insertdatetime ",
         "table contextmenu paste textcolor"
   ],
   content_css: "css/content.css",
   toolbar: "insertfile undo redo | styleselect | bold italic underline strikethrough superscript subscript | forecolor backcolor | alignleft aligncenter alignright alignjustify | bullist numlist outdent indent | link image",
   menubar: false
});
jQuery("#item_date_input").datepicker({
	showButtonPanel: true,
	dateFormat: "MM d, yy",
	changeYear: true
});
</script>

		';
		break;

	case 'code':

		break;

	case 'link':
		$editor.='

<form action="?'.$_SERVER['QUERY_STRING'].'" method="POST">
<input type="hidden" name="item_selected_id" value="'.$item['id'].'" />

<label for="item_name">Item Name</label><input type="text" name="item_name" value="'.$item['i_name'].'"/><br/>
<label for="item_description">Item Description</label><input type="text" name="item_description" value="'.$item['i_description'].'"/><br/>
<label for="item_date">Publish Date</label><input type="text" name="item_date" value="'.$itemDate.'" id="item_date_input"/><br/>
<label for="item_content">Link Destination</label><input type="text" name="item_content" value="'.$item['i_content'].'"
onkeyup="this.onchange();"
onchange="document.getElementById(\'item_new_destination_input\').value = this.value; document.getElementById(\'item_new_destination_input\').onchange();"/><br/>


<label>&nbsp;</label>Click the URLs displayed below to test the link in a new window.<br/>
<label for="item_current_destination">Current Link Destination</label>
<a href="'.$item['i_content'].'" target="_blank">
<input type="text" name="item_current_destination" value="'.$item['i_content'].'" disabled="disabled" style="border: none; cursor: pointer;"/></a><br/>
<label for="item_new_destination">New Link Destination</label>
<a href="" target="_blank" id="item_new_destination">
<input type="text" name="item_new_destination" value="'.$item['i_content'].'" disabled="disabled" style="border: none; cursor: pointer;"
onchange="document.getElementById(\'item_new_destination\').href = this.value" id="item_new_destination_input"/></a><br/>

<input type="submit" name="item_action" value="Update With Edits"/>
<input type="submit" name="item_action" value="Save Edits"/>

	<hr/>
<label for="item_cancel">&nbsp;</label><input type="submit" name="item_cancel" value="Cancel" />


<div class="ff">&nbsp;</div>
</form>

<script>
jQuery("#item_date_input").datepicker({
	showButtonPanel: true,
	dateFormat: "MM d, yy",
	changeYear: true
});
</script>
		';
		break;

	case 'source':
		$editor.='

<form action="?'.$_SERVER['QUERY_STRING'].'" method="POST">
<input type="hidden" name="item_selected_id" value="'.$item['id'].'" />

<label for="item_name">Item Name</label><input type="text" name="item_name" value="'.$item['i_name'].'"/><br/>
<label for="item_description">Item Description</label><input type="text" name="item_description" value="'.$item['i_description'].'"/><br/>
<label for="item_date">Publish Date</label><input type="text" name="item_date" value="'.$itemDate.'" id="item_date_input"/><br/>
<label for="item_content">Select Source Code</label><input type="text" name="item_content" value="'.$item['i_content'].'"/><br/>


<input type="submit" name="item_action" value="Update With Edits"/>
<input type="submit" name="item_action" value="Save Edits"/>

	<hr/>
<label for="item_cancel">&nbsp;</label><input type="submit" name="item_cancel" value="Cancel" />


<div class="ff">&nbsp;</div>
</form>

<script>
jQuery("#item_date_input").datepicker({
	showButtonPanel: true,
	dateFormat: "MM d, yy",
	changeYear: true
});
</script>
		';

		break;

	default:

		break;
}



if($showTags){
	$rightCol = '<div class="fmright">
			<div class="fmrighti" id="image_info_area">

			possible tags

			</div>
		</div>';
	$leftColStyle = 'width: 75%;';
}else{
	$rightCol = '';
	$leftColStyle = 'width: 100%;';
}


$this->pageArea.='
	<div class="panecontainer">
		<div class="fmcenter" style="'.$leftColStyle.'">
			<div class="fmcenteri">

			<fieldset style="width: 100%;">
				<legend>Edit Item "'.$item['i_name'].'"</legend>
			'.$editor.'
			</fieldset>

			</div>
		</div>
		'.$rightCol.'
		<div class="ff">&nbsp;</div>
	</div>
	<div class="ff">&nbsp;</div>';


?>