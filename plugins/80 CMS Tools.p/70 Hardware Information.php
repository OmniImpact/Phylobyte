<?php


$cpuinfoRaw = explode("\n", file_get_contents('/proc/cpuinfo'));
$cpuInfo = null;
foreach($cpuinfoRaw as $cpuinforow){
	$cpuinfoArray = explode(':', $cpuinforow);
	$cpuInfo[trim($cpuinfoArray[0])] = trim($cpuinfoArray[1]);
}

$meminfoRaw = explode("\n", file_get_contents('/proc/meminfo'));
$memInfo = null;
foreach($meminfoRaw as $meminforow){
	$meminfoArray = explode(':', $meminforow);
	$memInfo[trim($meminfoArray[0])] = rtrim(trim($meminfoArray[1]), ' kB')/1024;
}

$uptime = explode(' ', file_get_contents('/proc/uptime'));
$uptimeHoursDecimal = $uptime[0]/60/60;
$uptimeHoursArray = explode('.', $uptimeHoursDecimal);
$uptimeHours = $uptimeHoursArray[0];
$uptimeMinutesDecimal = ('.'.$uptimeHoursArray[1])*60;
$uptimeMinutesArray = explode('.', $uptimeMinutesDecimal);
$uptimeMinutes = str_pad($uptimeMinutesArray[0], 2, '0', STR_PAD_LEFT);
$uptimeSecondsDecimal = ('.'.$uptimeMinutesArray[1])*60;
$uptimeSecondsArray = explode('.', $uptimeSecondsDecimal);
$uptimeSeconds = str_pad($uptimeSecondsArray[0], 2, '0', STR_PAD_LEFT);


$this->pageArea.="

<pre>$infospit</pre>

<table style=\"width: 100%; border-spacing: 10px; overflow: hide;\">
<tr style=\"background-color: #ddd;\">
	<th style=\"width: 35%; padding: 4pt;\">Item</th><th>Value</th>
</tr>
<tr>
	<td><b>Processor</b></td><td></td>
</tr>
<tr>
	<td>Vendor</td><td>{$cpuInfo['vendor_id']}</td>
</tr>
<tr>
	<td>Model</td><td>{$cpuInfo['model name']}</td>
</tr>
<tr>
	<td>Speed</td><td>{$cpuInfo['cpu MHz']} MHz</td>
</tr>
<tr>
	<td>Address Size</td><td>{$cpuInfo['address sizes']}</td>
</tr>
<tr>
	<td>Power Saving</td><td>{$cpuInfo['power management']}</td>
</tr>
<tr>
	<td><b>Memory</b></td><td></td>
</tr>
<tr>
	<td>Total Available</td><td>{$memInfo['MemTotal']} mB</td>
</tr>
<tr>
	<td>Free</td><td>{$memInfo['MemFree']} mB</td>
</tr>
<tr>
	<td>Swap Free / Available</td><td>{$memInfo['SwapFree']} mB / {$memInfo['SwapTotal']} mB</td>
</tr>
<tr>
	<td><b>System</b></td><td></td>
</tr>
<tr>
	<td>Uptime</td><td>$uptimeHours Hour(s), $uptimeMinutes Minute(s), $uptimeSeconds Second(s)</td>
</tr>
</table>

";

?>
