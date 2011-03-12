<?php
	require_once('LocalFileSystem.class.php');

	/**
	 * AmazonS3FileSystem.class.php
	 *
	 * The Amazon S3 file system functions. This requires the
	 * Zend Framework.
	 * 
	 * Copyright 2006-2011, Phork Labs. (http://phorklabs.com)
	 *
	 * Licensed under The MIT License
	 * Redistributions of files must retain the above copyright notice.
	 *
	 * @author Elenor Collings <elenor@phork.org>
	 * @license http://www.opensource.org/licenses/mit-license.php The MIT License
	 * @package phork
	 * @subpackage files
	 */
	class AmazonS3FileSystem extends LocalFileSystem {
	
		protected $objS3;
				
		
		/**
		 * Sets up the folder root from the config, and sets up 
		 * the s3 stream. The S3FolderRoot protocol (eg. s3://)
		 * should match the stream wrapper (eg. s3).
		 *
		 * @access public
		 */
		public function __construct() {
			AppLoader::includeExtension('zend/', 'ZendLoader');
	 		ZendLoader::includeClass('Zend_Service_Amazon_S3');
			
			if (!AppConfig::get('S3AccessKey', false) || !AppConfig::get('S3SecretKey', false)) {
				AppConfig::load('amazon');
			}
			
			$this->strFilesDir = AppConfig::get('S3FolderRoot');
			$this->strPublicUrl = AppConfig::get('S3FilesUrl', false);
			
			$this->objS3 = new Zend_Service_Amazon_S3(AppConfig::get('S3AccessKey'), AppConfig::get('S3SecretKey'));
			$this->objS3->registerStreamWrapper('s3');
		}
		
		
		/**
		 * This functionality has been removed.
		 *
		 * @access public
		 * @param string $strDirPath The directory to chmod
		 * @param integer $intMode The directory permissions
		 * @return boolean True on success
		 */
		public function setDirectoryPerms($strDirPath, $intMode = null) { 
			return true;
		}
		
		/**
		 * This functionality has been removed.
		 *
		 * @access public
		 * @param string $strFilePath The file to chmod
		 * @param integer $intMode The file permissions
		 * @return boolean True on success
		 */
		public function setFilePerms($strFilePath, $intMode = null) {
			return true;
		}
		
		
		/**
		 * Moves or renames a file. Has special handling to copy temp
		 * files because it's not possible to move files across streams.
		 *
		 * @access public
		 * @param string $strFilPath The file to move
		 * @param string $strDestination The new file name / path
		 * @param boolean $blnFromTemp Whether the file is being moved from a temp location
		 * @return boolean True on success
		 */
		public function moveFile($strFilePath, $strDestination, $blnFromTemp = false) {
			$blnResult = false;
			if ($blnFromTemp) {
				if ($blnResult = $this->copyFile($strFilePath, $strDestination, $blnFromTemp)) {
					$this->deleteFile($strFilePath);
				}
			} else {
				$blnResult = parent::moveFile($strFilePath, $strDestination, $blnFromTemp);
			}
			return $blnResult;
		}
		
		
		/**
		 * This functionality has been removed.
		 *
		 * @access public
		 * @param string $strPath The path to translate
		 * @return string The real path
		 */
		public function realPath($strPath) {
			return $strPath;
		}
	}