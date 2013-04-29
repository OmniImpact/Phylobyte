<?php
	
	// Let's access our registry!
	$DATAREG = new tinyRegistry;
	$DATAREG->open('p_codeblocks');
	$DATAREG->push('last_plugin_load', time());

	// The directory we'll use for storing our code blocks
	$dir = '../data/source_code/';

	// If we don't have our folder yet, create it
	if (!file_exists($dir)) {
		if (mkdir($dir)) {
			$this->messageAddNotification("Source Code directory created successfully.");
		} else {
			$this->messageAddError("There was a problem creating the Source Code directory, please refresh the page.");
		}
	}
	
	// If there is no default for the code block filter, that's OK, but we still need to set it if asked
	if(isset($_POST['c_filter'])){
		$_SESSION['code_block_filter'] = stripslashes($_POST['c_filter']);
	}

	// Let's save the user's code block
	if (isset($_POST['c_submit']) && $_POST['c_submit'] == 'Save') {
		if (file_put_contents($dir . $_POST['c_name'] . '.php', $_POST['c_code'])) {
			$author = $GLOBALS['UGP']->user_get($_SESSION['userid']);
			$json = json_encode(array('name' => $_POST['c_name'], 'desc' => $_POST['c_desc'], 'author' => $author[0]['name']));
			if (file_put_contents($dir. $_POST['c_name'] . '.dsc', $json)) {
				$this->messageAddNotification("Your code block was saved successfully.");
			} else {
				$this->messageAddError("There was a problem saving your code block, please try again.");
			}
		} else {
			$this->messageAddError("There was a problem saving your code block, please try again.");
		}
	}

	// Let's update the user's code block
	if (isset($_POST['c_submit']) && $_POST['c_submit'] == 'Update') {
		if (file_put_contents($dir . $_POST['c_name'] . '.php', $_POST['c_code'])) {
			$author = $GLOBALS['UGP']->user_get($_SESSION['userid']);
			$json = json_encode(array('name' => $_POST['c_name'], 'desc' => $_POST['c_desc'], 'author' => $author[0]['name']));
			if (file_put_contents($dir. $_POST['c_name'] . '.dsc', $json)) {
				$this->messageAddNotification("Your code block was saved successfully.");
			} else {
				$this->messageAddError("There was a problem saving your code block, please try again.");
			}
		} else {
			$this->messageAddError("There was a problem updating your code block, please try again.");
		}
	}

	// Let's update the user's code block's name
	if (isset($_POST['c_submit']) && $_POST['c_submit'] == 'Update Name') {
		if (!isset($_POST['c_new_name'])) {
			$this->messageAddError("You must enter a valid name to rename your code block.");
		} else if (rename($dir . $_POST['c_old_name'] . '.php', $dir . $_POST['c_new_name'] . '.php')) {
			$author = $GLOBALS['UGP']->user_get($_SESSION['userid']);
			$desc = json_decode(file_get_contents($dir . $_POST['c_old_name'] . '.dsc'), true);
			$json = json_encode(array('name' => $_POST['c_new_name'], 'desc' => $desc['desc'], 'author' => $author[0]['name']));
			if (unlink($dir . $_POST['c_old_name'] . '.dsc') && file_put_contents($dir . $_POST['c_new_name'] . '.dsc', $json)) {
				$this->messageAddNotification("Your code block was renamed successfully.");
			} else {
				$this->messageAddError("There was a problem renaming your code block, please try again.");
			}
		} else {
			$this->messageAddError("There was a problem renaming your code block, please try again.");
		}
	}

	// Let's delete the user's code block
	if (isset($_POST['c_submit']) && $_POST['c_submit'] == 'Delete Code Block') {
		if (!isset($_POST['c_uname'])) {
			$this->messageAddError("You must select a code block before trying to delete it.");
		} else if (unlink($dir . $_POST['c_uname'] . '.php')) {
			unlink($dir . $_POST['c_uname'] . '.dsc');
			$this->messageAddNotification("Your code block was deleted successfully.");
		} else {
			$this->messageAddError("There was a problem deleting your code block, please try again.");
		}
	}

	if (isset($_POST['c_submit']) && $_POST['c_submit'] == 'Rename Code Block') {
		$this->breadcrumbs .= ' &raquo; Rename "' . $_POST['c_uname'] . '" Code Block';

		$this->pageArea .= '
			<fieldset>
				<legend>Rename Code Block</legend>
				<form action="?' . $_SERVER['QUERY_STRING'] . '" method="POST">
					<label for="c_old_name">Old Name</label><span>' . $_POST['c_uname'] . '</span><br />
					<label for="c_new_name">New Name</label><input type="text" name="c_new_name" id="c_new_name" autofocus />

					<label for="c_old_name"></label><input type="hidden" name="c_old_name" id="c_old_name" value="' . $_POST['c_uname'] . '" autofocus />
					<label for="c_submit"></label><input type="submit" name="c_submit" value="Update Name" />
					<label for="c_submit"></label><input type="submit" name="c_submit" value="Cancel" />
					<div class="ff">&nbsp;</div>
				</form>
			</fieldset>
		';
	} else if (isset($_POST['c_submit']) && $_POST['c_submit'] == 'Edit Code Block') {
		$desc = json_decode(file_get_contents($dir . $_POST['c_uname'] . '.dsc'), true);

		$this->breadcrumbs .= ' &raquo; Edit "' . $_POST['c_uname'] . '" Code Block';

		$this->headArea .= '
		<script type="text/javascript" src="../plugins/codemirror/codemirror.js"></script>
		<script type="text/javascript" src="../plugins/codemirror/codemirror_css.js"></script>
		<script type="text/javascript" src="../plugins/codemirror/codemirror_javascript.js"></script>
		<script type="text/javascript" src="../plugins/codemirror/codemirror_clike.js"></script>
		<script type="text/javascript" src="../plugins/codemirror/codemirror_matchbrackets.js"></script>
		<script type="text/javascript" src="../plugins/codemirror/codemirror_php.js"></script>
		<link rel="stylesheet" href="../plugins/codemirror/codemirror.css" />
		<style>.CodeMirror {border: 1px solid #000;}</style>
		';

		$this->pageArea .= '
			<fieldset>
				<legend>Edit Code Block</legend>
				<form action="?' . $_SERVER['QUERY_STRING'] . '" method="POST">
					<label for="c_desc">Description</label><input type="text" name="c_desc" id="c_desc" value="' . $desc['desc'] . '" autofocus />
					<label for="c_code"></label><textarea name="c_code" id="c_code">
' . file_get_contents($dir . $_POST['c_uname'] . '.php') . '</textarea><br />
					<label for="c_name"></label><input type="hidden" name="c_name" value="' . $_POST['c_uname'] . '" />
					<label for="c_submit"></label><input type="submit" name="c_submit" value="Update" />
					<label for="c_submit"></label><input type="submit" name="c_submit" value="Cancel" />
					<div class="ff">&nbsp;</div>
				</form>
			</fieldset>

			<script>var myCodeMirror = CodeMirror.fromTextArea(document.getElementById("c_code"), {
				lineNumbers: true,
				matchBrackets: true,
				mode: "application/x-httpd-php",
				indentUnit: 4,
				indentWithTabs: true,
				tabMode: "shift"
			});
			</script>
		';
	} else if (isset($_POST['c_submit']) && $_POST['c_submit'] == 'Add Code Block') {
		$this->breadcrumbs .= ' &raquo; Add Code Block';

		$this->headArea .= '
		<script type="text/javascript" src="../plugins/codemirror.js"></script>
		<script type="text/javascript" src="../plugins/codemirror_css.js"></script>
		<script type="text/javascript" src="../plugins/codemirror_javascript.js"></script>
		<script type="text/javascript" src="../plugins/codemirror_clike.js"></script>
		<script type="text/javascript" src="../plugins/codemirror_matchbrackets.js"></script>
		<script type="text/javascript" src="../plugins/codemirror_php.js"></script>
		<link rel="stylesheet" href="../plugins/codemirror.css" />
		<style>.CodeMirror {border: 1px solid #000;}</style>
		';

		$this->pageArea .= '
			<fieldset>
				<legend>Add New Code Block</legend>
				<form action="?' . $_SERVER['QUERY_STRING'] . '" method="POST">
					<label for="c_name">Name</label><input type="text" name="c_name" id="c_name" value="' . $_POST['c_name'] . '" required autofocus />
					<label for="c_desc">Description</label><input type="text" name="c_desc" id="c_desc" value="' . $_POST['c_desc'] . '" />
					<textarea name="c_code" id="c_code">
<?php
	echo "Hello World!";
?></textarea><br />
					<label for="c_submit"></label><input type="submit" name="c_submit" value="Save" />
					<div class="ff">&nbsp;</div>
				</form>
			</fieldset>

			<script>var myCodeMirror = CodeMirror.fromTextArea(document.getElementById("c_code"), {
				lineNumbers: true,
				matchBrackets: true,
				mode: "application/x-httpd-php",
				indentUnit: 4,
				indentWithTabs: true,
				tabMode: "shift"
			});
			</script>
		';
	} else {

		// Let's fetch all of our code blocks
		$codeBlocksTable = "";
		$files = glob($dir . '*.php');
		$found_files = array();

		// If the user is trying to search, search through both the code and description files of each code block
		if (isset($_SESSION['code_block_filter']) && $_SESSION['code_block_filter'] != '') {
			$filter = $_SESSION['code_block_filter'];

			foreach ($files as $file) {
				$filename = substr(substr($file, strlen($dir)), 0, -4);

				$code_file = file_get_contents($file);
				$desc_file = file_get_contents($dir . $filename . '.dsc');

				if (strpos($code_file, $filter) != false || strpos($desc_file, $filter) != false) {
					array_push($found_files, $file);
				}
			}
		} else {
			$found_files = $files;
		}
		

		if (count($found_files) > 0) {
			$codeBlocksTable .= '
				<table class="selTable">
					<tbody>
						<tr><th style="width: 6em;">Select</th><th>Name</th><th>Description</th></tr>';

			foreach ($found_files as $i => $file) {
				// Format the path name to show the code block name
				$filename = substr(substr($file, strlen($dir)), 0, -4);
				$desc = json_decode(file_get_contents($dir . $filename . '.dsc'), true);

				$codeBlocksTable .= '
				<tr id="c_table_row_' . $i . '" class="table_row_normal">
					<td style="text-align: center;">
					<input type="radio" name="c_uname" value="' . $filename . '"
					onchange="changeLast(\'table_row_normal\', \'table_row_highlight\');changeClass(\'c_table_row_' . $i . '\', \'table_row_normal\', \'table_row_highlight\');"
					style="cursor: pointer;"/>
					</td>
					<td>' . $filename . '</td>
					<td>' . $desc['desc'] . '</td>
				</tr>
				';
			}

			$codeBlocksTable .= '</tbody></table>';
		} else {
			if (isset($_SESSION['code_block_filter']) && $_SESSION['code_block_filter'] != '') {
				$this->messageAddAlert("You have no code blocks with the current search term. Try a different term or clear it.");
			} else {
				$this->messageAddAlert("You currently have no code blocks. Use the form below to create one.");
			}
		}

		$this->headArea .='
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

		$this->pageArea .= '
		<fieldset>
			<legend>Add New Code Block</legend>
			<form action="?' . $_SERVER['QUERY_STRING'] . '" method="POST">
				<label for="c_name">Name</label><input type="text" name="c_name" id="c_name" />
				<label for="c_desc">Description</label><input type="text" name="c_desc" id="c_desc" />
				<label for="c_submit"></label><input type="submit" name="c_submit" value="Add Code Block" />

				<div class="ff">&nbsp;</div>
			</form>
		</fieldset>

		<fieldset>
			<legend>Existing Code Block</legend>
			<form action="?' . $_SERVER['QUERY_STRING'] . '" method="POST">
				<label for="c_filter">Filter Blocks</label><input type="text" name="c_filter" id="c_filter" value="' . $_SESSION['code_block_filter'] . '" />
				<label for="c_submit"></label><input type="submit" name="c_submit" value="Apply" />
				' . $codeBlocksTable . '
				<div class="ff">&nbsp;</div>

				<div style="display: block; text-align: right;">
					<label for="c_submit"></label><input type="submit" name="c_submit" value="Edit Code Block"/>
					<div class="ff">&nbsp;</div>
					<div class="destructive">
					<label for="c_submit"></label><input type="submit" name="c_submit" value="Rename Code Block"/>
					<label for="c_submit"></label><input type="submit" name="c_submit" value="Delete Code Block"/>
					<div class="ff">&nbsp;</div>
					</div>
				</div>
			</form>
		</fieldset>
	';
	}
?>