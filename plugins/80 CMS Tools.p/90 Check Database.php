<?php

if(isset($_POST['runsetup'])){
	
$this->pageArea.='<h3>Attempting setup for \''.$_POST['runsetup'].'\' in \''.$GLOBALS['UGP']->getDatabase().'\'</h3>';

$setupOutput = null;

//check for setups directory

//match a script to the requested table

//include the script

//print results

$this->pageArea.=$setupOutput;

}else{

/*
'table' => '%t%',
'tableExists' => '%tE%',
'title' => '%T%',
'description' => '%D%',
'arrColumnsExpected' => '',
'colsExpected' => '%cE%',
'arrColsFound' => '',
'colsFound' => '%cF%',
'colsMatch' => '%cM%',
'dataExists' => '%dE%',
'dataCount' => '%dC%'
*/
	
$tableCheckTemplate = '
<div class="tablecheck">
<div class="tablechecki">

<form class="inlineform" action="?'.$_SERVER['QUERY_STRING'].'" method="POST"
style="text-align: right; float: left;">
<button type="submit" name="runsetup" value="%t%" style="font-size: 80%; padding: 2pt; margin-right: 1em;">Setup</button>
</form>

<div>
<strong>%T%</strong>
</div>

<div style="font-size: 80%;">
	%D%
</div>
<table class="selTable" style="margin-bottom: .25em;">
	<tr>
		<th>Check</th>
		<th>Result</th>
	</tr>
	<tr>
		<td>Table \'%t%\' Exists?</td>
		<td style="text-align: center;">%tE%</td>
	</tr>
	<tr>
		<td>Table Columns Expected:<br/>
		<span style="font-size: 75%;">%cE%</span>
		<hr/>
		Table Columns Found:<br/>
		<span style="font-size: 75%;">%cF%</span>
		</td>
		<td style="text-align: center;">%cM%</td>
	</tr>
	<tr>
		<td>Number of Rows?</td>
		<td style="text-align: center;">%dC%</td>
	</tr>
</table>

</div>
</div>
';

$good = '<i class="icon-ok" style="color: #228c22;"></i>';
$bad = '<i class="icon-remove" style="color: #900;"></i>';


$this->pageArea.='<h3>Validating Tables in \''.$GLOBALS['UGP']->getDatabase().'\'</h3>';

$this->pageArea.=$GLOBALS['UGP']->checkDb($tableCheckTemplate, 'p_users',
Array('id','username','name','passwordhash','status','statusvalue','super','email'),
'Phylobyte Users', 'Stores user accounts', $good, $bad);

$this->pageArea.=$GLOBALS['UGP']->checkDb($tableCheckTemplate, 'p_groups',
Array('id','name','description'),
'Phylobyte Groups', 'Stores groups', $good, $bad);

$this->pageArea.='<div class="ff">&nbsp;</div>';

$this->pageArea.=$GLOBALS['UGP']->checkDb($tableCheckTemplate, 'p_memberships', 
Array('id','userid','groupid','lastused','joined'),
'Phylobyte Memberships', 'Stores group memberships', $good, $bad);

$this->pageArea.=$GLOBALS['UGP']->checkDb($tableCheckTemplate, 'p_gattributes', 
Array('id','gid','attribute','defaultvalue'),
'Phylobyte Attributes', 'Stores group attributes', $good, $bad);

$this->pageArea.='<div class="ff">&nbsp;</div>';

$this->pageArea.=$GLOBALS['UGP']->checkDb($tableCheckTemplate, 'p_uattributes', 
Array('id','uid','aid','value'),
'Phylobyte User Attributes', 'Stores per-user attribute values', $good, $bad);

$this->pageArea.=$GLOBALS['UGP']->checkDb($tableCheckTemplate, 'p_plugins', 
Array('id','name','weight','enabled','available'),
'Phylobyte Plugins', 'Tracks plugins status', $good, $bad);

}
?>