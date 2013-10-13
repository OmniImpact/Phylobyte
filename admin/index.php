<?php
session_start();
include('../plugins/phylobyte_tr.php');
$GLOBALS['MESSAGES'] = new tinyRegistry;
$GLOBALS['MESSAGES']->open('__pmessages');
include('phylobyte.php');
include('../plugins/oi_mobilesupport.php');
$MS = new oi_mobilesupport;
$GLOBALS['MS'] = $MS;
$GLOBALS['PHYLOBYTE'] = new phylobyte;
$PHYLOBYTE->build_finish();

//display
?>

<?php echo '<?xml version="1.0" encoding="UTF-8"?> '; ?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN" "http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd"> 
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en"> 
<!-- END XHTML DECLARATIONS --> 
	<head> 
		<title><?php echo $PHYLOBYTE->pageTitle;?></title> 
		<link href="css/oi_reset.css" rel="stylesheet" type="text/css" /> 
		<link href="css/style.css" rel="stylesheet" type="text/css" />
		<?php $MS->addMobileStyle(); $MS->setScale(); ?>
		<link href="//netdna.bootstrapcdn.com/font-awesome/3.2.1/css/font-awesome.css" rel="stylesheet">
		<link rel="icon" href="gfx/favicon.ico">
		<?php echo $PHYLOBYTE->headArea;?>
		<meta http-equiv="Content-Type" content="text/html;charset=utf-8" /> 
		<meta name="author" content="Daniel Stephen Marcus" /> 
		<meta name="keywords" content="Omni Impact Small Business Services, graphics, design, websites" /> 
		<meta name="description" content="Omni Impact provides high quality and cost effective services for small and upstart businesses including website and graphics design, consultation, branding, and more." />
		<script src="//ajax.googleapis.com/ajax/libs/jquery/1.10.2/jquery.min.js"></script>
		<script src="//ajax.googleapis.com/ajax/libs/jqueryui/1.10.3/jquery-ui.min.js"></script>
		<link rel="stylesheet" href="jqueryui_theme_phylobyte/jquery-ui-1.10.3.custom.min.css" />
		<script type="text/javascript">
		jQuery.noConflict();
		</script>
		<script type="text/javascript" src="https://ajax.googleapis.com/ajax/libs/mootools/1.4.5/mootools-yui-compressed.js"></script>
		<script src="//tinymce.cachefly.net/4.0/tinymce.min.js"></script>
	</head> 
<body>

<div class="header">
<div class="top">
	<a href="?"><img src="gfx/logo_white_mono.png" style="max-width: 40%;"/></a>
<div class="headertext" <?php $MS->mobileEcho('style="top: 6px;"');?>>
<?php
$MS->mobileEcho(
date('l F jS, Y').'<br/>'.date('g:i ').'<span style="font-size: 7pt;">'.date('A').'</span>',
date('l F jS, Y').'&nbsp;&nbsp;&nbsp;&nbsp;'.date('g:i ').'<span style="font-size: 7pt;">'.date('A').'</span>'
); ?>
</div>
</div>
	<div id="dropdown">
		<?php echo $PHYLOBYTE->navigationArea;?>
	<div style="float: none; clear: both; height: 0; overflow: hidden;">&nbsp;</div>
	</div>
</div>

<div class="leftcol">
	<?php if($PHYLOBYTE->messageArea != null){ ?>
	<div class="messagebox" id="messagebox">
		<?php echo $PHYLOBYTE->messageArea;?>
	</div>
	<div class="messageboxtab" id="messageboxtab">
	&nbsp; Message Pile &nbsp;<i class="icon-reorder" style="font-size: 80%;">&nbsp;</i>&nbsp;
	</div>
	<script type="text/javascript">
	$('messagebox').set('styles', {
	'padding-bottom': '3pt'
	});
	originalheight = document.getElementById('messagebox').offsetHeight;
	$('messagebox').set('styles', {
		'height': '0',
		'padding-bottom': '0'
	});
	$('messagebox').morph({
		'height': originalheight,
		'padding-bottom': '0'
	});
	//setTimeout('$(\'messagebox\').morph({\'height\': \'0\',\'padding-bottom\': \'0\'});',3000);
	$('messageboxtab').addEvent('click', function(){
		if(document.getElementById('messagebox').offsetHeight > 6){
			$('messagebox').morph({
			'height': '0',
			'padding-bottom': '0'
			});
		}else{
			$('messagebox').morph({
			'height': originalheight,
			'padding-bottom': '0'
			});
		}
	});
	</script>
	<?php } ?>
	<div class="pluginbox home">
		<?php if($PHYLOBYTE->breadcrumbs != null) echo '<div class="breadcrumbs">'.$PHYLOBYTE->breadcrumbs.'</div>';?>
		<div class="padding">
		<?php echo $PHYLOBYTE->pageArea;?>
		</div>
		<div style="float: none; clear: both; height: 0; overflow: hidden;">&nbsp;</div>
	</div>
</div>

<?php if(!$MS->useMobile()){ ?>
<div class="rightcol">
	<div class="rightcolinner">
		<?php echo $PHYLOBYTE->docArea;?>
	</div>
</div>
<?php }else{ echo($PHYLOBYTE->mobileNav); }?>

<div style="float: none; clear: both; height: 0; overflow: hidden;">&nbsp;</div>
<div class="footer">
	Phylobyte is &copy;2013 Daniel S. Marcus / <a href="http://omniimpact.com/">Omni Impact</a> under terms of the <a href="http://www.apache.org/licenses/LICENSE-2.0.html">Apache License 2.0</a> | <?php $MS->mobileToggle(); ?>
</div>

</body>
</html>