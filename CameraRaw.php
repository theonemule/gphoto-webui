<?php

/**
* A class that can fetch preview images from camera raw files.
*
* In order to use this class you'll need a few php mods and 
* some other software installed on your server.
*
* - imagemagick (Manage images)
* - ufraw (Reading and converting raw files)
* - exiv2 (Extracting previews from raw files)
* 
*/
class CameraRaw {
	/**
	* @var	array	an array containing popular raw file format extensions
	*/	
	private static $rawExtensions = array('.ari','.arw','.bay','.crw','.cr2','.cap','.dcs','.dcr','.dng','.drf','.eip','.erf','.fff','.iiq','.k25','.kdc','.mdc','.mef','.mos','.mrw','.nef','.nrw','.obm','.orf','.pef','.ptx','.pxn','.r3d','.raf','.raw','.rwl','.rw2','.rwz','.sr2','.srf','.srw', '.x3f');
	
	/**
	* Checks if a file exists based on the filepath.
	*
	* @param  	string	$filePath	the path to the file
	* @return 	bool	returns true if the file exists
	*/
	private static function checkFile($filePath) {
		if(file_exists($filePath)) {
			return true;
		}

		return false;
	}

	/**
	* Checks if a file is a raw file based on file extension.
	*
	* @param  	string	$filePath	the path to the file
	* @return 	bool	returns true if the file is a raw type
	*/
	public static function isRawFile($filePath) {
		$fileExtension = '.' . pathinfo($filePath, PATHINFO_EXTENSION);
		if(in_array(strToLower($fileExtension), self::$rawExtensions)) {
			return true;
		}

		return false;
	}

	/**
	* Fetches all preview types that are embedded in an image.
	*
	* @param  	string	$filePath	the path to the file
	* @return 	array	all the detected previews.
	*/
	public static function embeddedPreviews($filePath) {
		if(!self::checkFile($filePath)) {
			throw new \InvalidArgumentException('Incorrect filepath given');
		}

		$previews = array();
		exec('exiv2 -pp "'.$filePath.'"', $previews);
		return $previews;
	}

	/**
	* Generates a new jpg image with new sizes from an already existing file.
	* (This will also work on raw files, but it will be exceptionally
	* slower than extracting the preview).
	*
	* @param  	string	$sourceFilePath	the path to the original file
	* @param  	string	$targetFilePath	the path to the target file
	* @param  	int		$width			the max width
	* @param  	int		$height			the max height of the image
	* @param  	int		$quality		the quality of the new image (0-100)
	*
	* @throws \InvalidArgumentException
	*
	* @return void.
	*/
	public static function generateImage($sourceFilePath, $targetFilePath, $width, $height, $quality = 60) {
		if(!self::checkFile($sourceFilePath)) {
			throw new \InvalidArgumentException('Incorrect filepath given');
		}

		$im = new \Imagick($sourceFilePath);
		$im->setImageFormat('jpg');
		$im->setImageCompressionQuality($quality); 
    		$im->stripImage(); 
		$im->thumbnailImage($width, $height, true);
		$im->writeImage($targetFilePath);
		$im->clear();
		$im->destroy();
	}

	/**
	* Extracts a preview image from an image and saves the
	* image to the designated path.
	*
	* @param  	string	$sourceFilePath	the path to the original file
	* @param  	string	$targetFilePath	the path to the new file
	* @param  	int		$previewNumber	which preview (defaults to the highest preview)
	*
	* @throws \InvalidArgumentException
	* @throws \Exception
	*
	* @return void.
	*/
	public static function extractPreview($sourceFilePath, $targetFilePath, $previewNumber = null) {
		// check that the source file exists.
		if(!self::checkFile($sourceFilePath)) {
			throw new \InvalidArgumentException('Incorrect source filepath given '.$sourceFilePath);
		}

		// fetch the all the preview images (and verify that there indeed exit previews)
		$numberOfPreviews = self::embeddedPreviews($sourceFilePath);
		if(count($numberOfPreviews) == 0) {
			throw new \Exception('No embedded previews detected');
		}

		// default to the last preview
		if(is_null($previewNumber)) {
			$previewNumber = count($numberOfPreviews);
		}

		$previewNumber = intval($previewNumber);
		$filename = pathinfo($sourceFilePath, PATHINFO_FILENAME);
		$return = array();
		
		// generate the preview with exiv2, save it to the temp directory
		exec('exiv2 -ep'.$previewNumber.' -l '.sys_get_temp_dir(). ' "' . $sourceFilePath . '"', $return);
		$previewFilePath = sys_get_temp_dir() . DIRECTORY_SEPARATOR . $filename . '-preview' . $previewNumber . '.jpg';
		
		// check that the preview got saved correctly
		if(!self::checkFile($previewFilePath)) {
			throw new \Exception('No preview file.');
		}

		// copy the preview to the target path and remove the original one in temp
		copy($previewFilePath, $targetFilePath);
		unlink($previewFilePath);
	}
}