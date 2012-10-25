<?php
$this->pageTitle.=' | Home';

//build

$this->pageArea = '
<h3 style="float: left;">
<img src="gfx/logo_color_md.png" style="max-width: 100%;"/><br/>
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
Phylobyte Version 0.7 (Beta)</h3>

<div style="float: right; width: 30%; text-align: center; min-width: 20em;">
	<h2>Love your website.<br/>
	Make it grow.<br/>
	Choose a plugin<br/>
	from the list below.</h2>
</div>

<div class="ff">&nbsp;</div>

<hr />

<div style="margin: 1em;  text-align: center;">
';

$pluginDirArray = scandir('../plugins');

foreach($pluginDirArray as $possiblePlugin) {
	if(is_dir('../plugins/'.$possiblePlugin) && substr($possiblePlugin, -3) == '.on'){
		//now we make sure the plugin has the minimal requirements
		$pluginDir = $possiblePlugin;
		$pluginName = trim(preg_replace('#^\d+#', '', substr($possiblePlugin, 0, -3)));
		if(is_file('../plugins/'.$pluginDir.'/'.$pluginName.'.php')){
			//we have the minimal plugin setup, so we can now generate navigation
			$this->pageArea.='
			<a href="?plugin='.substr($pluginDir, 0, -3).'" class="headertext"
			style="position: relative; padding: .5em; color: white; font-size: 14pt;
			text-shadow: 1px 1px 2pt black; margin: .5em;">'.$pluginName.'</a>';
		}
	}
}

$this->pageArea.= '
</div>
';

$this->docArea.='
<h3>Welcome!</h3>
<p>Phylobyte is an advanced tool that helps you manage your website. It provides a powerful framework including user and resource management enabling rapid website development.</p>
';

?>