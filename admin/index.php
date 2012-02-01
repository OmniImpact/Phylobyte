<?php
session_start();
include('../plugins/phylobyte_tr.php');
$GLOBALS['MESSAGES'] = new tinyRegistry;
$GLOBALS['MESSAGES']->open('__pmessages');
include('oi_mobilesupport.php');
include('phylobyte.php');
$MS = new oi_mobilesupport;
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
		<link href="oi_reset.css" rel="stylesheet" type="text/css" /> 
		<link href="style.css" rel="stylesheet" type="text/css" />
		<?php $MS->addMobileStyle(); $MS->setScale(); ?>
		<link rel="icon" href="gfx/favicon.ico">
		<?php echo $PHYLOBYTE->headArea;?>
		<meta http-equiv="Content-Type" content="text/html;charset=utf-8" /> 
		<meta name="author" content="Daniel Stephen Marcus" /> 
		<meta name="keywords" content="Omni Impact Small Business Services, graphics, design, websites" /> 
		<meta name="description" content="Omni Impact provides high quality and cost effective services for small and upstart businesses including website and graphics design, consultation, branding, and more." /> 
		<script src="../plugins/mootools-core-1.4.0-full-nocompat.js" type="text/javascript"></script>
	</head> 
<body>

<div class="header">
<div class="top">
	<a href="?"><img src="gfx/logo_white_mono.png"/></a>
<div class="headertext">
<?php echo date('l F jS, Y').'&nbsp;&nbsp;&nbsp;&nbsp;'.date('g:i ').'<span style="font-size: 7pt;">'.date('A').'</span>'; ?>
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
	&nbsp; Message Pile &nbsp;
	</div>
	<script type="text/javascript">
	window.addEvent('domready', function() {
		var originalheight = document.getElementById('messagebox').offsetHeight;
		$('messageboxtab').addEvent('click', function(){
			if(document.getElementById('messagebox').offsetHeight > 6){
				$('messagebox').morph({
				'height': '0',
				'padding-bottom': '0'
				});
			}else{
				$('messagebox').morph({
				'height': originalheight
				});
			}
		});
		if(originalheight >= 10){
			$('messagebox').set('styles', {
			'padding-bottom': '5pt'
			});
			originalheight = document.getElementById('messagebox').offsetHeight;
			setTimeout('$(\'messagebox\').morph({\'height\': \'0\',\'padding-bottom\': \'0\'});',3000);
		}else{
			originalheight = 0;
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
<? }else{ echo($PHYLOBYTE->mobileNav); }?>

<div style="float: none; clear: both; height: 0; overflow: hidden;">&nbsp;</div>
<div class="footer">
	Phylobyte is &copy;2011 Daniel S. Marcus / <a href="http://omniimpact.com/">Omni Impact</a> under terms of the <a href="http://www.gnu.org/copyleft/gpl.html">GNU/GPL v3</a> | <?php $MS->mobileToggle(); ?>
</div>

</body>
</html>