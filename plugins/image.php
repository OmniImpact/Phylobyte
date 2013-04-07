<?php 
class imagemaker{
	
	private $cachePath = '../data/imgcache/';
	private $imageDB = null;
	private $isImage = false;
	
	private $imageImage = null;
	
	private $imageSource = null;
	private $imageInfo = null;
	
	function __construct(){
	//before running, do any setup
		//$this->cachePath = $_SERVER['DOCUMENT_ROOT'].$this->cachePath;
		if(!is_dir($this->cachePath)){
			if(!mkdir($this->cachePath)){
				die('Script can not function; unable to create cache directory.
						Check that your cache path is set to a writable area.');
			}
		}
		try{
			$dbLoc = $this->cachePath.'images.sqlite';
			$this->imageDB = new PDO('sqlite:'.$dbLoc);
			$imagesTableCreate = 
				"CREATE TABLE IF NOT EXISTS images (
				id INTEGER PRIMARY KEY,
				filename TEXT,
				filesize TEXT,
				filetype TEXT,
				modified TEXT,
				hash TEXT)";
			$requestsTableCreate =
				"CREATE TABLE IF NOT EXISTS requests (
				id INTEGER PRIMARY KEY,
				request TEXT,
				resulthash TEXT)";
			$this->imageDB->exec($imagesTableCreate);
			$this->imageDB->exec($requestsTableCreate);
		}catch(PDOException $e){
			echo $e;
		}
		
