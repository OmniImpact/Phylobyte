<?php
	
	// Let's access our registry!
	$DATAREG = new tinyRegistry;
	$DATAREG->open('p_metadata');
	$DATAREG->push('last_plugin_load', time());

	if ($_POST['m_submit']) {
		$DATAREG->push('default_title', $_POST['m_title']);
		$DATAREG->push('precede_title', $_POST['m_precede_title']);
		$DATAREG->push('follow_title', $_POST['m_follow_title']);
		$DATAREG->push('keywords', $_POST['m_keywords']);
		$DATAREG->push('desc', $_POST['m_desc']);
		$DATAREG->push('persistent_desc', $_POST['m_persistent_desc']);
		$DATAREG->push('author', $_POST['m_author']);
		$DATAREG->push('default_keywords', $_POST['m_default_keywords']);
		$DATAREG->push('persistent_keywords', $_POST['m_persistent_keywords']);

		$this->messageAddNotification("Your information has been updated!");
	}

	$this->pageArea .= '
	<fieldset>
		<legend>Website Metadata</legend>
		<form action="?' . $_SERVER['QUERY_STRING'] . '" method="POST">
			<label for="m_title">Title</label><input type="text" name="m_title" id="m_title" value="' . $DATAREG->pull(true, null, 'default_title')[0]['value'] . '" />
			<label for="m_precede_title">Precede Title</label><input type="text" name="m_precede_title" id="m_precede_title" value="' . $DATAREG->pull(true, null, 'precede_title')[0]['value'] . '" />
			<label for="m_follow_title">Follow Title</label><input type="text" name="m_follow_title" id="m_follow_title" value="' . $DATAREG->pull(true, null, 'follow_title')[0]['value'] . '" />
			<label for="m_keywords">Keywords</label><input type="text" name="m_keywords" id="m_keywords" value="' . $DATAREG->pull(true, null, 'keywords')[0]['value'] . '" />
			<label for="m_desc">Description</label><input type="text" name="m_desc" id="m_desc" value="' . $DATAREG->pull(true, null, 'desc')[0]['value'] . '" />
			<label for="m_persistent_desc">Persistent Description</label><input type="text" name="m_persistent_desc" id="m_persistent_desc" value="' . $DATAREG->pull(true, null, 'persistent_desc')[0]['value'] . '" />
			<label for="m_author">Author</label><input type="text" name="m_author" id="m_author" value="' . $DATAREG->pull(true, null, 'author')[0]['value'] . '" />
			<label for="m_default_keywords">Default Keywords</label><input type="text" name="m_default_keywords" id="m_default_keywords" value="' . $DATAREG->pull(true, null, 'default_keywords')[0]['value'] . '" />
			<label for="m_persistent_keywords">Persistent Keywords</label><input type="text" name="m_persistent_keywords" id="m_persistent_keywords" value="' . $DATAREG->pull(true, null, 'persistent_keywords')[0]['value'] . '" />
			<label for="m_submit">&nbsp;</label><input type="submit" id="m_submit" name="m_submit" value="Update" />

			<div class="ff">&nbsp;</div>
		</form>
	</fieldset>
	';
?>