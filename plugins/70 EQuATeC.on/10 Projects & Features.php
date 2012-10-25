<?php

$baseQueryString = '?plugin='.urlencode($_GET['plugin']).'&amp;function='.urlencode($_GET['function']);
$selectedQueryString = '&amp;selectedproject='.urldecode($_GET['selectedproject']);

$this->docArea.='
<h3>Manage Projects <br/>and Features</h3>
<p>This section allows you to manage your projects and features.
To see the features for a project, click "Select" in the table.</p>
';

if(isset($_POST['edit_project_submit'])){
	$this->messageAddDebug('Process Edit Project...');
}

if(isset($_POST['delete_project_submit'])){
	$this->messageAddDebug('Process Delete Project...');
}

if(isset($_POST['edit_feature_submit'])){
	$this->messageAddDebug('Process Edit Feature...');

}

if(isset($_POST['delete_feature_submit'])){
	$this->messageAddDebug('Process Delete Feature...');

}

if( ctype_digit($_GET['editproject']) ){

$this->breadcrumbs.= ' &raquo; Edit Project';

$this->pageArea.='
<fieldset>
	<legend>Edit Project</legend>
	<form action="'.$baseQueryString.$selectedQueryString.'" method="POST">
		<label for="edit_project_name">Project Name</label><input name="edit_project_name" type="text" />
		<label for="edit_project_description">Description</label><input name="edit_project_description" type="text" />
		<label for="edit_project_cancel"></label><input name="edit_project_cancel" type="submit" value="Cancel Editing"/>
		<label for="edit_project_submit"></label><input name="edit_project_submit" type="submit" value="Save Project"/>
		<div class="ff">&nbsp;</div>
	</form>
</fieldset>
';

return;
}

if( ctype_digit($_GET['editfeature']) ){

$this->breadcrumbs.= ' &raquo; Edit Feature';

$this->pageArea.='
<fieldset>
	<legend>Edit Feature</legend>
	<form action="'.$baseQueryString.$selectedQueryString.'" method="POST">
		<label for="edit_feature_name">Feature Name</label><input name="edit_feature_name" type="text" />
		<label for="edit_feature_description">Description</label><input name="edit_feature_description" type="text" />
		<label for="edit_feature_cancel"></label><input name="edit_feature_cancel" type="submit" value="Cancel Editing"/>
		<label for="edit_feature_submit"></label><input name="edit_feature_submit" type="submit" value="Save Feature"/>
		<div class="ff">&nbsp;</div>
	</form>
</fieldset>
';

return;
}

if(ctype_digit($_GET['deleteproject'])){

$this->breadcrumbs.= ' &raquo; Delete Project';

$this->pageArea.='
<fieldset>
	<legend>Confirm Delete</legend>
	<form action="'.$baseQueryString.$selectedQueryString.'" method="POST">
	<p><strong>Are you sure you want to delete the project (blah)?</strong></p>
		<label for="delete_project_cancel"></label><input name="delete_project_cancel" type="submit" value="Cancel"/>
		<label for="delete_project_submit"></label><input name="delete_project_submit" type="submit" value="Delete Project"/>
		<div class="ff">&nbsp;</div>
	</form>
</fieldset>
';

return;
}

if(ctype_digit($_GET['deletefeature'])){

$this->breadcrumbs.= ' &raquo; Delete Feature';

$this->pageArea.='
<fieldset>
	<legend>Confirm Delete</legend>
	<form action="'.$baseQueryString.$selectedQueryString.'" method="POST">
	<p><strong>Are you sure you want to delete the feature (blah)?</strong></p>
		<label for="delete_feature_cancel"></label><input name="delete_feature_cancel" type="submit" value="Cancel"/>
		<label for="delete_feature_submit"></label><input name="delete_feature_submit" type="submit" value="Delete Feature"/>
		<div class="ff">&nbsp;</div>
	</form>
</fieldset>
';

return;
}

$projectRows = '
<tr>
	<th>Project</th><th>Description</th><th>Select</th><th>Edit</th><th>Delete</th>
</tr>
<tr>
	<td>Android</td>
	<td>The Android Platform</td>
	<td class="select"><a href="'.$baseQueryString.'&amp;selectedproject=1">Select</a></td>
	<td class="edit"><a href="'.$baseQueryString.$selectedQueryString.'&amp;editproject=1">Edit</a></td>
	<td class="delete"><a href="'.$baseQueryString.$selectedQueryString.'&amp;deleteproject=1">Delete</a></td>
</tr>
<tr>
	<td>iOS</td><td>The Apple Platform</td>
	<td class="select"><a href="'.$baseQueryString.'&amp;selectedproject=2">Select</a></td>
	<td class="edit"><a href="'.$baseQueryString.$selectedQueryString.'&amp;editproject=2">Edit</a></td>
	<td class="delete"><a href="'.$baseQueryString.$selectedQueryString.'&amp;deleteproject=2">Delete</a></td>
</tr>
';

$this->pageArea.='
<fieldset>
	<legend>Manage Project</legend>
	<form action="'.$baseQueryString.$selectedQueryString.'" method="POST">

		<table class="equatec_table">
			'.$projectRows.'
		</table>
	
		<label for="new_project_name">New Project Name</label><input name="new_project_name" type="text" />
		<label for="new_project_description">Description</label><input name="new_project_description" type="text" />
		<label for="new_project_submit"></label><input name="new_project_submit" type="submit" value="Add Project"/>
		<div class="ff">&nbsp;</div>
	</form>
</fieldset>
';

if( ctype_digit($_GET['selectedproject']) ){

$this->breadcrumbs.= ' &raquo; (Selected Project)';

$featureRows = '
<tr>
	<th>Feature</th><th>Description</th><th>Edit</th><th>Delete</th>
</tr>
<tr>
	<td>EUGL 1.2</td>
	<td>End User Generated List v 1.2</td>
	<td class="edit"><a href="'.$baseQueryString.$selectedQueryString.'&amp;editfeature=1">Edit</a></td>
	<td class="delete"><a href="'.$baseQueryString.$selectedQueryString.'&amp;deletefeature=1">Delete</a></td>
</tr>
<tr>
	<td>USL 2.0</td>
	<td>User Generated List v 2.0</td>
	<td class="edit"><a href="'.$baseQueryString.$selectedQueryString.'&amp;editfeature=2">Edit</a></td>
	<td class="delete"><a href="'.$baseQueryString.$selectedQueryString.'&amp;deletefeature=2">Delete</a></td>
</tr>
';

$this->pageArea.='
<fieldset>
	<legend>Manage Project Features</legend>
	<form action="'.$baseQueryString.$selectedQueryString.'" method="POST">

		<table class="equatec_table">
			'.$featureRows.'
		</table>

		<label for="new_feature_name">New Feature Name</label><input name="new_feature_name" type="text" />
		<label for="new_feature_description">Description</label><input name="new_feature_description" type="text" />
		<label for="new_feature_submit"></label><input name="new_feature_submit" type="submit" value="Add Feature"/>
		<div class="ff">&nbsp;</div>
	</form>
</fieldset>
';
}

?>