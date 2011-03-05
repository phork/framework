<?php
	require_once('php/cache/CacheAdaptor.class.php');
	
	/**
	 * FilecacheTiered.class.php
	 * 
	 * A class for implementing a file cache with multiple
	 * cache tiers. This also has has simulated namespace
	 * support.
	 *
	 * Constants:
	 * - CACHE_ADD_ONLY
	 * - CACHE_REPLACE_ONLY
	 *
	 * Copyright 2006-2010, Phork Labs. (http://phorklabs.com)
	 *
	 * Licensed under The MIT License
	 * Redistributions of files must retain the above copyright notice.
	 *
	 * @author Elenor Collings <elenor@phork.org>
	 * @license http://www.opensource.org/licenses/mit-license.php The MIT License
	 * @package phork
	 * @subpackage cache
	 */
	class FilecacheTiered extends CacheAdaptor {
		
		protected $intMaxKeyLength = 250;
		
		
		/**
		 * Determines if the the cache is available.
		 *
		 * @access public
		 * @return boolean True if the cache is available
		 * @static
		 */
		static public function isAvailable() {
			return true;
		}
		
		
		/**
		 * Adds the full directory path to the key, including 
		 * any hash paths.
		 *
		 * @access public
		 * @param string $strKey The key to get the path for
		 * @return string The key with the path
		 */
		public function getKeyPath($strKey) {
			if ($this->objActive) {
				$arrDirectory = $this->objActive->arrConfig;
				if ($strDirectoryPath = $this->objActive->objCache->getHashDirectory($arrDirectory['RootPath'], $strKey, $arrDirectory['HashLevel'])) {
					return $strDirectoryPath . $strKey;
				}	
			}
		}
		
		
		/*****************************************/
		/**     CONNECTION METHODS              **/
		/*****************************************/
		
		
		/**
		 * Connects to the current caching tier. There isn't
		 * any connecting necessary for a filesystem cache
		 * but the filesystem object is instantiated and the
		 * connected flag is set.
		 * 
		 * @access public
		 * @param string $strTier The tier to connect to
		 * @return boolean True on success
		 */
		public function connect($strTier) {
			if (!$this->objActive) {
				throw new CoreException(AppLanguage::translate('Invalid cache tier object'));
			}
			
			if (!$this->objActive->blnConnected) {
				if (AppLoader::includeExtension('files/', $strFileSystem = $this->objActive->arrConfig['FileSystem'] . 'FileSystemHandler')) {
					$this->objActive->objCache = new $strFileSystem();
					$this->objActive->blnConnected = true;
				}
			}
			
			return $this->objActive->blnConnected;
		}
		
		
		/**
		 * Closes the connection to the current caching
		 * tier. There isn't any connection to close so
		 * this just clears the connection flag.
		 *
		 * @access public
		 */
		public function close() {
			if ($this->objActive) {
				$this->objActive->blnConnected = false;
			}
		}
		
		
		/**
		 * Returns the server stats for the connection pool.
		 *
		 * @access public
		 * @return array The stats array
		 */
		public function getStats() {
			if ($this->checkTier()) {
				return array(
					'filesystem' => array(
						'FileSystem' => $this->objActive->arrConfig['FileSystem'],
						'FileDir' => $this->objActive->objCache->getFilesDirectory()
					)
				);
			} else {
				trigger_error(AppLanguage::translate('There was an error loading the stats - No servers available'));
				return false;
			}
		}
		
		
		/*****************************************/
		/**     CACHE METHODS                   **/
		/*****************************************/
		
		
		/**
		 * Saves the cache data.
		 * 
		 * @access public
		 * @param string $strKey The key associated with the item
		 * @param mixed $mxdValue The value of the cache data
		 * @param integer $intExpire The expiration time in seconds, or 0 to never expire
		 * @param integer $intSaveType The optional save type (CACHE_ADD_ONLY, CACHE_REPLACE_ONLY)
		 * @return boolean True on success
		 */
		public function save($strKey, $mxdValue, $intExpire = 0, $intSaveType = null) {
			if (($strKey = $this->cleanKey($strKey)) && ($strKeyPath = $this->getKeyPath($strKey))) {
				CoreDebug::debug($this, "Save {$strKey}");
				
				if ($this->checkTier()) {
					switch($intSaveType) {
						case self::CACHE_ADD_ONLY: 
							$strMethod = 'add';
							if ($this->objActive->objCache->isFile($strKeyPath)) {
								trigger_error(AppLanguage::translate('There was an error saving the cache (%s, %s mode)', $strKey, $strMethod));
								return false;
							}
							break;
							
						case self::CACHE_REPLACE_ONLY:
							$strMethod = 'replace';
							if (!$this->objActive->objCache->isFile($strKeyPath)) {
								trigger_error(AppLanguage::translate('There was an error saving the cache (%s, %s mode)', $strKey, $strMethod));
								return false;
							}
							break;
							
						default:
							$strMethod = 'set';
							break;
					}
					
					//call the save method and check for errors
					if ($this->objActive->objCache->createFile($strKeyPath, $this->pack($mxdValue, $intExpire)) === false) {
						trigger_error(AppLanguage::translate('There was an error saving the cache (%s, %s mode)', $strKey, $strMethod));
						return false;
					} else {
						return true;
					}
				}
			}
		}
		
		
		/**
		 * Retrieves the cache data for the key passed.
		 * If the cache has expired (the unpack method will
		 * return null) then this deletes the cache file.
		 *
		 * @access public
		 * @param mixed $strKey The key to retrieve
		 * @return mixed The retrieved data or null on failure
		 */
		public function load($strKey) {
			if (($strKey = $this->cleanKey($strKey)) && ($strKeyPath = $this->getKeyPath($strKey))) {
				CoreDebug::debug($this, "Load {$strKey}");
				
				if ($this->checkTier()) {
					$mxdResult = $this->objActive->objCache->readFile($strKeyPath, true);
					if ($mxdResult) {
						if (($mxdResult = $this->unpack($mxdResult, $intExpire)) === null) {
							$this->objActive->objCache->deleteFile($strKeyPath, true);
						}
					}
				} else {
					$mxdResult = null;
				}
				
				CoreDebug::debug($this, ($mxdResult ? 'Hit' : 'Miss'));
				
				return $mxdResult;
			}
		}
		
		
		/**
		 * Retrieves the cache data for the keys passed.
		 *
		 * @access public
		 * @param array $arrKeys The keys to retrieve
		 * @return mixed The retrieved data or null on failure
		 */
		public function loadMulti($arrKeys) {
			if ($arrCleanedKeys = $this->cleanKey($arrKeys)) {
				CoreDebug::debug($this, 'Load ' . implode(', ', array_values($arrCleanedKeys)));
				
				$arrResult = array();
				foreach ($arrKeys as $intKey=>$strKey) {
					$arrResult[$strKey] = $this->load($arrCleanedKeys[$intKey]);
				}
				
				return $arrResult;
			}
		}
		
		
		/**
		 * Deletes the cache data by the key passed.
		 * 
		 * @access public
		 * @param string $strKey The key associated with the item
		 * @param integer $intTimeout Number of seconds after which the item will expire
		 * @return boolean True on success
		 */
		public function delete($strKey, $intTimeout = null) {
			if (($strKey = $this->cleanKey($strKey)) && ($strKeyPath = $this->getKeyPath($strKey))) {
				CoreDebug::debug($this, "Delete {$strKey}");
				
				if ($this->checkTier()) {
					if ($intTimeout) {
						$mxdResult = $this->objActive->objCache->readFile($strKeyPath, true);
						if ($mxdResult) {
							if (($mxdResult = $this->unpack($mxdResult, $intExpire)) !== null) {
								return $this->save($strKey, $mxdResult, $intTimeout);
							}
						}
					} else {
						return $this->objActive->objCache->deleteFile($strKeyPath);
					}
				}
			}
		}
		
		
		/**
		 * Increments the cached record.
		 * 
		 * @access public
		 * @param string $strKey The key associated with the item
		 * @param integer $intValue The amount to increment by; if the item isn't numeric it will be set to this
		 * @param boolean $blnCreate If this is true and the item doesn't exist it will be created
		 * @return integer The new incremented value or false on failure
		 */
		public function increment($strKey, $intValue = 1, $blnCreate = true) {
			if (($strKey = $this->cleanKey($strKey)) && ($strKeyPath = $this->getKeyPath($strKey))) {
				CoreDebug::debug($this, "Increment {$strKey}");
				
				if ($this->checkTier()) {
					$mxdResult = $this->objActive->objCache->readFile($strKeyPath, true);
					if ($mxdResult) {
						if (($mxdResult = $this->unpack($mxdResult, $intExpire)) !== null) {
							$intResult = ((int) $mxdResult) + $intValue;
						}
					}
					
					if (!isset($intResult) && $blnCreate) {
						$intResult = $intValue;
					}
					
					if (isset($intResult)) {
						$this->save($strKey, $intResult, (isset($intExpire) ? $intExpire - time() : 0));
					}
										
					return isset($intResult) ? $intResult : false;
				}
			}
		}
		
		
		/**
		 * Decrements the cached record.
		 * 
		 * @access public
		 * @param string $strKey The key associated with the item
		 * @param integer $intValue The amount to decrement by; if the item isn't numeric it will be set to this
		 * @return integer The new decremented value or false on failure
		 */
		public function decrement($strKey, $intValue = 1) {
			if (($strKey = $this->cleanKey($strKey)) && ($strKeyPath = $this->getKeyPath($strKey))) {
				CoreDebug::debug($this, "Decrement {$strKey}");
				
				if ($this->checkTier()) {
					$mxdResult = $this->objActive->objCache->readFile($strKeyPath, true);
					if ($mxdResult) {
						if (($mxdResult = $this->unpack($mxdResult, $intExpire)) !== null) {
							$intResult = max(0, ((int) $mxdResult) - $intValue);
						}
					}
					
					if (isset($intResult)) {
						$this->save($strKey, $intResult, (isset($intExpire) ? $intExpire - time() : 0));
					}
										
					return isset($intResult) ? $intResult : false;
				}
			}
		}
		
		
		/**
		 * Flushes the tier by deleting all the files. This
		 * is considered too risky and has been left out.
		 * Any filesystem cache flushing should be handled
		 * manually.
		 * 
		 * @access public
		 * @return boolean True on success
		 */
		public function flush() {
			CoreDebug::debug($this, 'Flush tier ' . $this->strActive);
			return false;
		}
		
		
		/*****************************************/
		/**     PACKING METHODS                 **/
		/*****************************************/
		
		
		/**
		 * Packs the data into cacheable form.
		 *
		 * @access protected
		 * @param mixed $mxdData The data to pack
		 * @param integer $intExpire The expiration time in seconds
		 * @return string The packed data
		 */
		protected function pack($mxdData, $intExpire) {
			return serialize(array(
				'Expire' 	=> $intExpire ? time() + $intExpire : 0,
				'Data' 		=> $mxdData
			));
		}
		
		
		/**
		 * Unserializes the data and if the data hasn't
		 * expired then it returns it.
		 *
		 * @access protected
		 * @param string $strData The data to unpack
		 * @param integer $intExpire The expiration timestamp
		 * @return mixed The unpacked data
		 */
		protected function unpack($strData, &$intExpire) {
			$arrData = unserialize($strData);
			if (array_key_exists('Expire', $arrData) && array_key_exists('Data', $arrData)) {
				if ($arrData['Expire'] === 0 || $arrData['Expire'] > time()) {
					$intExpire = $arrData['Expire'];
					return $arrData['Data'];
				}
			}
			return null;
		}
		
		
		/*****************************************/
		/**     MAGIC METHODS                   **/
		/*****************************************/
		
		
		/**
		 * Returns the Cache object's pretty name.
		 *
		 * @access public
	 	 * @return string The object's name
		 */
		public function __toString() {
			return 'Cache: Filecache';
		}
	}