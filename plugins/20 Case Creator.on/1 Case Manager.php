<?php


$this->docArea.='
<h3>Creating a Case</h3>
<p>To create a new case, enter the case name in the <em>Create a New Case</em> form. Once the case is created, it wil show in the <em>Case List</em>.
</p>

<h3>Editing Case Details</h3>
<p>
To edit details about the case, such as a case description, or the completion level that must be reached for a user to have access to the case, click the <em>Edit Details</em>
link to the right of the case name in the list.
</p>
';

$this->pageArea.='

<h2>Case Manager</h2>

<fieldset>
	<legend>Create a New Case</legend>
<form action="?plugin=Case%20Creator&function=1%20Case%20Manager" method="POST">
	<label for="p_username">Case Name</label><input type="text" name="p_username" value=""/><br/>
	<label for="p_submit">&nbsp;</label><input type="submit" name="p_submit" value="Create Case" />
</form>
</fieldset>

<fieldset>
	<legend>Case List</legend>
<form action="?plugin=Case%20Creator&function=1%20Case%20Manager" method="POST">
</form>
</fieldset>

';

?>