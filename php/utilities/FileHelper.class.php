<?php
	/**
	 * FileHelper.class.php
	 * 
	 * This class handles uploaded files and has additional
	 * file system checks before tying into the file system
	 * helper object to permanently save the file.
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
	class FileHelper {
	
		/**
		 * Gets an array of the successfully uploaded files.
		 * 
		 * @access public
		 * @param boolean $blnUploadedOnly Whether the file has to have been uploaded
		 * @return array The uploaded files array
		 * @static
		 */
		static public function getUploadedFiles($blnUploadedOnly = true) {
			$arrFiles = array();
			
			if (!empty($_FILES)) {
				foreach ($_FILES as $strElement=>$arrValue) {
					if (is_array($arrValue['tmp_name'])) {
						foreach ($arrValue['tmp_name'] as $intKey=>$mxdValue) {
							$arrFile = array(
								'name'		=> $_FILES[$strElement]['name'][$intKey],
								'type'		=> $_FILES[$strElement]['type'][$intKey],
								'tmp_name'	=> $_FILES[$strElement]['tmp_name'][$intKey],
								'error'		=> $_FILES[$strElement]['error'][$intKey],
								'size'		=> $_FILES[$strElement]['size'][$intKey],
							);
							
							if ($arrResult = self::validateUploadedFile($arrFile, $blnUploadedOnly)) {
								$arrFiles[$strElement][$intKey] = $arrResult;
							}
						}
					} else {
						if ($arrResult = self::validateUploadedFile($arrValue, $blnUploadedOnly)) {
							$arrFiles[$strElement] = $arrResult;
						}
					}
				}
			}
			
			return $arrFiles;
		}
		
		
		/**
		 * Validates that the file upload was successful and
		 * returns the file data only if successful.
		 *
		 * @access public
		 * @param array $arrFile The file array to validate and format.
		 * @param boolean $blnUploadedOnly Whether the file has to have been uploaded
		 * @return array The file data
		 * @static
		 */
		static public function validateUploadedFile($arrFile, $blnUploadedOnly = true) {
			if ($arrFile['error']) {
				switch ($arrFile['error']) {
					case UPLOAD_ERR_INI_SIZE:
					case UPLOAD_ERR_FORM_SIZE:
						$strError = AppLanguage::translate('The filesize exceeds the maximum allowed filesize');
						break;
						
					case UPLOAD_ERR_PARTIAL:
						$strError = AppLanguage::translate('The file was only partially uploaded');
						break;
						
					case UPLOAD_ERR_NO_TMP_DIR:
						$strError = AppLanguage::translate('Invalid temporary directory');
						break;
						
					case UPLOAD_ERR_CANT_WRITE:
						$strError = AppLanguage::translate('The file was not written to disk');
						break;
						
					default:
						$strError = null;
						break;
				}
			
				if ($strError) {
					trigger_error(AppLanguage::translate('There was an error uploading the file: %s', $strError));
					return false;
				}				
			} else {
				if ($blnUploadedOnly && !is_uploaded_file($arrFile['tmp_name'])) {
					trigger_error(AppLanguage::translate('Invalid file - It must be an uploaded file'));
					return false;
				} 
				
				$arrFile['tmp_name'] = realpath($arrFile['tmp_name']);
				return $arrFile;
			}
		}
		
		
		/**
		 * Moves the uploaded file from its temporary location
		 * to the final location. If the file isn't an uploaded
		 * file it's copied to the new location rather than moved.
		 * 
		 * @access public
		 * @param string $strTempPath The file's temporary location
		 * @param string $strFilePath The filepath where the file should be saved
		 * @param boolean $blnOverwrite Whether the file can overwrite an existing file
		 * @return boolean True if the file was saved successfully
		 * @static
		 */
		static public function saveUploadedFile($strTempPath, $strFilePath, $blnOverwrite = false) {
			if (!empty($strFilePath)) {
				$objFileSystem = AppRegistry::get('FileSystem');
				if (($blnOverwrite == true && $objFileSystem->isFile($strFilePath)) || !$objFileSystem->pathExists($strFilePath)) {
					if (is_uploaded_file($strTempPath)) { 
						if ($objFileSystem->moveFile($strTempPath, $strFilePath, true)) {
							$objFileSystem->setFilePerms($strFilePath);
							return true;
						}
					} else {
						if ($objFileSystem->copyFile($strTempPath, $strFilePath, true)) {
							$objFileSystem->setFilePerms($strFilePath);
							return true;
						}
					}
				} else {
					trigger_error(AppLanguage::translate('That file exists already and cannot be overwritten'));
				}
			}
			
			return false;
		}
		
		
		/**
		 * Returns the file extension for the file if it's a
		 * valid image. Additional security precautions should
		 * still be used in conjunction with this, and relying
		 * on the mime type is a last resort bad idea.
		 *
		 * @access public
		 * @param array $arrFile The file array to validate
		 * @param array $arrAllowedTypes The allowed image types (eg. gif, jpg, png)
		 * @return string The file extension
		 * @static
		 */
		static public function isValidImage($arrFile, $arrAllowedTypes) {
			$intImageType = $strImageType = null;
			
			if (function_exists('exif_imagetype')) {
				$intImageType = exif_imagetype($arrFile['tmp_name']);
			} else if (function_exists('getimagesize')) {
				$arrSize = getimagesize($arrFile['tmp_name']);
				$intImageType = $arrSize[2];
				unset($arrSize);
			} else {
				$strImageType = $arrFile['type'];
			}
			
			if ($intImageType == IMAGETYPE_GIF || $strImageType == 'image/gif') {
				$strExt = 'gif';
			} else if ($intImageType == IMAGETYPE_JPEG || $strImageType == 'image/jpeg' || $strImageType == 'image/pjpeg') {
				$strExt = 'jpg';
			} else if ($intImageType == IMAGETYPE_PNG || $strImageType == 'image/png') {
				$strExt = 'png';
			} else {
				$strExt = null;
			}
			
			if (in_array($strExt, $arrAllowedTypes)) {
				return $strExt;
			}
		}
	}