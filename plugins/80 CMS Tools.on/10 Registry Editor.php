<?php
$TR = new tinyRegistry;
$MESSAGES = new tinyRegistry;
$MESSAGES->open('__messages');
if(isset($_REQUEST['addmessage'])){$MESSAGES->push(microtime(true), stripslashes($_REQUEST['addmessage']));}

if(isset($_GET['REGDROP'])){
	$MESSAGES->push(microtime(true), 'Drop Registry '.stripslashes($_GET['REGDROP']));
	$TR->registrydrop(stripslashes($_GET['REGDROP']));
	$this->messageAddNotification('Dropping Registry <i>'.stripslashes($_GET['REGDROP']).'</i>');
	if(stripslashes($_GET['REGDROP']) == '__messages'){
		$MESSAGES = null;
		$MESSAGES = new tinyRegistry;
		$MESSAGES->open('__messages');
		$this->messageAddAlert('Dropped Registry was __messages; it will be automatically recreated.');
		$this->messageAddNotification('Cleared Messages');
		$_GET['REG'] = '__messages';
	}
}
if(isset($_POST['REG']) && $_POST['REG'] != ''){
	$_GET['REG'] = $_POST['REG'];
}
if(isset($_GET['REG'])){
	$MESSAGES->push(microtime(true), 'Open Registry '.stripslashes($_GET['REG']));
	$TR->open(stripslashes($_GET['REG']));
}
if(isset($_POST['RPUSHKEY'])){
	$TR->push(stripslashes($_POST['RPUSHKEY']), stripslashes($_POST['RPUSHVAL']));
	$MESSAGES->push(microtime(true), 'Registry ['.stripslashes($_GET['REG']).'] push: '.stripslashes($_POST['RPUSHKEY']).','.stripslashes($_POST['RPUSHVAL']));
	$this->messageAddNotification('In Registry <i>'.stripslashes($_GET['REG']).'</i>, Pushing Key: <i>'.stripslashes($_POST['RPUSHKEY']).'</i>, Value: <i>'.stripslashes($_POST['RPUSHVAL']).'</i>');
}
if(isset($_GET['DEL'])){
	$TR->pull(false, stripslashes($_GET['DEL']));
	$MESSAGES->push(microtime(true), 'Delete '.stripslashes($_GET['DEL']).' from registry ['.stripslashes($_GET['REG']).']');
	$this->messageAddNotification('In Registry <i>'.stripslashes($_GET['REG']).'</i>, Deleting Item with ID: <i>'.stripslashes($_GET['DEL']).'</i>');
}

$leftcol.= '
<div class="re_leftcol">
<strong>Registries</strong>
	<form action="?plugin=80%20CMS%20Tools&function=10%20Registry%20Editor" method="POST" style="text-align: center;">
	<input name="REG" style="width: 80%;"/>
	<input type="hidden" name="addmessage" value="Submitted registry form." />
	<input type="submit" value="+" style="width: 2em;"/>
	</form>
<dl>
<dt>SEL?|DEL| NAME</dt>
';
$registries = $TR->registrylist();
foreach($registries as $registry){
if(stripslashes($_GET['REG']) == $registry['name']){$selchar = '&rarr;';}else{$selchar = '&nbsp;';}
$leftcol.=('<dt>['.$selchar.'] | <a href="?plugin=80%20CMS%20Tools&function=10%20Registry%20Editor&amp;REGDROP='.$registry['name'].'">&times;</a>
 | <a href="?plugin=80%20CMS%20Tools&function=10%20Registry%20Editor&amp;REG='.$registry['name'].'">'.$registry['name'].'</a></dt>
');
}
$leftcol.='
</dl>
</div>
';

$rightcol = null;
$rightcol.='
<div class="re_rightcol">
<strong>Registry Entries</strong><br/>
	<form action="?plugin=80%20CMS%20Tools&function=10%20Registry%20Editor&amp;REG='.$_GET['REG'].'" method="POST" style="text-align: center; width: 99.4%; margin-bottom: 6pt;">
	K: <input name="RPUSHKEY" style="width: 25%;"/>
	&nbsp;V: <input name="RPUSHVAL" style="width: 57%;"/>
	<input type="hidden" name="addmessage" value="Adding new Registry Entry to .'.$_GET['REG'].'" />
	<input type="submit" value="+" style="width: 2em;"/>
	</form>
';
if(!isset($_GET['REG'])){
$rightcol.='NO REGISTRY SELECTED.';
}else{
$rightcol.='
<table class="re_regentries">
<tr style="background-color: #ddd;"><td style="width: 5%;">ID</td><td style="width: 30%;">KEY</td><td>VALUE</td><td style="width: 1em;"></td></tr>
'.$TR->pull("<tr><td>%i%</td><td>%k%</td><td>%v%</td><td style=\"text-align: center;\"><a href=\"?plugin=80%20CMS%20Tools&function=10%20Registry%20Editor&amp;REG={$_GET['REG']}&amp;DEL=%i%\">&times;</a></td></tr>").'</table>';

}
$rightcol.='
</table>
</div>
';

$this->pageArea.=$leftcol;
$this->pageArea.=$rightcol;



?> 
