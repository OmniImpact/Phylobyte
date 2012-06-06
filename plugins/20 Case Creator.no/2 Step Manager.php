<?php

$this->docArea.='
<h3>Select a Case</h3>
<p>If no case is currently selected for editing, you will be presented with the <em>Case List</em>. Click the <em>Edit Case</em> link to the right of the case you want to edit to select the case.
</p>

<h3>Steps and Paths</h3>
<p>
Cases are non-linear excercises where the user tries to reach a conclusion. Each case is made of <em>steps</em> and <em>paths</em>. A <em>step</em> is a piece of information that is evaluated to determine the <em>path</em>.
</p>

<h3>Creating Steps</h3>
<p>
To create a step, choose what type of step you want to create from the drop-down menu, and click the <em>Create Step</em> button. You will then be presented with a form to create the step. When you create the step, you will also be prompted to create results. Type of steps include Choice, Multiple Choice, Checklist, Keyword, and others which may be added in the future. The results you add in the step will be used to create the <em>paths</em>.
</p>

<h3>Creating Paths</h3>
<p>
To create a full case, we attach the <em>steps</em> together with <em>paths</em>. A path is a link from one <em>result</em> to another <em>step</em>. When creating a <em>step</em>, do not be surprised if you can not create the path immediately. You can save your step without the path, create the step you want to link to, and return later to create the path you want. One special option that is always available, however, is <em>STAY</em>. This simply clears the user\'s answer to the step, and they do not go anywhere.
</p>
';

$this->pageArea.='

<h2>Step Manager</h2>

<fieldset>
	<legend>Create a New Step</legend>
<form action="?plugin=Case%20Creator&function=2%20Step%20Manager" method="POST">
	<label for="cc_newsteptype">Select Step Type</label>
		<select name="">
			<option>&nbsp;</option>
			<option>Choice</option>
			<option>Multiple Choice</option>
			<option>Checklist</option>
			<option>Keyword</option>
		</select><br/>
	<label for="p_submit">&nbsp;</label><input type="submit" name="p_submit" value="Create Step" />
</form>
</fieldset>

<fieldset>
	<legend>Step List</legend>
<form action="?plugin=Case%20Creator&function=2%20Step%20Manager" method="POST">
</form>
</fieldset>

';
?>