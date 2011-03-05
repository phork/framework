<?php
	/**
	 * FileSystemHandler.interface.php
	 *
	 * The interface for all filesystem objects 
	 * to implement.
	 * 
	 * Copyright 2006-2010, Phork Labs. (http://phorklabs.com)
	 *
	 * Licensed under The MIT License
	 * Redistributions of files must retain the above copyright notice.
	 *
	 * @author Elenor Collings <elenor@phork.org>
	 * @license http://www.opensource.org/licenses/mit-license.php The MIT License
	 * @package phork
	 * @subpackage core
	 */
	interface FileSystemHandler {
	
		public function __construct();
		
		public function isDirectory($strDirPath, $blnTemp = false);
		public function createDirectory($strDirPath, $intMode = null, $blnRecursive = false);
		public function moveDirectory($strDirPath, $strDestination, $blnFromTemp = false);
		public function deleteDirectory($strDirPath, $blnSuppress = false);
		public function setDirectoryPerms($strDirPath, $intMode = null);
		
		public function listFiles($strDirPath);
		
		public function isFile($strFilePath, $blnTemp = false);
		public function readFile($strFilePath, $blnSuppress = false);
		public function createFile($strFilePath, $strContents, $intMode = null);
		public function createTempFile($strContents);
		public function appendFile($strFilePath, $strContents);
		public function copyFile($strFilePath, $strDestination, $blnFromTemp = false);
		public function moveFile($strFilePath, $strDestination, $blnFromTemp = false);
		public function deleteFile($strFilePath, $blnSuppress = false);
		public function setFilePerms($strFilePath, $intMode = null);
		
		public function pathExists($strPath);
		public function setLenient($blnLenient);
		public function getFilesDirectory();
		public function setFilesDirectory($strFilesDir);
		public function getTempDirectory();
		public function getHashDirectory($strBasePath, $strToHash, $intHashLevel, $blnSkipCheck = false);
		
		public function useTemp();
		public function clearTemp();
	}