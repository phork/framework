<?php
	require_once('php/cache/CacheAdaptor.class.php');
	
	/**
	 * MemcacheTiered.class.php
	 * 
	 * A class for implementing memcache with multiple
	 * cache tiers. This also has has simulated namespace
	 * support which memcache doesn't have natively.
	 *
	 * Constants:
	 * - CACHE_ADD_ONLY
	 * - CACHE_REPLACE_ONLY
	 *
	 * Copyright 2006-2011, Phork Labs. (http://phorklabs.com)
	 *
	 * Licensed under The MIT License
	 * Redistributions of files must retain the above copyright notice.
	 *
	 * @author Elenor Collings <elenor@phork.org>
	 * @license http://www.opensource.org/licenses/mit-license.php The MIT License
	 * @package phork
	 * @subpackage cache
	 */
	class MemcacheTiered extends CacheAdaptor {
		
		protected $intMaxKeyLength = 250;
		
		
		/**
		 * Determines if the memcache module is installed.
		 *
		 * @access public
		 * @return boolean True if the cache is available
		 * @static
		 */
		static public function isAvailable() {
			return class_exists('Memcache', false);
		}
		
		
		/*****************************************/
		/**     CONNECTION METHODS              **/
		/*****************************************/
		
		
		/**
		 * Connects to the current caching tier.
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
				$this->objActive->objCache = new Memcache();
				foreach ($this->objActive->arrConfig['Servers'] as $arrServer) {
					if ($this->objActive->objCache->addServer($arrServer['Host'], $arrServer['Port'], $arrServer['Persistent'], $arrServer['Weight'], $arrServer['Timeout'])) {
						$this->objActive->blnConnected = true;
					}
				}
			}
			
			return $this->objActive->blnConnected;
		}
		
		
		/**
		 * Closes the connection to the current caching
		 * tier. Persistent connections aren't closed.
		 *
		 * @access public
		 */
		public function close() {
			if ($this->objActive) {
				if ($this->objActive->blnConnected) {
					$this->objActive->objCache->close();
				}
				$this->objActive->objCache = null;
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
				return $this->objActive->objCache->getExtendedStats();
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
			if ($strKey = $this->cleanKey($strKey)) {
				CoreDebug::debug($this, "Save {$strKey}");
				
				if ($this->checkTier()) {
					switch($intSaveType) {
						case self::CACHE_ADD_ONLY: 
							$strMethod = 'add';
							break;
							
						case self::CACHE_REPLACE_ONLY:
							$strMethod = 'replace';
							break;
							
						default:
							$strMethod = 'set';
							break;
					}
					
					if ($this->objActive->objCache->$strMethod($strKey, $mxdValue, !empty($this->objActive->arrConfig['Compression']) ? MEMCACHE_COMPRESSED : null, $intExpire) === false) {
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
		 *
		 * @access public
		 * @param string $strKey The key to retrieve
		 * @return mixed The retrieved data or null on failure
		 */
		public function load($strKey) {
			if ($strKey = $this->cleanKey($strKey)) {
				CoreDebug::debug($this, "Load {$strKey}");
				
				if ($this->checkTier()) {
					$mxdResult = $this->objActive->objCache->get($strKey);
					if ($mxdResult === false) {
						$mxdResult = null;
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
				
				if ($this->checkTier()) {
					$mxdResult = $this->objActive->objCache->get($arrCleanedKeys);
					if ($mxdResult === false) {
						$mxdResult = null;
					}
					
					if (is_array($mxdResult)) {
						$arrResult = array();
						foreach ($arrKeys as $intKey=>$strKey) {
							$strCleanedKey = $arrCleanedKeys[$intKey];
							if (!empty($mxdResult[$strCleanedKey])) {
								$arrResult[$strKey] = $mxdResult[$strCleanedKey];
							}
						}
						$mxdResult = $arrResult;
					}
				} else {
					$mxdResult = null;
				}
				
				CoreDebug::debug($this, ($mxdResult ? 'Hit' : 'Miss'));
				
				return $mxdResult;
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
			if ($strKey = $this->cleanKey($strKey)) { 
				CoreDebug::debug($this, "Delete {$strKey}");
				
				if ($this->checkTier()) {
					return $this->objActive->objCache->delete($strKey, $intTimeout);
				}
			}
		}
		
		
		/**
		 * Increments the cached record. Makes sure that
		 * the compression is turned off or else it won't
		 * work.
		 * 
		 * @access public
		 * @param string $strKey The key associated with the item
		 * @param integer $intValue The amount to increment by; if the item isn't numeric it will be set to this
		 * @param boolean $blnCreate If this is true and the item doesn't exist it will be created
		 * @return integer The new incremented value or false on failure
		 */
		public function increment($strKey, $intValue = 1, $blnCreate = true) {
			if ($strKey = $this->cleanKey($strKey)) {
				CoreDebug::debug($this, "Increment {$strKey}");
				
				if ($this->checkTier()) {
					if (!($intResult = $this->objActive->objCache->increment($strKey, $intValue))) {
						if ($blnCreate) {
							$intCompressionBackup = array_key_exists('Compression', $this->objActive->arrConfig) ? $this->objActive->arrConfig['Compression'] : null;
							$this->objActive->arrConfig['Compression'] = null;
							if ($this->save($strKey, $intValue)) {
								$intResult = $intValue;
							}
							$this->objActive->arrConfig['Compression'] = $intCompressionBackup;
						}
					}
					
					return $intResult;
				}
			}
			return false;
		}
		
		
		/**
		 * Decrements the cached record. Makes sure that
		 * the compression is turned off or else it won't
		 * work.
		 * 
		 * @access public
		 * @param string $strKey The key associated with the item
		 * @param integer $intValue The amount to decrement by; if the item isn't numeric it will be set to this
		 * @return integer The new decremented value or false on failure
		 */
		public function decrement($strKey, $intValue = 1) {
			if ($strKey = $this->cleanKey($strKey)) {
				CoreDebug::debug($this, "Decrement {$strKey}");
				
				if ($this->checkTier()) {
					return $this->objActive->objCache->decrement($strKey, $intValue);
				}
			}
			return false;
		}
		
		
		/**
		 * Flushes the tier by setting everything to expired.
		 * 
		 * @access public
		 * @return boolean True on success
		 */
		public function flush() {
			CoreDebug::debug($this, 'Flush tier ' . $this->strActive);
			
			if ($this->checkTier()) {
				return $this->objActive->objCache->flush();
			}
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
			return 'Cache: Memcache';
		}
	}