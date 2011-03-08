<?php
	/**
	 * ImageCreator.class.php
	 *
	 * Creates and manipulates image data using
	 * the GD library.
	 * 
	 * Copyright 2006-2011, Phork Labs. (http://phorklabs.com)
	 *
	 * Licensed under The MIT License
	 * Redistributions of files must retain the above copyright notice.
	 *
	 * @author Elenor Collings <elenor@phork.org>
	 * @license http://www.opensource.org/licenses/mit-license.php The MIT License
	 * @package phork
	 * @subpackage utilities
	 */
	class ImageCreator {
		
		/**
		 * Creates and saves the resized image.
		 *
		 * @access public
		 * @param string $strImagePath The full path to the image being resized
		 * @param string $strResizedPath The full path to save the new image to
		 * @param integer $intWidth The resized image width
		 * @param integer $intHeight The resized image height
		 * @param boolean $blnSkew Whether to skew the image to fit the dimensions exactly
		 * @return boolean True on success
		 * @static
		 */
		static public function resize($strImagePath, $strResizedPath, $intWidth, $intHeight, $blnSkew = false) {
			
			//get the original image info
			if (!($arrImageInfo = getimagesize($strImagePath))) {
				trigger_error(AppLanguage::translate('There was an error getting the original image dimensions'));
				return false;
			}
			
			//get the dimensions of the resized image
			if (!$blnSkew) {
				
				//get the ratio of the original dimensions to the resized dimensions
				$numWidthRatio = $intWidth / $arrImageInfo[0];
				$numHeightRatio = $intHeight / $arrImageInfo[1];
				
				//use the smaller ratio for resizing to ensure both images are within the bounds
				$numResizeRatio = min($numWidthRatio, $numHeightRatio);
				
				//get the image dimensions to use for the resize
				$intWidth = round($arrImageInfo[0] * $numResizeRatio);
				$intHeight = round($arrImageInfo[1] * $numResizeRatio);
			}
			
			//determine the functions to use based on the type of image
			switch($arrImageInfo[2]) {
				case IMAGETYPE_GIF:
					$strCreateFunction = 'ImageCreateFromGIF';
					$strOutputFunction = 'ImageGIF';
					break;
					
				case IMAGETYPE_JPEG: 
					$strCreateFunction = 'ImageCreateFromJPEG';
					$strOutputFunction = 'ImageJPEG';
					break;
				
				case IMAGETYPE_PNG:
					$strCreateFunction = 'ImageCreateFromPNG';
					$strOutputFunction = 'ImagePNG';
					break;
					
				default:
					trigger_error(AppLanguage::translate('Invalid image type - It must be GIF, JPG or PNG'));
					return false;
			}
			
			//make sure the image create function exists
			if (!function_exists($strCreateFunction) || !function_exists($strOutputFunction)) {
				trigger_error(AppLanguage::translate("The image resizing functions don't exist"));
				return false;
			}
			
			//make sure the image identifier was returned
			if (!($rscImage = $strCreateFunction($strImagePath))) {
				trigger_error(AppLanguage::translate('There was an error handling the original image'));
				return false;
			}
			
			//create a new palette based image; returns an image identifier for a blank image
			$rscResized = function_exists('imagecreatetruecolor') ? imagecreatetruecolor($intWidth, $intHeight) : imagecreate($intWidth, $intHeight);
			
			//make sure the image identifier was returned
			if (!$rscResized) {
				imagedestroy($rscImage);
				
				trigger_error(AppLanguage::translate('There was an error creating the new image file'));
				return false;
			}
			
			//copy and resize the original image to another image
			if (!imagecopyresampled($rscResized, $rscImage, 0, 0, 0, 0, $intWidth, $intHeight, ImageSX($rscImage), ImageSY($rscImage))) {
				imagedestroy($rscImage);
				imagedestroy($rscResized);
				
				trigger_error(AppLanguage::translate('There was an error creating the resized image'));
				return false;
			}
		
			//create the new image
			if (!$strOutputFunction($rscResized, $strResizedPath)) {
				imagedestroy($rscImage);
				imagedestroy($rscResized);
				
				trigger_error(AppLanguage::translate('There was an error saving the resized image'));
				return false;
			}
			
			//free any memory associated with the image identifiers returned by the image creation function
			imagedestroy($rscImage);
			imagedestroy($rscResized);
			
			return true;
		}
	}