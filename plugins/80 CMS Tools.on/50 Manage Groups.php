<?php

if(($_POST['g_submit'] == 'Add Group' || $_POST['g_submit'] == 'Save Group') && trim(stripslashes($_POST['g_name'])) != null ){
	$GLOBALS['UGP']->group_put(Array(
		'id' => stripslashes($_POST['g_groupid']),
		'name' => stripslashes($_POST['g_name']),
		'description' => stripslashes($_POST['g_desc']),
	));
}

if($_POST['g_submit'] == 'Delete Group'){
	$GLOBALS['UGP']->group_delete(stripslashes($_POST['g_groupid']));
	$_POST['g_action'] = 'delete';
}

if($_POST['g_submit'] == 'Add Attribute'){
	$gid = stripslashes($_POST['g_groupid']);
	$attribute = stripslashes($_POST['g_attribute']);
	$default = stripslashes($_POST['g_default']);
	$GLOBALS['UGP']->group_attributeAdd($gid, $attribute, $default);
}

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

if( $_POST['g_action'] == 'edit' && ctype_digit($_POST['g_groupid']) ){

	$this->breadcrumbs.=' &raquo; Edit Group';

	$group = $GLOBALS['UGP']->group_get($_POST['g_groupid']);

	$attributes = $GLOBALS['UGP']->group_attributesGet($_POST['g_groupid']);;

	$groupEditTemplate='
	<fieldset>
		<legend>Update Group Information</legend>
	<form action="?'.$_SERVER['QUERY_STRING'].'" method="POST">
		<input type="hidden" name="g_groupid" value="%i%" />
		<label for="g_name">Group Name</label><input type="text" name="g_name" value="%n%"/><br/>
		<label for="g_desc">Description</label><input type="text" name="g_desc" value="%d%"/><br/>
		<label for="g_submit">&nbsp;</label><input type="submit" name="g_submit" value="Save Group" />
		<label for="g_cancel">&nbsp;</label><input type="submit" name="g_cancel" value="Cancel Editing" />
	</form>
	</fieldset>

	<fieldset>
		<legend>Group Attributes</legend>
	<form action="?'.$_SERVER['QUERY_STRING'].'" method="POST">
		<input type="hidden" name="g_action" value="edit" />
		<input type="hidden" name="g_groupid" value="%i%" />

		<label for="g_attribute">Attribute Name</label><input type="text" name="g_attribute" value="'.$_SESSION['user_list_filter'].'"/><br/>
		<label for="g_default">Default Value</label><input type="text" name="g_default" value=""/><br/>
		<label for="g_submit">&nbsp;</label><input type="submit" name="g_submit" value="Add Attribute" />

		<table class="selTable">
			<tr>
				<th style="width: 6em;">Select</th><th>Attribute Name</th><th>Default Value</th>
			</tr>
		</table>

		<div style="display: block; text-align: right;">
			<input type="submit" name="g_submit" value="Delete Attribute"  style="width: 14em;" />
		</div>

	</form>
	</fieldset>

	';

	$this->pageArea.=$GLOBALS['UGP']->group_format($group, $groupEditTemplate);

}else{

	$groupsArray = $GLOBALS['UGP']->group_get(false);

	$groupListTemplate = '
	<tr id="g_table_row_%i%" class="table_row_normal">
		<td style="text-align: center;">
		<input type="radio" name="g_groupid" value="%i%"
		onchange="changeLast(\'table_row_normal\', \'table_row_highlight\');changeClass(\'g_table_row_%i%\', \'table_row_normal\', \'table_row_highlight\');"
		style="cursor: pointer;"/>
		</td>
		<td>%n%</td><td>%m%</td><td>%d%</td>
	</tr>
	';

	$groupListRows = $GLOBALS['UGP']->group_format($groupsArray, $groupListTemplate);

	$this->pageArea.= '

	<fieldset>
		<legend>Add New Group</legend>
	<form action="?'.$_SERVER['QUERY_STRING'].'" method="POST">
		<label for="g_name">Group Name</label><input type="text" name="g_name" value=""/><br/>
		<label for="g_desc">Description</label><input type="text" name="g_desc" value=""/><br/>
		<label for="g_submit">&nbsp;</label><input type="submit" name="g_submit" value="Add Group" />
	</form>
	</fieldset>

	<fieldset>
		<legend>Existing Primary Groups</legend>
	<form action="?'.$_SERVER['QUERY_STRING'].'" method="POST">

		<table class="selTable">
			<tr>
				<th>Select</th><th>Group Name</th><th>Members</th><th>Description</th>
			</tr>
			'.$groupListRows.'
		</table>

		<div style="display: block; text-align: right;">
			<input type="hidden" name="g_action" value="edit" />
			<input type="submit" name="g_submit" value="Edit Group" style="width: 14em;" />
			<input type="submit" name="g_submit" value="Delete Group"  style="width: 14em;" />
		</div>

	</form>
	</fieldset>

	';
}

?>