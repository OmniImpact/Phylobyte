<?php



//are we trying to create a new group?
if($_POST['g_submit'] == 'Add Group' && trim(stripslashes($_POST['g_name'])) != null ){
	$newName = $this->phylobyteDB->quote(stripslashes($_POST['g_name']));
	$newDesc = $this->phylobyteDB->quote(htmlentities(stripslashes($_POST['g_desc'])));
	$groupExists = $this->phylobyteDB->prepare("SELECT COUNT(*) FROM p_groups WHERE name=$newName");
	$groupExists->execute();
	$groupExists = $groupExists->fetch();
	$groupExists = $groupExists[0];
	if($groupExists < 1){
		$addQuery = $this->phylobyteDB->prepare("
			INSERT INTO p_groups (name, description)
			VALUES ($newName, $newDesc);");
		if($addQuery->execute()){
			$this->messageAddNotification('Successfully added group.');
		}else{
			$this->messageAddError('Failed to add Group.');
		}
	}else{
		$this->messageAddError('A group with that name already exists.');
	}
}elseif ($_POST['g_submit'] == 'Add Group') {
    $this->messageAddError('You can not create a group without a name.');
}


//are we trying to save changes to a group?
if($_POST['g_submit'] == 'Save Group'){
	if($_POST['g_groupid'] == 1 && $_POST['g_name'] != 'admin'){
		//it's got to be admin!
		$this->messageAddError('You can not change the name of the "admin" group.');
		$_POST['g_submit'] = 'Edit Group';
	}else{
		$newName = $this->phylobyteDB->quote($_POST['g_name']);
		$newDesc = $this->phylobyteDB->quote($_POST['g_desc']);
		//try to save the group
		$groupExists = $this->phylobyteDB->prepare(
			"SELECT COUNT(*) FROM p_groups WHERE name=$newName AND id<>{$_POST['g_groupid']}");
		$groupExists->execute();
		$groupExists = $groupExists->fetch();
		$groupExists = $groupExists[0];
		if($groupExists > 0){
			$this->messageAddError('A group with that name already exists.');
		}else{
			if($this->phylobyteDB->exec("
			UPDATE p_groups SET name=$newName, description=$newDesc WHERE id='{$_POST['g_groupid']}';")){
				$this->messageAddNotification('Successfully saved group.');
			}
		}
		
	}
}

//are we trying to delete a group?

if($_POST['g_submit'] == 'Delete Group'){
	//get the number of members in the group
	$members = $this->phylobyteDB->prepare("SELECT COUNT(*) FROM p_users WHERE primarygroup={$_POST['g_groupid']};");
	$members->execute();
	$members = $members->fetch();
	$members = $members[0];
	
	if($_POST['g_groupid'] == 1){
		//you can't delete admin!
		$this->messageAddError('You can not delete the "admin" group.');
	}elseif($members > 0){
		$this->messageAddError('You can not delete a group while it still has members.');
	}else{
		//try to delete the group
		if($this->phylobyteDB->exec("
		DELETE FROM p_groups WHERE id='{$_POST['g_groupid']}';")){
		$this->messageAddNotification('Successfully deleted group.');
		}
	}
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

//generate pageArea, start by reading groups and generating the form there is nothing else needing to be processed right now

$groupList = $this->phylobyteDB->prepare("SELECT * FROM p_groups ORDER BY name;");
$groupList->execute();
$groupList = $groupList->fetchAll();

if($_POST['g_submit'] == 'Edit Group' && ctype_digit($_POST['g_groupid'])){

$this->breadcrumbs.=' &raquo; Edit Group';

$g_groupid = $this->phylobyteDB->quote($_POST['g_groupid']);
$group = $this->phylobyteDB->prepare("SELECT * FROM p_groups WHERE id=$g_groupid;");
$group->execute();
$group = $group->fetch();
//get group information from database

$this->pageArea.='
<fieldset>
	<legend>Update Group Information</legend>
<form action="?'.$_SERVER['QUERY_STRING'].'" method="POST">
	<input type="hidden" name="g_groupid" value="'.$group['id'].'" />
	<label for="g_name">Group Name</label><input type="text" name="g_name" value="'.$group['name'].'"/><br/>
	<label for="g_desc">Description</label><input type="text" name="g_desc" value="'.$group['description'].'"/><br/>
	<label for="g_submit">&nbsp;</label><input type="submit" name="g_submit" value="Save Group" />
	<label for="g_cancel">&nbsp;</label><input type="submit" name="g_cancel" value="Cancel Editing" />
</form>
</fieldset>
';

$this->docArea='
<h3>Edit Group</h3>
<p>If you would like to change the name of the description of a group, edit the values in the form and click "Save Group".</p>

<h3>Cancel Editing</h3>
<p>If you do not want to save your changes, click "Cancel Editing".</p>

<h3>The Admin Group</h3>
<p>Please note that you can only change the description of the <em>admin</em> group. If you try to change the name, you will receive an error.</p>
';

}else{

if($_POST['g_submit'] == 'Edit Group' && !ctype_digit($_POST['g_groupid'])){
	$this->messageAddError('Please select a valid group to edit.');
}
if($_POST['g_cancel'] != null){
	$this->messageAddAlert('Editing canceled. No changes have been made.');
}

$groupListTable = null;
foreach($groupList as $groupArray){

	//get the number of members in the group
	$members = $this->phylobyteDB->prepare("SELECT COUNT(*) FROM p_users WHERE primarygroup={$groupArray['id']};");
	$members->execute();
	$members = $members->fetch();
	$members = $members[0];
	
	$groupListTable.='
	<tr id="g_table_row_'.$groupArray['id'].'" class="table_row_normal">
		<td style="text-align: center;">
		<input type="radio" name="g_groupid" value="'.$groupArray['id'].'"
		onchange="changeLast(\'table_row_normal\', \'table_row_highlight\');changeClass(\'g_table_row_'.$groupArray['id'].'\', \'table_row_normal\', \'table_row_highlight\');"
		style="cursor: pointer;"/>
		</td>
		<td>'.$groupArray['name'].'</td><td>'.$members.'</td><td>'.$groupArray['description'].'</td>
	</tr>
	';
}

$this->pageArea.= '

<fieldset>
	<legend>Add New Primary Group</legend>
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
		'.$groupListTable.'
	</table>

	<div style="display: block; text-align: right;">
		<input type="submit" name="g_submit" value="Edit Group" style="width: 14em;" />
		<input type="submit" name="g_submit" value="Delete Group"  style="width: 14em;" />
	</div>

</form>
</fieldset>

';

}
?>