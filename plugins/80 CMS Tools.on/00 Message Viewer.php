<?php

	
	$viewmessages.='<h3>Last 20 Error Messages:</h3>';
	$viewmessages.= $GLOBALS['MESSAGES']->pullquery('<div style="display: block; margin-top: 1em; font-size: 80%;">%k%</div>%v%', "SELECT * FROM __REGISTRY____pmessages WHERE value LIKE '#e.%' LIMIT 20;");
	$viewmessages.='<br/><h3>Last 20 Alerts:</h3>';
	$viewmessages.= $GLOBALS['MESSAGES']->pullquery('<div style="display: block; margin-top: 1em; font-size: 80%;">%k%</div>%v%', "SELECT * FROM __REGISTRY____pmessages WHERE value LIKE '#a.%' LIMIT 20;");
	$viewmessages.='<br/><h3>Last 20 Notifications:</h3>';
	$viewmessages.= $GLOBALS['MESSAGES']->pullquery('<div style="display: block; margin-top: 1em; font-size: 80%;">%k%</div>%v%', "SELECT * FROM __REGISTRY____pmessages WHERE value LIKE '#n.%' LIMIT 20;");
	$viewmessages.='<br/><h3>Last 20 Debugging Messages:</h3>';
	$viewmessages.= $GLOBALS['MESSAGES']->pullquery('<div style="display: block; margin-top: 1em; font-size: 80%;">%k%</div>%v%', "SELECT * FROM __REGISTRY____pmessages WHERE value LIKE '#d.%' LIMIT 20;");
	
	$viewmessages = str_replace('#e.', '<div class="error">', $viewmessages);
	$viewmessages = str_replace('#a.', '<div class="alert">', $viewmessages);
	$viewmessages = str_replace('#n.', '<div class="notification">', $viewmessages);
	$viewmessages = str_replace('#d.', '<div class="debug">', $viewmessages);

	$viewmessages = str_replace('##.', '</div>', $viewmessages);

	$this->pageArea.=$viewmessages;
?>