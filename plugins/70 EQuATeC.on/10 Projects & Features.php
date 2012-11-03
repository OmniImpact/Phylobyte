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
	$deleteProjectId = $_POST['delete_project_confirm'];
	if($GLOBALS['EQUATEC']->project_delete($deleteProjectId)){
		$this->messageAddNotification('Successfully deleted project.');
	}else{
		$this->messageAddError('There was a problem deleting the project.');
	}
}

if(isset($_POST['edit_feature_submit'])){
	$this->messageAddDebug('Process Edit Feature...');

}

if(isset($_POST['delete_feature_submit'])){
	$this->messageAddDebug('Process Delete Feature...');

}

if($_POST['new_project_submit']){
	$projectName = trim(stripslashes($_POST['new_project_name']));
	if($projectName != null){
		if($GLOBALS['EQUATEC']->project_put($projectName, stripslashes($_POST['new_project_description']))){
			$this->messageAddNotification('Successfully created new project.');
		}else{
			$this->messageAddError('There was a problem creating a new project.');
		}
	}else{
		$this->messageAddAlert('You must provide a name for the project.');
	}
}

if( ctype_digit($_GET['editproject']) ){
$editProjectId = stripslashes($_GET['editproject']);

$projectArray = $GLOBALS['EQUATEC']->project_get($editProjectId);
$projectArray = $projectArray[0];

$this->breadcrumbs.= ' &raquo; Edit Project';

$this->pageArea.='
<fieldset>
	<legend>Edit Project</legend>
	<form action="'.$baseQueryString.$selectedQueryString.'" method="POST">
		<label for="edit_project_name">Project Name</label><input name="edit_project_name" type="text" value="'.$projectArray['name'].'"/>
		<label for="edit_project_description">Description</label><input name="edit_project_description" type="text" value="'.$projectArray['description'].'"/>
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
$deleteProjectId = stripslashes($_GET['deleteproject']);

$projectArray = $GLOBALS['EQUATEC']->project_get($deleteProjectId);
$projectArray = $projectArray[0];

$this->breadcrumbs.= ' &raquo; Delete Project';

$this->pageArea.='
<fieldset>
	<legend>Confirm Delete</legend>
	<form action="'.$baseQueryString.$selectedQueryString.'" method="POST">
	<input type="hidden" name="delete_project_confirm" value="'.$deleteProjectId.'" />
	<p><strong>Are you sure you want to delete '.$projectArray['name'].'?</strong><br/>
	<em>"'.$projectArray['description'].'"</em></p>
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
';

$projectRowsArray = $GLOBALS['EQUATEC']->projects_get();

foreach($projectRowsArray as $projectRow) {
    $projectRows.="
<tr>
	<td>{$projectRow['name']}</td><td>{$projectRow['description']}</td>
	<td class=\"select\"><a href=\"$baseQueryString&amp;selectedproject={$projectRow['id']}\">Select</a></td>
	<td class=\"edit\"><a href=\"$baseQueryString$selectedQueryString&amp;editproject={$projectRow['id']}\">Edit</a></td>
	<td class=\"delete\"><a href=\"$baseQueryString$selectedQueryString&amp;deleteproject={$projectRow['id']}\">Delete</a></td>
</tr>
    ";
}


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

$selectedProject = $GLOBALS['EQUATEC']->project_get(stripslashes($_GET['selectedproject']));

$this->breadcrumbs.= ' &raquo; '.$selectedProject[0]['name'];

$featureRows = '
<tr>
	<th>Feature</th><th>Description</th><th>Edit</th><th>Delete</th>
</tr>
';


$featureRowsArray = $GLOBALS['EQUATEC']->features_get($selectedProject[0]['id']);

if(sizeof($featureRowsArray) < 1){
	$featureRows.='
		<tr>
			<td colspan="4" style="text-align: center; padding: 2em;"><strong>No Features to Display.</strong><br/>
			You can add new features using the form below this table.</td>
		</tr>
	';
}else{
	foreach($featureRowsArray as $featureRow) {
	$featureRows.="
	<tr>
		<td>{$featureRow['name']}</td><td>{$featureRow['description']}</td>
		<td class=\"edit\"><a href=\"$baseQueryString$selectedQueryString&amp;editfeature={$featureRow['id']}\">Edit</a></td>
		<td class=\"delete\"><a href=\"$baseQueryString$selectedQueryString&amp;deletefeature={$featureRow['id']}\">Delete</a></td>
	</tr>
		";
	}
}



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