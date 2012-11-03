<?php

class pcontent{

	static $pDB;

	/**
	 * Constructor for phylobyte Web Content class.
	 **/
	function __construct(){
		$this->pDB = $GLOBALS['PHYLOBYTEDB'];

		if(!is_dir('../data/images')){
			if(mkdir('../data/images')){
				phylobyte::messageAddNotification('Successfully initialized image storage.');
			}else{
				phylobyte::messageAddError('Could not initialize image storage; you will not be able to upload any images.');
			}
		}
	}

	/**
	 * Image management functions
	 **/

	private $colDir = '../data/images/';

	function images_collection_add($collectionName){
		if(@mkdir($this->colDir.$collectionName)){
			phylobyte::messageAddNotification('Successfully created new collection.');
			return true;
		}else{
			phylobyte::messageAddError('There was a problem creating the new collection.');
			return false;
		}
	}

	function images_collection_delete($collectionName){
		if(@rmdir($this->colDir.$collectionName)){
			phylobyte::messageAddNotification('Successfully deleted collection.');
			return true;
		}else{
			phylobyte::messageAddError('There was a problem deleting the collection.');
			return false;
		}
	}

	function images_collection_list($template = null){

		$possibleCollections = scandir($this->colDir);
		$collections = null;

		//phylobyte::messageAddDebug(print_r($possibleCollections, true));

		if($template != null){
		
			//handle template
			$result = null;
			$i = 0;
			foreach($possibleCollections as $possibleCollection) {
				$i++;
				if(is_dir($this->colDir.$possibleCollection) && $possibleCollection != '.' && $possibleCollection != '..'){
					$collections[] = $possibleCollection;
					$needles = array(
						'**num',
						'**Collection name',
						'**Number of images',
						'**PRINT ARRAY**'
					);
					$replacements = array(
						$i,
						$possibleCollection,
						'#?',
						print_r($collections, true)
					);
					$result.=str_replace($needles, $replacements, $template);
				}
			}
			return $result;

		}else{
			return $collections;
		}
		
	}
	
}

$GLOBALS['PCON'] = new pcontent;

?>