	//this runs first, so get any variables and put them into locals
		if(isset($_GET['source'])){
			$imageURL = stripslashes($_GET['source']);
			$this->imageSource = str_replace(' ', '%20', $imageURL);
		}
	}
	
	/* if a image is set, ping it for headers
	 * if we get back headers that indicate a jpeg or png
	 * return true 
	 */
	function isImage(){
		if($this->isImage == true){
			return true;
		}
		if($this->imageSource != null){
			$curlInstance = curl_init($this->imageSource);
			curl_setopt($curlInstance, CURLOPT_NOBODY, true);
			curl_setopt($curlInstance, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($curlInstance, CURLOPT_HEADER, true);
			curl_setopt($curlInstance, CURLOPT_FOLLOWLOCATION, true);
			$response = curl_exec($curlInstance);
			if($response === false){
				curl_close($curlInstance);
				return false;
			}else{
				curl_close($curlInstance);
				$responseArray = preg_split( '/(\r\n){2}|(\r){2}|(\n){2}/', trim($response));
				$response = end($responseArray);
				$headersArray = preg_split('/(\r\n|\r|\n){1}/', $response);
				if(strpos($headersArray[0], '200') === false){
					die('That image is not valid; it was not found on the server.');
					return false;
				}
				$headerArray = null;
				foreach($headersArray as $headerLine){
					$firstColon = strpos($headerLine, ':');
					if($firstColon !== false){
						$headerLineArray = explode(':', $headerLine, 2);
						$headerArray[trim($headerLineArray[0])] = trim($headerLineArray[1]);
					}
				}
				$this->imageInfo = $headerArray;
			}
			if($this->imageInfo['Content-Length'] > 10485760){
				die('That image is not valid; file size is over 10 megabytes: '.
					($this->imageInfo['Content-Length']/1048576).'MiB');
				return false;
			}
			if($this->imageInfo['Content-Type'] == 'image/png' || $this->imageInfo['Content-Type'] == 'image/jpeg'){
				$this->isImage = true;
				$this->cacheImage();
				$this->processImage();
				return true;
			}else{
				print_r($this->imageInfo);
				die('That image is not valid; wrong file type: '.$this->imageInfo['Content-Type']);				
			}
			die('That image is not valid.');
			return false;
		}
		return false;
	}
	
	function cacheImage($useCurl = true){
		$this->imageInfo['fileName'] = substr($this->imageSource, strrpos($this->imageSource, '/')+1);
		$name = $this->imageDB->quote($this->imageInfo['fileName']);
		$size = $this->imageDB->quote($this->imageInfo['Content-Length']);
		$type = $this->imageDB->quote($this->imageInfo['Content-Type']);
		$lastModified = $this->imageDB->quote($this->imageInfo['Last-Modified']);
	//check the database to see if the image is already in the cache
		$checkQuery = "
		SELECT hash FROM images
		WHERE filename=$name AND filesize=$size AND filetype=$type AND modified=$lastModified;";
		$checkQuery = $this->imageDB->prepare($checkQuery);
		$checkQuery->execute();
		$checkResults = $checkQuery->fetchAll();
		
		if(count($checkResults) < 1){
			if($useCurl){
				//write the file to the cache as MD5
				$curlInstance = curl_init($this->imageSource);
				curl_setopt($curlInstance, CURLOPT_HEADER, false);
				curl_setopt($curlInstance, CURLOPT_RETURNTRANSFER, true);
				curl_setopt($curlInstance, CURLOPT_FOLLOWLOCATION, true);
				$rawImage = curl_exec($curlInstance);
				$this->imageInfo['md5'] = md5($rawImage);
				$this->imageImage = $rawImage;
				$putContentResult = file_put_contents($this->cachePath.$this->imageInfo['md5'], $rawImage);
			}
			$hash = $this->imageDB->quote($this->imageInfo['md5']);
			if($putContentResult || !$useCurl){
				//write the image information to the database
				$cacheQuery = "
				INSERT INTO images
				(filename, filesize, filetype, modified, hash)
				VALUES
				($name, $size, $type, $lastModified, $hash);";
				$cacheQuery = $this->imageDB->exec($cacheQuery);
				$this->imageInfo['fromCache'] = 'false';
				return true;
			}else{
				die('There was a problem caching the image.<br/><pre>'.print_r($this->getImageInfo(), true).'</pre>');
			}
		}else{
			//instead, get the hash from the database and store it to info
			$this->imageInfo['md5'] = $checkResults[0]['hash'];
			$this->imageInfo['fromCache'] = 'true';
		}
	}
	
	function processImage(){
		//if given parameters, process the image
		//each of these functions should update the database and image info
		
		//resize
		$this->processSize();
		
		return true;
	}
	
	function processSize(){
		$request = null;
		$size = null;
		$method = null;
		
		//compose a request from the hash, method, and size
		
		if(ctype_digit($_GET['size'])){
			$size = $_GET['size'];
		}else{
			//we're not being asked to size the image
			return true;
		}
		if(isset($_GET['method'])){
			switch($_GET['method']){
				
				case 'vfit':
					$method = 'vfit';
				break;
				
				case 'hfit':
					$method = 'hfit';
				break;
				
				case 'ofit':
					$method = 'ofit';
				break;
				
				case 'percent':
					$method = 'percent';
				break;
				
				default:
					$method = 'fit';
				break;
			}
		}else{
			$method = 'fit';
		}
		if(ctype_digit($size)){
			$request = 'hash_'.$this->imageInfo['md5'].':size_'.$size.':method_'.$method;
			
			//look for the request in the database
			$quotedRequest = $this->imageDB->quote($request);
			$searchQuery = "
			SELECT * FROM requests
			WHERE request=$quotedRequest;
			";
			$searchQuery = $this->imageDB->prepare($searchQuery);
			$searchQuery->execute();
			$searchResults = $searchQuery->fetchAll(PDO::FETCH_ASSOC);
			
			if(count($searchResults) == 1){
				//request has been processed
				//adjust the info and return
				
				$resultHash = $this->imageDB->quote($searchResults[0]['resulthash']);
				
				$infoQuery = "
				SELECT * FROM images
				WHERE hash=$resultHash;
				";
				$infoQuery = $this->imageDB->prepare($infoQuery);
				$infoQuery->execute();
				$infoResults = $infoQuery->fetchAll(PDO::FETCH_ASSOC);
				
				$this->imageInfo['fileName'] = $infoResults[0]['filename'];
				$this->imageInfo['Content-Length'] = $infoResults[0]['filesize'];
				$this->imageInfo['Content-Type'] = $infoResults[0]['filetype'];
				$this->imageInfo['Last-Modified'] = $infoResults[0]['modified'];
				$this->imageInfo['md5'] = $infoResults[0]['hash'];
				
				return  true;
			}else{
				//process the image
				
				if($this->imageInfo['Content-Type'] == 'image/png'){
					$unsizedImg = imagecreatefrompng($this->getImageFilePath());
				}elseif($this->imageInfo['Content-Type'] == 'image/jpeg'){
					$unsizedImg = imagecreatefromjpeg($this->getImageFilePath());
				}
				
				$originalWidth = imagesx($unsizedImg);
				$originalHeight = imagesy($unsizedImg);
				
				header('Image-Original-Dimensions: '.$originalWidth.'x'.$originalHeight);
				//based on the method, determine the target size
				
				header('Image-Scaling-Algorithm: '.$method);
				
				if($method == 'fit'){
					if($originalHeight >= $originalWidth){
						$method = 'vfit';
					}else{
						$method = 'hfit';
					}
				}
				
				if($method == 'ofit'){
					if($originalHeight <= $originalWidth){
						$method = 'vfit';
					}else{
						$method = 'hfit';
					}
				}
				
				$targetWidth = null;
				$targetHeight = null;
				
				switch($method){
				
					case 'vfit':
						//fit inside vertical limit
						$targetWidth = $originalWidth * ($size / $originalHeight);
						$targetHeight = $size;
					break;
					
					case 'hfit':
						//fit insized horizontal limit
						$targetWidth = $size;
						$targetHeight = $originalHeight * ($size / $originalWidth);
					break;
					
					case 'percent':
						//percentage instead
						$targetWidth = $originalWidth*($size/100);
						$targetHeight = $originalHeight*($size/100);
					break;
					
				}
				
				
				header('Image-Target-Dimensions: '.$targetWidth.'x'.$targetHeight);
				
				$resultImage = imagecreatetruecolor($targetWidth, $targetHeight);
				
				imagealphablending($resultImage, true);
				imagesavealpha($resultImage, true);
				
				$transparentColor = imagecolorallocatealpha($resultImage, 128, 128, 128, 127);
				imagefill($resultImage, 0, 0, $transparentColor);
				imagecolortransparent($resultImage, $transparentColor);
				
				imagecopyresampled($resultImage, $unsizedImg, 0, 0, 0, 0, $targetWidth, $targetHeight, $originalWidth, $originalHeight);
				
				//write the result
				if($this->writeResultImage($resultImage)){
					
					//adjust info
					$this->imageInfo['md5'] = md5(file_get_contents($this->cachePath.'TEMP'));
					header('Image-Result-MD5: '.$this->imageInfo['md5']);
					rename($this->cachePath.'TEMP', $this->getImageFilePath());
					$this->imageInfo['Content-Length'] = filesize($this->getImageFilePath());
					
					//write results to database
					$this->cacheImage(false);
					
					$requestInsertQuery = "
					INSERT INTO requests
					(request, resulthash)
					VALUES
					($quotedRequest, '".$this->imageInfo['md5']."');";
					if($this->imageDB->exec($requestInsertQuery)){
						return true;
					}
				}
			}
			
		}
		return true;
	}
	
	function purgeDB($md5){
		$md5 = $this->imageDB->quote($md5);
		$purgeRequestQuery = $this->imageDB->prepare("
			DELETE FROM requests WHERE resulthash=$md5;
		");
		$purgeRequestQuery->execute();
		$purgeImagesQuery = $this->imageDB->prepare("
			DELETE FROM images WHERE hash=$md5;
		");
		$purgeImagesQuery->execute();
	}
	
	function writeResultImage($resultImage){
		if($this->imageInfo['Content-Type'] == 'image/png'){
			if(ImagePng($resultImage, $this->cachePath.'TEMP')){
				return true;
			}else{
				return false;
			}
		}elseif($this->imageInfo['Content-Type'] == 'image/jpeg'){
			if(ImageJpeg($resultImage, $this->cachePath.'TEMP')){
				return true;
			}else{
				return false;
			}
		}
	}
	
	function getImageInfo(){
		return $this->imageInfo;
	}
	
	function getImageFilePath(){
		return $this->cachePath.$this->imageInfo['md5'];
	}
	
	function getCacheFilePath(){
		return $this->cachePath;
	}
	
	function emptyCache(){
		$files = glob($this->cachePath.'*');
		foreach($files as $file){
			if(is_file($file)) unlink($file);
		}
	}
	
}

$SM = new imagemaker;

if(!$SM->isImage()){
	
	if($_GET['do'] == 'quickclear'){
		$SM->emptyCache();
		die('true');
	}
	
	if($_GET['do'] == 'empty_cache'){
		$SM->emptyCache();
	}
	
	//fall back to image browser mode
	echo('
	<h4><a href="?">Image Browser</a></h4>
	<p>These images already exist in the cache. (<a href="?do=empty_cache">clear</a>)</p>
	<pre>
	'.print_r(scandir($SM->getCacheFilePath()), true).'
	</pre>');
	
}else{
// Deliver the image
	$imageInfo = $SM->getImageInfo();
	header('Cache-Control: no-cache, must-revalidate');
	header('Content-Type: '.$imageInfo['Content-Type']);
	header('Content-Length: '.$imageInfo['Content-Length']);
	header('Last-Modified: '.$imageInfo['Last-Modified']);
	header('Content-Disposition: filename="'.$imageInfo['fileName'].'"');
	header('Content-Path-Target: '.$SM->getImageFilePath());
	header('Content-From-Cache: '.$imageInfo['fromCache']);
	if(is_file($SM->getImageFilePath())){
		readfile($SM->getImageFilePath());
	}else{
		//something went wrong, there is a file in the database that isn't in the cache!
		header('Sorry-Purge-Requested: for="'.$imageInfo['md5'].'"');
		$SM->purgeDB($imageInfo['md5']);
	}
}

?>