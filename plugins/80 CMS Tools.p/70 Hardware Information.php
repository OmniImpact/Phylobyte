<?php


$cpuinfoRaw = explode("\n", file_get_contents('/proc/cpuinfo'));
$cpuInfo = []; // Initialize as an empty array
foreach($cpuinfoRaw as $cpuinforow){
	$cpuinforow = trim($cpuinforow); // Trim the row first
	if (empty($cpuinforow)) continue; // Skip empty lines
	$parts = explode(':', $cpuinforow, 2); // Limit explode to 2 parts
	if (count($parts) === 2) {
		$key = trim($parts[0]);
		$value = trim($parts[1]);
		$cpuInfo[$key] = $value;
	}
}

$meminfoRaw = explode("\n", file_get_contents('/proc/meminfo')); // Fixed syntax error here
$memInfo = []; // Initialize as an empty array
foreach($meminfoRaw as $meminforow){
	$meminforow = trim($meminforow); // Trim the row first
	if (empty($meminforow)) continue; // Skip empty lines
	$parts = explode(':', $meminforow, 2); // Limit explode to 2 parts
	if (count($parts) === 2) {
		$key = trim($parts[0]);
		$value = trim($parts[1]);
		// Remove ' kB' and convert to MB, ensuring it's a numeric value
		$numericValue = (float)str_replace(' kB', '', $value);
		$memInfo[$key] = $numericValue / 1024;
	}
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


<table style=\"width: 100%; border-spacing: 10px; overflow: hide;\">
<tr style=\"background-color: #ddd;\">
	<th style=\"width: 35%; padding: 4pt;\">Item</th><th>Value</th>
</tr>
<tr>
	<td><b>Processor</b></td><td></td>
</tr>
<tr>
	<td>Vendor</td><td>".($cpuInfo['vendor_id'] ?? 'N/A')."</td>
</tr>
<tr>
	<td>Model</td><td>".($cpuInfo['model name'] ?? 'N/A')."</td>
</tr>
<tr>
	<td>Speed</td><td>".($cpuInfo['cpu MHz'] ?? 'N/A')." MHz</td>
</tr>
<tr>
	<td>Address Size</td><td>".($cpuInfo['address sizes'] ?? 'N/A')."</td>
</tr>
<tr>
	<td>Power Saving</td><td>".($cpuInfo['power management'] ?? 'N/A')."</td>
</tr>
<tr>
	<td><b>Memory</b></td><td></td>
</tr>
<tr>
	<td>Total Available</td><td>".($memInfo['MemTotal'] ?? 'N/A')." mB</td>
</tr>
<tr>
	<td>Free</td><td>".($memInfo['MemFree'] ?? 'N/A')." mB</td>
</tr>
<tr>
	<td>Swap Free / Available</td><td>".($memInfo['SwapFree'] ?? 'N/A')." mB / ".($memInfo['SwapTotal'] ?? 'N/A')." mB</td>
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