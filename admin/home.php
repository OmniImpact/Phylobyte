<?php
$this->pageTitle.=' | Home';

//build

$this->pageArea = '
<h3 style="float: left;">
<img src="gfx/logo_color_md.png" style="max-width: 100%;"/><br/>
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
Phylobyte Version 0.8 (Beta)</h3>

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

$pluginQuery = $this->phylobyteDB->prepare("
	SELECT * FROM p_plugins WHERE enabled='true' ORDER BY weight;
");
$pluginQuery->execute();
$pluginArray = $pluginQuery->fetchAll(PDO::FETCH_ASSOC);

foreach($pluginArray as $plugin) {
	//now we make sure the plugin has the minimal requirements
	$pluginDir = $plugin['weight'].' '.$plugin['name'].'.p';
	$pluginName = $plugin['name'];
	if(is_file('../plugins/'.$pluginDir.'/'.$pluginName.'.php')){
		//we have the minimal plugin setup, so we can now generate navigation
		$this->pageArea.='
		<a href="?plugin='.substr($pluginDir, 0, -2).'" class="headertext"
		style="position: relative; display: block; float: left;
		padding: .5em; color: white; font-size: 14pt;
		text-shadow: 1px 1px 2pt black; margin: .5em;">'.$pluginName.'</a>';
	}
}

$this->pageArea.= '
<div class="ff">&nbsp;</div>
</div>
';

$this->docArea.='
<h3>Welcome!</h3>
<p>Phylobyte is an advanced tool that helps you manage your website. It provides a powerful framework including user and resource management enabling rapid website development.</p>
';

?>