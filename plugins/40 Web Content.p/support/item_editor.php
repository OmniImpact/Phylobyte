<?php

//Process




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

//Build

$this->breadcrumbs.=' &raquo; Edit Item';

//switch on the content type
$editor = '';
switch($item['i_content_type']){

	case 'wysiwyg':
		$editor.='

<form action="?'.$_SERVER['QUERY_STRING'].'" method="POST">
<textarea style="height: 520px;">
'.$item['i_content'].'
</textarea>
<br/>
<input type="submit" value="Update With Edits"/>
<input type="submit" value="Save Edits"/>


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
</script>

		';
		break;

	case 'code':

		break;

	case 'link':

		break;

	case 'source':

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