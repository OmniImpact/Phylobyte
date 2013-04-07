<?php
session_start();

include('plugins/oi_mobilesupport.php');
$MOBILE = new oi_mobilesupport;

require_once('api.class.php');
$API = new phylobyteAPI;
?>


<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">

<html xmlns="http://www.w3.org/1999/xhtml">

<head>

<title>Phylobyte</title>


<link rel="stylesheet" type="text/css" href="style/oi_reset.css" />
<link rel="stylesheet" type="text/css" href="style/style_base.css" />

<script src="//ajax.googleapis.com/ajax/libs/jquery/1.8.3/jquery.min.js"></script>
<script type="text/javascript" src="http://cdn.kendostatic.com/2012.3.1315/js/kendo.all.min.js"></script>

</head>

<body>

<h3><a href="admin"></a>Admin</a></h3>

</body>

</html> 

