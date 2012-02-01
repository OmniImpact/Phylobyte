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

?>