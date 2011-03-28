<?php
	require_once('interfaces/FileSystemHandler.interface.php');
	
	/**
	 * LocalFileSystem.class.php
	 *
	 * The local file system functions. All the file
	 * and directory operations take place in the files
	 * directory except for the temp files methods which
	 * work in the temp directory. This is done as a
	 * safety precaution. If you would like forego this
	 * precations then leave the FilesDir config value
	 * empty and work with absolute paths when using
	 * this.
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
	class LocalFileSystem implements FileSystemHandler {
	
		protected $strPublicUrl;
		protected $strFilesDir;
		protected $strTempDir;
		protected $blnTemp;
		protected $blnLenient;
		protected $blnAbsolute;
		
		const DIR_PERMS = 0755;
		const FILE_PERMS = 0644;
		
	
		/**
		 * Sets up the folder base and root from the config.
		 *
		 * @access public
		 */
		public function __construct() {
			$this->strFilesDir = AppConfig::get('FilesDir');
			$this->strTempDir = AppConfig::get('TempDir', false);
			$this->strPublicUrl = AppConfig::get('FilesUrl', false);
		}
	
	
		/*****************************************/
		/**     DIRECTORY METHODS               **/
		/*****************************************/
		
	
		/**
		 * Checks if a directory exists and is a directory.
		 *
		 * @access public
		 * @param string $strDirPath The directory to check
		 * @param boolean $blnTemp Whether to check the temp directory
		 * @return boolean True if directory
		 */
		public function isDirectory($strDirPath, $blnTemp = false) {
			return is_dir($this->cleanPath($strDirPath, $blnTemp));
		}
		
		
		/**
		 * Creates the directory.
		 *
		 * @access public
		 * @param string $strDirPath The directory to create
		 * @param integer $intMode The directory permissions
		 * @param boolean $blnRecursive Whether to create the directory's parents if they don't exist
		 * @return boolean True on success
		 */
		public function createDirectory($strDirPath, $intMode = null, $blnRecursive = false) {
			if (!($blnResult = @mkdir($this->cleanPath($strDirPath), $intMode ? $intMode : self::DIR_PERMS, $blnRecursive))) {
				trigger_error(AppLanguage::translate('There was an error creating the directory %s', $strDirPath));
			}
			return $blnResult;
		}
		
		
		/**
		 * Moves or renames a directory.
		 *
		 * @access public
		 * @param string $strDirPath The directory to move
		 * @param string $strDestination The new directory name / path
		 * @param boolean $blnFromTemp Whether the directory is being moved from a temp location
		 * @return boolean True on success
		 */
		public function moveDirectory($strDirPath, $strDestination, $blnFromTemp = false) {
			if ($blnResult = $this->isDirectory($strDirPath, $blnFromTemp)) {
				if (!($blnResult = @rename($this->cleanPath($strDirPath, $blnFromTemp), $this->cleanPath($strDestination)))) {
					trigger_error(AppLanguage::translate('There was an error moving the directory %s to %s', $strDirPath, $strDestination));
				}
			} else {
				trigger_error(AppLanguage::translate('The directory to move %s is not a valid directory', $strDirPath));
			}
			return $blnResult;
		}
		
		
		/**
		 * Deletes a directory. The directory must be
		 * empty.
		 *
		 * @access public
		 * @param string $strDirPath The directory to delete
		 * @param boolean $blnSuppress Whether to suppress errors
		 * @return boolean True on success
		 */
		public function deleteDirectory($strDirPath, $blnSuppress = false) {
			if (!($blnResult = @rmdir($this->cleanPath($strDirPath)))) {
				if (!$blnSuppress) {
					trigger_error(AppLanguage::translate('There was an error deleting the directory %s', $strDirPath));
				}
			}
			return $blnResult;
		}
		
		
		/**
		 * Sets the read, write and execute permissions
		 * for the directory.
		 *
		 * @access public
		 * @param string $strDirPath The directory to chmod
		 * @param integer $intMode The directory permissions
		 * @return boolean True on success
		 */
		public function setDirectoryPerms($strDirPath, $intMode = null) {
			if (!($blnResult = @chmod($this->cleanPath($strDirPath), $intMode ? $intMode : self::DIR_PERMS))) {
				trigger_error(AppLanguage::translate('There was an error changing the permissions of the directory %s', $strDirPath));
			}
			return $blnResult;
		}
		
		
		/**
		 * Returns all the files in the directory.
		 *
		 * @access public
		 * @param string $strDirPath The directory to read from
		 * @return array The files in the directory
		 */
		public function listFiles($strDirPath) {
			$arrResult = array();
			if ($rscDir = @opendir($this->cleanPath($strDirPath))) {
				while (($strFile = readdir($rscDir)) !== false) {
					if ($strFile != '.' && $strFile != '..') {
						$arrResult[] = $strFile;
					}
				}
				closedir($rscDir);
			} else {
				trigger_error(AppLanguage::translate('There was an error opening the directory %s', $strDirPath));
			}
			return $arrResult;
		}
		
		
		/*****************************************/
		/**     FILE METHODS                    **/
		/*****************************************/
		
		
		/**
		 * Checks if a file exists and is a file.
		 *
		 * @access public
		 * @param string $strFilePath The file to check
		 * @param boolean $blnTemp Whether to check the temp directory
		 * @return boolean True if file
		 */
		public function isFile($strFilePath, $blnTemp = false) {
			return is_file($this->cleanPath($strFilePath, $blnTemp));
		}
		
		
		/**
		 * Reads the contents of a file. Binary safe.
		 *
		 * @access public
		 * @param string $strFilePath The file to read
		 * @param boolean $blnSuppress Whether to suppress errors
		 * @return string The file contents
		 */
		public function readFile($strFilePath, $blnSuppress = false) {
			if (($strResult = @file_get_contents($this->cleanPath($strFilePath))) === false) {
				if (!$blnSuppress) {
					trigger_error(AppLanguage::translate('There was an error reading the file %s', $strFilePath));
				}
			}
			return $strResult;
		}
		
		
		/**
		 * Outputs the contents of a file. Binary safe.
		 *
		 * @access public
		 * @param string $strFilePath The file to output
		 * @return boolean True on success 
		 */
		public function outputFile($strFilePath) {
			if (!($blnResult = readfile($this->cleanPath($strFilePath))) !== false) {
				trigger_error(AppLanguage::translate('There was an error outputting the file %s', $strFilePath));
			}
		}
		
		
		/**
		 * Writes a new file. Binary safe.
		 *
		 * @access public
		 * @param string $strFilePath The file to write to
		 * @param string $strContents The contents to write
		 * @param integer $intMode The file permissions
		 * @return boolean True on success
		 */
		public function createFile($strFilePath, $strContents, $intMode = null) {
			$blnResult = false;
			if (($intBytes = file_put_contents($this->cleanPath($strFilePath), $strContents)) !== false) {
				if ($this->setFilePerms($strFilePath, $intMode)) {
					$blnResult = true;
				}
			} else {
				trigger_error(AppLanguage::translate('There was an writing to the file %s', $strFilePath));
			}
			return $blnResult;
		}
		
		
		/**
		 * Writes to a temporary file with an automatically
		 * determined name.
		 *
		 * @access public
		 * @param string $strContents The contents to write to the temp file
		 * @return string The name of the temp file excluding the path
		 */
		public function createTempFile($strContents = null) {
			if ($strFilePath = tempnam($strTempDir = $this->getTempDirectory(), 'phk')) {
				$strFileName = str_replace($strTempDir, '', $strFilePath);
				
				if (!$this->blnTemp) {
					$blnClearTemp = true;
					$this->useTemp();
				}
				
				if ($strContents && !$this->appendFile($strFileName, $strContents)) {
					$strFileName = null;
				}
				
				empty($blnClearTemp) || $this->clearTemp();
				return $strFileName;
			}
		}
		
		
		/**
		 * Appends contents to a file. Binary safe.
		 *
		 * @access public
		 * @param string $strFilePath The file to write to
		 * @param string $strContents The contents to write
		 * @return boolean True on success
		 */
		public function appendFile($strFilePath, $strContents) {
			$blnResult = false;
			if ($this->isFile($strFilePath)) {
				if ($rscFile = @fopen($this->cleanPath($strFilePath), 'ab')) {
					if (fwrite($rscFile, $strContents)) {
						$blnResult = true;
					} else {
						trigger_error(AppLanguage::translate('There was an error writing to %s', $strFilePath));
					}
					fclose($rscFile);
				} else {
					trigger_error(AppLanguage::translate('There was an error opening the file %s', $strFilePath));
				}
			} else {
				trigger_error(AppLanguage::translate('Invalid file to append %s', $strFilePath));
			}
			return $blnResult;
		}
		
		
		/**
		 * Copies a file.
		 *
		 * @access public
		 * @param string $strFilePath The file to copy
		 * @param string $strDestination The copy's destination
		 * @param boolean $blnFromTemp Whether the file is being copied from a temp location
		 * @return boolean True on success
		 */
		public function copyFile($strFilePath, $strDestination, $blnFromTemp = false) {
			if (!($blnResult = @copy($this->cleanPath($strFilePath, $blnFromTemp), $this->cleanPath($strDestination)))) {
				trigger_error(AppLanguage::translate('There was an error copying the file %s to %s', $strFilePath, $strDestination));
			}
			return $blnResult;
		}
				
		
		/**
		 * Moves or renames a file.
		 *
		 * @access public
		 * @param string $strFilPath The file to move
		 * @param string $strDestination The new file name / path
		 * @param boolean $blnFromTemp Whether the file is being moved from a temp location
		 * @return boolean True on success
		 */
		public function moveFile($strFilePath, $strDestination, $blnFromTemp = false) {
			if ($blnResult = $this->isFile($strFilePath, $blnFromTemp)) {
				if (!($blnResult = @rename($this->cleanPath($strFilePath, $blnFromTemp), $this->cleanPath($strDestination)))) {
					trigger_error(AppLanguage::translate('There was an error moving the file %s to %s', $strFilePath, $strDestination));
				}
			} else {
				trigger_error(AppLanguage::translate('The file to move %s is not a valid file', $strFilePath));
			}
			return $blnResult;
		}
		
		
		/**
		 * Deletes a file.
		 *
		 * @access public
		 * @param string $strFilePath The file to delete
		 * @param boolean $blnSuppress Whether to suppress errors
		 * @return boolean True on success
		 */
		public function deleteFile($strFilePath, $blnSuppress = false) {
			if ($blnResult = $this->isFile($strFilePath)) {
				if (!($blnResult = @unlink($this->cleanPath($strFilePath)))) {
					if (!$blnSuppress) {
						trigger_error(AppLanguage::translate('There was an error deleting the file %s', $strFilePath));
					}
				}
			}
			return $blnResult;
		}
		
		
		/**
		 * Sets the read, write and execute permissions
		 * for the file.
		 *
		 * @access public
		 * @param string $strFilePath The file to chmod
		 * @param integer $intMode The file permissions
		 * @return boolean True on success
		 */
		public function setFilePerms($strFilePath, $intMode = null) {
			if (!($blnResult = chmod($this->cleanPath($strFilePath), $intMode ? $intMode : self::FILE_PERMS))) {
				trigger_error(AppLanguage::translate('There was an error changing the permissions of the file %s', $strFilePath));
			}
			return $blnResult;
		}
				
		
		/**
		 * Gets the size of a file in bytes.
		 *
		 * @access public
		 * @param string $strFilePath The file to get the size of
		 * @return integer The file size in bytes
		 */
		public function getFileSize($strFilePath) {
			if (($intFileSize = filesize($this->cleanPath($strFilePath))) === false) {
				trigger_error(AppLanguage::translate('There was an error getting the size of the file %s', $strFilePath));
			}
			return $intFileSize;
		}
				
		
		/*****************************************/
		/**     SHARED METHODS                  **/
		/*****************************************/
		
		
		/**
		 * Returns true if the file or directory exists.
		 *
		 * @access public
		 * @param string $strPath The file or directory path
		 * @return boolean True if it exists
		 */
		public function pathExists($strPath) {
			return file_exists($this->cleanPath($strPath));
		}
		
		
		/**
		 * Returns the real path of the file or directory
		 * passed and makes sure that it exists within the
		 * files directory. 
		 *
		 * @access protected
		 * @param string $strPath The file or directory path
		 * @param boolean $blnTemp Whether the path is in the temp directory
		 * @return string The cleaned path
		 */
		protected function cleanPath($strPath, $blnTemp = false) {
			if (!$this->blnAbsolute) {
				$strFilesDir = $this->blnTemp || $blnTemp ? $this->getTempDirectory() : $this->strFilesDir;
				if (substr($strPath, 0, strlen($strFilesDir)) == $strFilesDir) {
					$strPath = substr($strPath, strlen($strFilesDir));
				}
				$strPath = $this->realPath($strFilesDir . $strPath);		
				if (!$this->blnLenient && substr($strPath, 0, strlen($strFilesDir)) != $strFilesDir) {
					throw new CoreException(AppLanguage::translate('Invalid file path'));
				}
			}
			return $strPath;
		}
		
		
		/**
		 * Returns the real path of a file even if the
		 * file doesn't exist. This parses out all ../
		 * instances.
		 *
		 * @access public
		 * @param string $strPath The path to translate
		 * @return string The real path
		 */
		public function realPath($strPath) {
			if (!($strRealPath = realpath($strPath))) {
				$strPath = str_replace(array('/', '\\'), DIRECTORY_SEPARATOR, $strPath);
				$arrParts = array_filter(explode(DIRECTORY_SEPARATOR, $strPath), 'strlen');
				
				$arrPath = array();
				foreach ($arrParts as $strPart) {
					switch ($strPart) {
					 	case '.':
					 		break;
					 		
					 	case '..':
					 		array_pop($arrPath);
					 		break;
					 		
					 	default:
					 		$arrPath[] = $strPart;
					 		break;
					}
				}
				$strRealPath = DIRECTORY_SEPARATOR . implode(DIRECTORY_SEPARATOR, $arrPath);
			}
			return $strRealPath;
		}

		
		
		/*****************************************/
		/**     GET & SET METHODS               **/
		/*****************************************/
		
		
		/**
		 * Sets the leniency flag. If turned on the strict path
		 * is bypassed and symlinks and paths outside of the files
		 * base will work.
		 *
		 * @access public
		 * @param boolean $blnLenient Whether to be lenient with the paths
		 */
		public function setLenient($blnLenient) {
			$this->blnLenient = $blnLenient;
		}
		
		
		/**
		 * Sets the absolute flag. If turned on the path won't be
		 * cleaned and the paths will be taken as is. This has the
		 * potential to do damage if used incorrectly.
		 *
		 * @access public
		 * @param boolean $blnAbsolute Whether the paths used are absolute
		 */
		public function setAbsolute($blnAbsolute) {
			$this->blnAbsolute = $blnAbsolute;
		}
		
		
		/**
		 * Gets the base file directory being used.
		 *
		 * @access public
		 * @return string The base files directory
		 */
		public function getFilesDirectory() {
			return $this->strFilesDir;
		}
		
		
		/**
		 * Sets the base file directory to use if not using the
		 * value from the config.
		 *
		 * @access public
		 * @param string $strFilesDir The base files directory
		 */
		public function setFilesDirectory($strFilesDir) {
			$this->strFilesDir = $strFilesDir;
		}
		
		
		/**
		 * Returns the real path to the temp directory.
		 *
		 * @access public
		 * @return string The temp directory
		 */
		public function getTempDirectory() {
			if (!$this->strTempDir) {
				if (function_exists('sys_get_temp_dir')) {
					$this->strTempDir = realpath(sys_get_temp_dir());
				} else {
					if (!empty($_ENV['TMP'])) { 
						$this->strTempDir = realpath($_ENV['TMP']); 
					} else if (!empty($_ENV['TMPDIR'])) { 
						$this->strTempDir = realpath($_ENV['TMPDIR']); 
					} else if (!empty($_ENV['TEMP'])) { 
						$this->strTempDir = realpath($_ENV['TEMP']); 
					} else {
						$strTempFile = tempnam(uniqid(rand(), true), '');
						if (file_exists($strTempFile)) {
							$this->strTempDir = dirname(realpath($strTempFile));
							@unlink($strTempFile);
						}
					}
				}
			}
			
			if (!$this->strTempDir) {
				throw new CoreException(AppLanguage::translate('Invalid temporary directory'));
			}
			
			if (!is_writable($this->strTempDir)) {
				throw new CoreException(AppLanguage::translate('Unable to write to temporary directory'));
			}
			
			return $this->strTempDir;
		}
		
		
		/**
		 * Returns the hashed directory for the file passed. If
		 * the hash directory doesn't exist and $blnSkipCheck
		 * is false then this will attempt to create the hash
		 * directory.
		 *
		 * @access public
		 * @param string $strBasePath The directory path to add the hash to
		 * @param string $strToHash The value to create the hash from (ie. the filename)
		 * @param integer $intHashLevel The level of directories deep the hash should be (absolute max 32)
		 * @param boolean $blnSkipCheck If set it won't bother checking the validity of the directory
		 * @return string The hashed path or false on error
		 */
		public function getHashDirectory($strBasePath, $strToHash, $intHashLevel, $blnSkipCheck = false) {
			$strHashPath = $strBasePath;
			
			if ($intHashLevel > 0) {
				$strHash = substr(md5($strToHash), 0, $intHashLevel);
				for ($i = 0, $ix = strlen($strHash); $i < $ix; $i++) {
					$strHashPath .= substr($strHash, $i, 1) . DIRECTORY_SEPARATOR;
				}
				
				if (!$blnSkipCheck) {
					if (!$this->isDirectory($strHashPath)) {
						if (!$this->createDirectory($strHashPath, null, true)) {
							trigger_error(AppLanguage::translate('Invalid hash path %s', $strHashPath));
							return false;
						}
					}
				}
			}
			
			return $strHashPath;
		}
		
		
		/**
		 * Returns the URL for public files.
		 *
		 * @access public
		 * @return string The public URL to the file
		 */
		public function getPublicUrl() {
			return $this->strPublicUrl;
		}
		
		
		/*****************************************/
		/**     TEMP FILE METHODS               **/
		/*****************************************/
		
		
		/**
		 * Uses the temp directory for all file and directory 
		 * methods.
		 *
		 * @access public
		 * @return string The path of the temp directory
		 */
		public function useTemp() {
			$this->blnTemp = true;
			return $this->getTempDirectory();
		}
		
		
		/**
		 * Uses the regular directory for all file and directory
		 * methods.
		 *
		 * @access public
		 */
		public function clearTemp() {
			$this->blnTemp = false;
		}
	}