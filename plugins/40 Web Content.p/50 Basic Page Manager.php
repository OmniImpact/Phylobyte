<?php

$this->docArea = '

<h3>
    <span style="font-size: 80%;">Web Content Tools</span><br>
    Basic Page Manager
</h3>

<p>
    The basic page manager is Phylobyte\'s simplest way to create and organize content for your website.
It works in conjunction with the <em>Basic Navigation Builder</em>, allowing you to build the content
    you need and present it on the website.
</p>

<h3>
Item Properties
</h3>

<p>
Select an item and click "Item Properties" to change the way the item is presented.
From properties, you can change the item name and weight and other relevant information.
    If the item is an "Entries" item, you can manage the tags that will be used for organization.
</p>

<h3>
Editing an Item
</h3>

<p>
To change the actual content of an item, click "Edit Item". This will bring up the relevant editor
    based on the content type selected in the item properties.
</p>

';

/**
 * Initialize
 */

if(!isset($_SESSION['item_filter_term'])){
	$_SESSION['item_filter_term'] = '';
}

if(!isset($_SESSION['item_filter_in'])){
	$_SESSION['item_filter_in'] = 'top';
	phylobyte::messageAddAlert('No filter selected. By default, only top-level items will be displayed.');
}


/**
 * PROCESS
 */

if(stristr($_POST['item_action'], 'properties')){
	include('support/item_properties.php');
	if($_POST['item_selected_id']) return;
}

if(stristr($_POST['item_action'], 'edit')){
	include('support/item_editor.php');
	if($_POST['item_selected_id']) return;
}

include('support/page_home.php');

?>