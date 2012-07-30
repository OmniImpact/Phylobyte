<?php
ini_set("memory_limit","50M");
$image_loc = $_GET['source'];
$strip_image_loc = stripslashes($image_loc);
$ext = substr($image_loc, -3);

$srcImageMD5 = md5_file($strip_image_loc);
 if($_GET['percent']){
 	$cacheType = 'percent';
	$cacheSize = $_GET['percent'];
 	}
 if($_GET['width'] && $_GET['height']){
 	$cacheType = 'stretch';
	$cacheSize = $_GET['width'].'x'.$_GET['height'];
 	}
 if($_GET['size'] || $_GET['hsize']){
 	$cacheType = 'hsize';
	$cacheSize = $_GET['size']+$_GET[''];
 	}
 if($_GET['vsize']){
 	$cacheType = 'vsize';
 	$cacheSize = $_GET['vsize'];
	}
if($_GET['square']){
	$cacheType = 'square';
	$cacheSize = $_GET['square'];
	}

//create CACHE name
$cacheName = $srcImageMD5.'.'.$cacheType.'.'.$cacheSize;
//check if CACHE exists
if(is_file('../cache/'.$cacheName)){
//if CACHE exists, use it
if(strtolower($ext) == 'jpg'){
 $header ='Content-type: image/jpeg';
 }elseif(strtolower($ext == 'png')){
 $header ='Content-type: image/png';
 }elseif(strtolower($ext) == 'gif'){
 $header ='Content-type: image/gif';
 }
 
header($header);
if(strtolower($ext) == 'jpg'){
 $cacheImg = imagecreatefromjpeg('../cache/'.$cacheName);
 ImageJpeg($cacheImg, null, -1);
 }elseif(strtolower($ext == 'png')){
 $cacheImg = imagecreatefrompng('../cache/'.$cacheName);
 ImagePng($cacheImg, null, -1);
 }elseif(strtolower($ext) == 'gif'){
 $cacheImg = imagecreatefromgif('../cache/'.$cacheName);
 ImageGif($cacheImg, null, -1);
 }
 

//else create image, write to CACHE at end
}else{

if(strtolower($ext) == 'jpg'){
 $srcImg = imagecreatefromjpeg("$strip_image_loc");
 $header ='Content-type: image/jpeg';
 }elseif(strtolower($ext == 'png')){
 $srcImg = imagecreatefrompng("$strip_image_loc");
 $header ='Content-type: image/png';
 }elseif(strtolower($ext) == 'gif'){
 $srcImg = imagecreatefromgif("$strip_image_loc");
 $header ='Content-type: image/gif';
 }
 $origWidth = imagesx($srcImg);
 $origHeight = imagesy($srcImg);
 
 
 if($_GET['percent']){
 	$newHeight = $origHeight * ($_GET['percent']/100);
 	$newWidth = $origWidth * ($_GET['percent']/100);
 	}
 if($_GET['width'] && $_GET['height']){
 	$newWidth = $_GET['width'];
 	$newHeight = $_GET['height'];
 	}
 if($_GET['size'] || $_GET['hsize']){
 	if($_GET['size']){$makesize = $_GET['size'];}
 	if($_GET['hsize']){$makesize = $_GET['hsize'];}
	$newHeight = $origHeight * ($makesize / $origWidth);
	$newWidth = $makesize;
 	}
 if($_GET['vsize']){
 	$newHeight = $_GET['vsize'];
 	$newWidth = $origWidth * ($newHeight / $origHeight);
	}
if($_GET['square']){
	if($origHeight >= $origWidth){
 		$newHeight = $_GET['square'];
 		$newWidth = $origWidth * ($_GET['square'] / $origHeight);
		}
	if($origWidth > $origHeight){
		$newWidth = $_GET['square'];
		$newHeight = $origHeight * ($_GET['square'] / $origWidth);
		}
	}


 $newImg = imagecreatetruecolor($newWidth, $newHeight);
 imagecopyresampled($newImg, $srcImg, 0, 0, 0, 0, $newWidth, $newHeight, $origWidth, $origHeight);

header($header);

if(strtolower($ext) == 'jpg'){
 ImageJpeg($newImg, null, -1);
 ImageJpeg($newImg, '../cache/'.$cacheName);
 }elseif(strtolower($ext == 'png')){
 ImagePng($newImg, null, -1);
 ImagePng($newImg, '../cache/'.$cacheName);
 }elseif(strtolower($ext) == 'gif'){
 ImageGif($newImg, null, -1);
 ImageGif($newImg, '../cache/'.$cacheName);
 }
 
ImageDestroy($srcImg);
ImageDestroy($newImg);

}

?>