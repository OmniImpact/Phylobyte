<?php

/*

Omni Impact Mobile Support Helper Script

Published under the GNU/GPL v3

Copyright 2010-2011 Daniel Marcus / OmniImpact

	This program is free software: you can redistribute it and/or modify
	it under the terms of the GNU General Public License as published by
	the Free Software Foundation, either version 3 of the License, or
	(at your option) any later version.

	This program is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
	GNU General Public License for more details.

	You should have received a copy of the GNU General Public License
	along with this program.  If not, see <http://www.gnu.org/licenses/>.

This script provides mobile website support for over 20 detectable mobile browsers.
This script provides convenience functions for make a mobile-ready website.

== EXAMPLE USE ==

<?php
session_start();
?>
<!-- SOME HTML -->
<?php
//add a mobile stylesheet
$MS = new oi_mobilesupport;
$MS->addMobileStyle();
?>
<!-- SOME MORE HTML -->
Mobile Mode is <?php $MS->mobileEcho('ON', 'OFF'); echo(' turn it '); $MS->mobileToggle('ON', 'OFF'); ?>.

*/

class oi_mobilesupport{

	private $isMobile = false;
	private $useMobile = false;
	private $requestString = null;

	function __construct(){
		//The constructor will check two things;
		//Is the user on a mobile browser?
		//Has the user selected an override?
		//
		//There are three modes.
		//	1 : No override selected
		//	2 : Mobile Forced
		//	3 : Desktop Forced
		
		//if we aren't initialized, set the $_SESSION['mobileoverride']
		//if(!isset($_SESSION['mobileoverride'])) $_SESSION['mobileoverride'] = '1';
		
		//allow 'clear', 'auto', 'mobile', 'phone', 'desktop', 'full' to be used
		if($_REQUEST['mobileoverride'] == 'auto' || $_REQUEST['mobileoverride'] == 'clear') $_REQUEST['mobileoverride'] = '1';
		if($_REQUEST['mobileoverride'] == 'mobile' || $_REQUEST['phone'] == 'phone') $_REQUEST['mobileoverride'] = '2';
		if($_REQUEST['mobileoverride'] == 'desktop' || $_REQUEST['mobileoverride'] == 'full') $_REQUEST['mobileoverride'] = '3';
		
		//check to see if the user is trying to SET an override
		//it will be safe to set the session variable directly, because it is ONLY triggered if we already know
		//that it is one of three safe values
		if($_REQUEST['mobileoverride'] == '1' || $_REQUEST['mobileoverride'] == '2' || $_REQUEST['mobileoverride'] == '3'){
			$_SESSION['mobileoverride'] = $_REQUEST['mobileoverride'];
		}
		
		//is the user on a mobile device?
		if(	stripos($_SERVER['HTTP_USER_AGENT'], "phone") ||
			stripos($_SERVER['HTTP_USER_AGENT'], "droid") ||
			stripos($_SERVER['HTTP_USER_AGENT'], "palm") ||
			stripos($_SERVER['HTTP_USER_AGENT'], "avantgo") ||
			stripos($_SERVER['HTTP_USER_AGENT'], "docomo") ||
			stripos($_SERVER['HTTP_USER_AGENT'], "windows ce") ||
			stripos($_SERVER['HTTP_USER_AGENT'], "minimo") ||
			stripos($_SERVER['HTTP_USER_AGENT'], "netfront") ||
			stripos($_SERVER['HTTP_USER_AGENT'], "wm5 pie") ||
			stripos($_SERVER['HTTP_USER_AGENT'], "xiino") ||
			stripos($_SERVER['HTTP_USER_AGENT'], "opera mobi") ||
			stripos($_SERVER['HTTP_USER_AGENT'], "mobile") ||
			stripos($_SERVER['HTTP_USER_AGENT'], "blackberry") ||
			stripos($_SERVER['HTTP_USER_AGENT'], "opera mini") ||
			stripos($_SERVER['HTTP_USER_AGENT'], "polaris") ||
			strpos($_SERVER['HTTP_USER_AGENT'], "LGE") ||
			strpos($_SERVER['HTTP_USER_AGENT'], "MIDP") ||
			stripos($_SERVER['HTTP_USER_AGENT'], "nintendo") ||
			stripos($_SERVER['HTTP_USER_AGENT'], "nokia") ||
			stripos($_SERVER['HTTP_USER_AGENT'], "samsung") ||
			stripos($_SERVER['HTTP_USER_AGENT'], "tear")
			){
			$this->isMobile = true;
		}
		
		//should we be using a mobile mode?
		if(
			($_SESSION['mobileoverride'] == '2' || $this->isMobile == true) && $_SESSION['mobileoverride'] != '3'
		){
			$this->useMobile = true;
		}else{
			$this->useMobile = false;
		}
		
		//finally, we will build the request string for the current page,
		//but we will strip it of any "mobileoverride" parameter
		//we will need this later to generate a nice switch
		$requestArray = null;
		foreach($_GET as $GETkey => $GETvalue){
			if($GETkey != 'mobileoverride'){
				$requestArray[]= htmlentities($GETkey).'='.htmlentities($GETvalue);
			}
		}
		
		if(is_array($requestArray))$this->requestString = implode('&amp;', $requestArray);
		
	}
	
