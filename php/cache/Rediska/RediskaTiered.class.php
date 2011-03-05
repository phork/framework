<?php
	require_once('php/cache/CacheAdaptor.class.php');
	
	/**
	 * RediskaTiered.class.php
	 * 
	 * A class for implementing Redis via Rediska with
	 * multiple cache tiers. This also has has simulated
	 * namespace support.
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
	class RediskaTiered extends CacheAdaptor {
		
		/**
		 * Determines if the Rediska class is available.
		 *
		 * @access public
		 * @return boolean True if the cache is available
		 * @static
		 */
		static public function isAvailable() {
			try {
				set_include_path(get_include_path() . PATH_SEPARATOR . AppConfig::get('RediskaBase', true, CacheFactory::CONFIG_NS));
				AppLoader::includeExtension('rediska/', 'Rediska', true);
				AppLoader::includeExtension('rediska/Rediska/', 'Key', true);
				
				return true;
			} catch (Exception $objException) {
				return false;
			}
		}
		
		
		/**
		 * Cleans the special characters out of a key or array
		 * of keys. Also turns each key into a key object if it
		 * isn't one already.
		 *
		 * @access public
		 * @param mixed $mxdKey The key or array of keys to clean
		 * @return mixed The cleaned key or array of keys in object form
		 */
		public function cleanKey($mxdKey) {
			if (is_array($mxdKey)) {
				return array_map(array($this, 'cleanKey'), $mxdKey);
			} else if (is_object($mxdKey)) {
				if ($mxdKey instanceof Rediska_Key_Abstract) {
					return $mxdKey;
				}
			} else {
				return $this->getSimpleKey($mxdKey);
			}
		}
		
		
		/*****************************************/
		/**     KEY METHODS                     **/
		/*****************************************/
		
		
		/**
		 * Returns a simple key object.
		 *
		 * @access public
		 * @param string $strKey The key name
		 * @return object A simple key object
		 */
		public function getSimpleKey($strKey) {
			AppLoader::includeExtension('rediska/Rediska/', 'Key', true);
			$objKey = new Rediska_Key($strKey);
			$objKey->setRediska($this->objActive->objCache);
			return $objKey;
		}
		
		
		/**
		 * Returns a list key object.
		 *
		 * @access public
		 * @param string $strKey The key name
		 * @return object A list key object
		 */
		public function getListKey($strKey) {
			AppLoader::includeExtension('rediska/Rediska/Key/', 'List', true);
			$objKey = new Rediska_Key_List($strKey);
			$objKey->setRediska($this->objActive->objCache);
			return $objKey;
		}
		
		
		/**
		 * Returns a set key object.
		 *
		 * @access public
		 * @param string $strKey The key name
		 * @return A set key object
		 */
		public function getSetKey($strKey) {
			AppLoader::includeExtension('rediska/Rediska/Key/', 'Set', true);
			$objKey = new Rediska_Key_Set($strKey);
			$objKey->setRediska($this->objActive->objCache);
			return $objKey;
		}
		
		
		/**
		 * Returns a sorted key object.
		 *
		 * @access public
		 * @param string $strKey The key name
		 * @return A sorted key object
		 */
		public function getSortedSetKey($strKey) {
			AppLoader::includeExtension('rediska/Rediska/Key/', 'SortedSet', true);
			$objKey = new Rediska_Key_SortedSet($strKey);
			$objKey->setRediska($this->objActive->objCache);
			return $objKey;
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
				$this->objActive->objCache = new Rediska();
				foreach ($this->objActive->arrConfig['Servers'] as $arrServer) {
					if ($this->objActive->objCache->addServer($arrServer['host'], $arrServer['port'], $arrServer)) {
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
					foreach ($this->objActive->objCache->getConnections() as $objConnection) {
						$objConnection->disconnect();
					}
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
				return $this->objActive->objCache->getConnections();
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
		 * @param mixed $mxdKey The key or key object associated with the item
		 * @param mixed $mxdValue The value of the cache data
		 * @param integer $intExpire The expiration time in seconds, or 0 to never expire
		 * @param integer $intSaveType The optional save type (CACHE_ADD_ONLY, CACHE_REPLACE_ONLY)
		 * @return boolean True on success
		 */
		public function save($mxdKey, $mxdValue, $intExpire = 0, $intSaveType = null) {
			if ($objKey = $this->cleanKey($mxdKey)) {
				CoreDebug::debug($this, 'Save ' . $objKey->getName());
					
				if ($this->checkTier()) {
					switch($intSaveType) {
						case self::CACHE_ADD_ONLY: 
							$strMethod = 'add';
							if ($objKey->isExists()) {
								trigger_error(AppLanguage::translate('There was an error saving the cache (%s, %s mode)', $objKey->getName(), $strMethod));
								return false;
							}
							break;
							
						case self::CACHE_REPLACE_ONLY:
							$strMethod = 'replace';
							if (!$objKey->isExists()) {
								trigger_error(AppLanguage::translate('There was an error saving the cache (%s, %s mode)', $objKey->getName(), $strMethod));
								return false;
							}
							break;
							
						default:
							$strMethod = 'set';
							$blnOverwrite = true;
							break;
					}
					
					if ($intExpire) {
						$objKey->setExpire($intExpire);
					}
							
					if ($objKey->setValue($mxdValue) === false) {
						trigger_error(AppLanguage::translate('There was an error saving the cache (%s, %s mode)', $objKey->getName(), $strMethod));
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
		 * @param mixed $mxdKey The key or key object associated with the item
		 * @return mixed The retrieved data or null on failure
		 */
		public function load($mxdKey) {
			if ($objKey = $this->cleanKey($mxdKey)) {
				CoreDebug::debug($this, 'Load ' . $objKey->getName());
				
				if ($this->checkTier()) {
					$mxdResult = $objKey->getValue();
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
			$arrResult = array();
			foreach ($arrKeys as $mxdKey) {
				$arrResult[$mxdKey] = $this->load($mxdKey);
			}
			return $arrResult;
		}
		
		
		/**
		 * Deletes the cache data by the key passed.
		 * 
		 * @access public
		 * @param mixed $mxdKey The key or key object associated with the item
		 * @param integer $intTimeout Number of seconds after which the item will expire
		 * @return boolean True on success
		 */
		public function delete($mxdKey, $intTimeout = null) {
			if ($objKey = $this->cleanKey($mxdKey)) {
				CoreDebug::debug($this, 'Delete ' . $objKey->getName());
				
				if ($this->checkTier()) {
					if ($intTimeout) {
						$blnResult = $objKey->expire($intTimeout);
					} else {
						$blnResult = $objKey->delete();
					}
					return $blnResult;
				}
			}
		}
		
		
		/**
		 * Increments the cached record.
		 * 
		 * @access public
		 * @param mixed $mxdKey The key or key object associated with the item
		 * @param integer $intValue The amount to increment by; if the item isn't numeric it will be set to this
		 * @param boolean $blnCreate If this is true and the item doesn't exist it will be created
		 * @return integer The new incremented value or false on failure
		 */
		public function increment($mxdKey, $intValue = 1, $blnCreate = true) {
			if ($objKey = $this->cleanKey($mxdKey)) {
				CoreDebug::debug($this, 'Increment ' . $objKey->getName());
				
				if ($this->checkTier()) {
					if (!($intResult = $objKey->increment($intValue))) {
						if ($blnCreate) {
							if ($this->save($objKey, $intValue)) {
								$intResult = $intValue;
							}
						}
					}
					
					return $intResult;
				}
			}
			return false;
		}
		
		
		/**
		 * Decrements the cached record.
		 * 
		 * @access public
		 * @param mixed $mxdKey The key or key object associated with the item
		 * @param integer $intValue The amount to decrement by; if the item isn't numeric it will be set to this
		 * @return integer The new decremented value or false on failure
		 */
		public function decrement($mxdKey, $intValue = 1) {
			if ($objKey = $this->cleanKey($mxdKey)) {
				CoreDebug::debug($this, 'Decrement ' . $objKey->getName());
				
				if ($this->checkTier()) {
					return $objKey->decrement($intValue);
				}
			}
			return false;
		}
		
		
		/**
		 * Flushes the tier.
		 * 
		 * @access public
		 * @return boolean True on success
		 */
		public function flush() {
			CoreDebug::debug($this, 'Flush tier ' . $this->strActive);
			
			if ($this->checkTier()) {
				return $this->objActive->objCache->flushdb();
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
			return 'Cache: Rediska';
		}
	}