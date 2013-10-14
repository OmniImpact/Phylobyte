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
   toolbar: "bold italic underline strikethrough superscript subscript forecolor backcolor styleselect | alignleft aligncenter alignright alignjustify outdent indent bullist numlist | link image undo redo",
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

		$this->headArea .= '
		<script type="text/javascript" src="../plugins/codemirror/codemirror.js"></script>
		<script type="text/javascript" src="../plugins/codemirror/codemirror_css.js"></script>
		<script type="text/javascript" src="../plugins/codemirror/codemirror_javascript.js"></script>
		<script type="text/javascript" src="../plugins/codemirror/codemirror_clike.js"></script>
		<script type="text/javascript" src="../plugins/codemirror/codemirror_xml.js"></script>
		<script type="text/javascript" src="../plugins/codemirror/codemirror_matchbrackets.js"></script>
		<script type="text/javascript" src="../plugins/codemirror/codemirror_php.js"></script>
		<link rel="stylesheet" href="../plugins/codemirror/codemirror.css" />
		<style>.CodeMirror {border: 1px solid #000;}</style>
		';

		$editor.='

<form action="?'.$_SERVER['QUERY_STRING'].'" method="POST">
<input type="hidden" name="item_selected_id" value="'.$item['id'].'" />

<label for="item_name">Item Name</label><input type="text" name="item_name" value="'.$item['i_name'].'"/><br/>
<label for="item_description">Item Description</label><input type="text" name="item_description" value="'.$item['i_description'].'"/><br/>
<label for="item_date">Publish Date</label><input type="text" name="item_date" value="'.$itemDate.'" id="item_date_input"/><br/>

<textarea style="height: 520px;" name="item_content" id="item_content_codemirror">
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

var myCodeMirror = CodeMirror.fromTextArea(document.getElementById("item_content_codemirror"), {
	lineNumbers: true,
	matchBrackets: true,
	mode: "application/x-httpd-php",
	indentUnit: 4,
	indentWithTabs: true,
	tabMode: "shift"
});

jQuery("#item_date_input").datepicker({
	showButtonPanel: true,
	dateFormat: "MM d, yy",
	changeYear: true,
	beforeShow: function(){
		setTimeout(function (){
			jQuery("#ui-datepicker-div").css("z-index","5");
         }, 500);
	}
});

</script>

		';

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

		// The directory we'll use for storing our code blocks
		$dir = '../data/source_code/';

		// If we don't have our folder yet, create it
		if (!file_exists($dir)) {
			if (mkdir($dir)) {
				$this->messageAddNotification("Source Code directory created successfully.");
			} else {
				$this->messageAddError("There was a problem creating the Source Code directory, please refresh the page.");
			}
		}

		$files = glob($dir . '*.php');
		$fileNum = 0;
		$selectedFile = -1;
		foreach ($files as $file) {
			$filename = substr(substr($file, strlen($dir)), 0, -4);
			$desc_file = json_decode(file_get_contents($dir . $filename . '.dsc'));
			if($item['i_content'] == $filename){
				$selectedFile = $fileNum;
			}
			$sourceRows.='
				<tr id="item_row_'.$fileNum.'" class="table_row_normal">
					<td style="text-align: center;"><input type="radio" name="item_content" value="'.$filename.'" id="item_num_'.$fileNum.'"
					onchange="changeLast(\'table_row_normal\', \'table_row_highlight\');changeClass(\'item_row_'.$fileNum.'\', \'table_row_normal\', \'table_row_highlight\');"
					/></td>
					<td>'.$filename.'</td>
					<td>'.$desc_file->desc.'</td>
				</tr>
			';
			$fileNum++;
		}

		$selectFileScript = '';
		if($selectedFile != -1){
			$selectFileScript = '
			document.getElementById("item_num_'.$selectedFile.'").checked = true;
			document.getElementById("item_num_'.$selectedFile.'").onchange();
			';
		}


		$editor.='

<form action="?'.$_SERVER['QUERY_STRING'].'" method="POST">
<input type="hidden" name="item_selected_id" value="'.$item['id'].'" />

<label for="item_name">Item Name</label><input type="text" name="item_name" value="'.$item['i_name'].'"/><br/>
<label for="item_description">Item Description</label><input type="text" name="item_description" value="'.$item['i_description'].'"/><br/>
<label for="item_date">Publish Date</label><input type="text" name="item_date" value="'.$itemDate.'" id="item_date_input"/><br/>

<table class="selTable">
			<tr>
				<th>Select</th><th>Source File</th><th>Description</th>
			</tr>
'.$sourceRows.'
</table>

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
'.$selectFileScript.'
</script>
		';


		break;

	default:
//I don't know what to do with this
phylobyte::messageAddAlert('The selected item does not contain editable content.');
$_POST['item_selected_id'] = false;
return;
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