	//now, a few little convenience functions
	function askMobile(){
		if($this->isMobile) echo('<!-- Mobile Device Detected -->');
	}
	
	function setScale(){
		if($this->useMobile) echo('
		<meta name = "viewport" content = "initial-scale = 1.0, maximum-scale = 1.0">
		<meta name = "viewport" content = "width = device-width">
		');
	}
	
	function isMobile(){
		return $this->isMobile;
	}
	
	function useMobile(){
		return $this->useMobile;
	}
	
	//now for some fun! we know whether the user is on a mobile device
	//and we know if they have forceda mode, so we want to make this convenient
	//we'll use some polymorphic functions to include the stylesheets we want
	//and to generate a toggle to switch modes
	
	function mobileEcho($ifMobile = null, $ifNotMobile = null){
		//echo if applicable
		if($this->useMobile && $ifMobile != null) echo($ifMobile);
		if(!$this->useMobile && $ifNotMobile != null) echo($ifNotMobile);
	}
	
	function mobileReturn($ifMobile = null, $ifNotMobile = null){
		//echo if applicable
		if($this->useMobile && $ifMobile != null) return $ifMobile;
		if(!$this->useMobile && $ifNotMobile != null) return $ifNotMobile;
	}
	
	function addMobileStyle($styleSheetName = null, $lookIn = ''){
	
		//if we're not mobile mode, return false
		if(!$this->useMobile) return false;
	
		//if no $styleSheetName is supplied, try to find it automagically
		//we then populate our own $styleSheetName
		
		//just in case, if $lookIn has a trailing '/' let's remove it
		if($lookIn != '' && substr($lookIn, -1) == '/') $lookIn = substr($lookIn, 0, -1);
		
		if($styleSheetName == null){
			//we will assume (hopefully without making an ass out of you or me...)
			//that mobile stylesheets contain at least one of the phrases
			//'mobile','phone','portable','small', or 'compact' and end with '.css'
			//also, kick out 'old_', '_old', '*~' or '.bak' files
			
			if($lookIn == ''){
				$possibleStyleSheets = scandir('.');
			}else{
				$possibleStyleSheets = scandir($lookIn);
				$lookIn = $lookIn.'/';
			}
						
			foreach($possibleStyleSheets as $possibleStyleSheet){
				if(
					(
						stripos($possibleStyleSheet, 'mobile') !== false ||
						stripos($possibleStyleSheet, 'phone') !== false ||
						stripos($possibleStyleSheet, 'portable') !== false ||
						stripos($possibleStyleSheet, 'small') !== false ||
						stripos($possibleStyleSheet, 'compact') !== false
					) &&
					strtoupper(substr($possibleStyleSheet, -3)) == 'CSS' &&
					!stripos($possibleStyleSheet, '_old') &&
					!stripos($possibleStyleSheet, 'old_') &&
					!stripos($possibleStyleSheet, '.bak') &&
					substr($possibleStyleSheet, -1) != '~'
				){
					$styleSheetName[] = $lookIn.$possibleStyleSheet;
				}
			}
		}
		
		//we can ignore $lookIn now and just include the style sheet(s)
		//if $styleSheetName is an array, we must loop it
		if(is_array($styleSheetName)){
			foreach($styleSheetName as $styleSheet){
				echo('<link rel="stylesheet" type="text/css" href="'.$styleSheet.'"/>'."\n");
			}
		}else{
			echo('<link rel="stylesheet" type="text/css" href="'.$styleSheetName.'"/>'."\n");
		}
	}
	
	function mobileToggle($toMobile = 'Mobile Mode', $toDesktop = 'Desktop Mode'){
		//This will present a TOGGLE between mobile and desktop modes
		//we can use the isMobile and useMobile variables to help
		//we will default to automatic detection when possible
		
		//if we are in a desktop browser...
		if(!$this->isMobile){
			//...and we are not currently mobile
			if(!$this->useMobile){
				$text = $toMobile;
				$target = 'mobile';
			}else{
				//...and we want to go back to desktop mode
				$text = $toDesktop;
				$target = 'auto';
			}
		}else{
			//we are in a mobile browser...
			//...and we want to use Desktop mode
			if($this->useMobile){
				$text = $toDesktop;
				$target = 'desktop';
			}else{
				//...and we want to go back to mobile mode
				$text = $toMobile;
				$target = 'auto';
			}
		}
		
		if($this->requestString != '') $connector = '&amp;';
		echo('<a href="?'.$this->requestString.$connector.'mobileoverride='.$target.'">'.$text.'</a>');
		
	}

}