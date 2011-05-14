<?php
	require_once('php/cache/memcache/MemcacheTiered.class.php');
		
	/**
	 * MemcachedTiered.class.php
	 * 
	 * A class for implementing memcached with multiple
	 * cache tiers. This uses the newer memcached package
	 * rather than the older memcache package.
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
	class MemcachedTiered extends MemcacheTiered implements Cache {
		
		/**
		 * Determines if the memcached module is installed.
		 *
		 * @access public
		 * @return boolean True if the cache is available
		 * @static
		 */
		static public function isAvailable() {
			return class_exists('Memcached', false);
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
				$this->objActive->objCache = new Memcached();
				
				$arrServers = array();
				foreach ($this->objActive->arrConfig['Servers'] as $arrServer) {
					$arrServers[] = array($arrServer['Host'], $arrServer['Port'], $arrServer['Weight']);
				}
				
				if (!empty($arrServers)) {
					if ($this->objActive->objCache->addServers($arrServers)) {
						$this->objActive->blnConnected = true;
					}
				}
			}
			
			return $this->objActive->blnConnected;
		}
		
		
		/**
		 * The memcached package doesn't currently have a
		 * close method so this does nothing.
		 *
		 * @access public
		 */
		public function close() {
			return;
		}
		
		
		/**
		 * Returns the server stats for the connection pool.
		 *
		 * @access public
		 * @return array The stats array
		 */
		public function getStats() {
			if ($this->checkTier()) {
				return $this->objActive->objCache->getStats();
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
							
					if ($this->objActive->objCache->$strMethod($strKey, $mxdValue, $intExpire) === false) {
						trigger_error(AppLanguage::translate('There was an error saving the cache (%s, %s mode)', $strKey, $strMethod));
						return false;
					} else {
						return true;
					}
				}
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
					$mxdResult = $this->objActive->objCache->getMulti($arrCleanedKeys);
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
			return 'Cache: Memcached';
		}
	}