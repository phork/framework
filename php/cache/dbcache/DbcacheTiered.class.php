<?php
	require_once('php/cache/CacheAdaptor.class.php');
	
	/**
	 * DbcacheTiered.class.php
	 * 
	 * A class for implementing a database cache with 
	 * multiple multiple cache tiers. This also has has
	 * simulated namespace support.
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
	class DbcacheTiered extends CacheAdaptor {
		
		protected $intMaxKeyLength = 250;
		const DB_NAME = 'Cache';
		
		
		/**
		 * Determines if the database module is installed.
		 * The database library MUST be instantiated before
		 * the cache library.
		 *
		 * @access public
		 * @return boolean True if the cache is available
		 * @static
		 */
		static public function isAvailable() {
			if (AppConfig::get('DatabaseEnabled', false) && class_exists('DatabaseFactory', false)) {
				AppLoader::includeClass('php/cache/dbcache/', 'CacheModel');
				AppLoader::includeClass('php/cache/dbcache/', 'CacheRecord');
				return true;
			}
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
			
			//make sure that the active tier object has been set
			if (!$this->objActive) {
				throw new CoreException(AppLanguage::translate('Invalid cache tier object'));
			}
			
			//if a connection doesn't already exist, connect now
			if (!$this->objActive->blnConnected) {
				AppLoader::includeClass('php/database/', 'DatabaseManager');
				if (!DatabaseManager::exists($strDatabase = self::DB_NAME . $strTier)) {
					$objDatabaseFactory = new DatabaseFactory($this->objActive->arrConfig['Database']);
					DatabaseManager::appendDatabase($strDatabase, $objDatabaseFactory->init());
				}
				
				AppLoader::includeExtension('helpers/', 'ModelDatabase');
				$this->objActive->objCache = new CacheModel(array('Tier' => $this->objActive->arrConfig['TierKey']));
				$this->objActive->objCache->appendHelper('database', 'ModelDatabase');
				$this->objActive->objCache->initHelper('database', array('preLoad', 'preSave', 'preDelete'), array('Database' => $strDatabase));
				
				$this->objActive->blnConnected = true;
			}
			
			return $this->objActive->blnConnected;
		}
		
		
		/**
		 * Closes the connection to the current caching
		 * tier. There isn't any connection to close so
		 * this just destroys the model.
		 *
		 * @access public
		 */
		public function close() {
			if ($this->objActive) {
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
				return array(
					'read' => array(
						'Database' => $this->objActive->arrConfig['Database']['Connections']['Read']['Database'],
						'TierKey' => $this->objActive->arrConfig['TierKey']
					),
					'write' => array(
						'Database' => $this->objActive->arrConfig['Database']['Connections']['Write']['Database'],
						'TierKey' => $this->objActive->arrConfig['TierKey']
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
		 * Saves the cache data. Uses a bit of an expires
		 * hack to set the expires date to 10 years in the
		 * future.
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
							$strMethod = 'update';
							break;
							
						default:
							$strMethod = 'save';
							break;
					}
					
					$this->objActive->objCache->clear();
					$this->objActive->objCache->import(array(
						'cachekey'	=> $strKey,
						'raw'		=> $mxdValue,
						'expires'	=> date('Y-m-d H:i:s', time() + ($intExpire ? $intExpire : 315569260))
					));
					
					//call the save method and check for errors
					if ($this->objActive->objCache->$strMethod() === false) {
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
				
				$this->objActive->objCache->clear();
				if ($this->checkTier()) {
					$mxdResult = $this->objActive->objCache->loadByCacheKey($strKey);
					if ($mxdResult && $this->objActive->objCache->count()) {
						$mxdResult = $this->objActive->objCache->current()->get('raw');
					} else {
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
			if ($arrCleanedKeys = array_unique($this->cleanKey($arrKeys))) {
				CoreDebug::debug($this, 'Load ' . implode(', ', array_values($arrCleanedKeys)));
				
				$this->objActive->objCache->clear();
				if ($this->checkTier()) {
					$mxdResult = $this->objActive->objCache->loadByCacheKey($arrCleanedKeys);
					if ($mxdResult !== false) {
						if ($arrAssociative = $this->objActive->objCache->getAssociativeList('cachekey', false)) {
							$arrResult = array();
							foreach ($arrKeys as $intKey=>$strKey) {
								$strCleanedKey = $arrCleanedKeys[$intKey];
								if (!empty($arrAssociative[$strCleanedKey])) {
									$arrResult[$strKey] = $arrAssociative[$strCleanedKey]->get('raw');
								}
							}
							$mxdResult = $arrResult;
						}
					} else {
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
		 * Deletes the cache data by the key passed.
		 * The timeout value has no effect here.
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
					return $this->objActive->objCache->delete(array(
						'Conditions' => array(
							array(
								'Column' => 'cachekey',
								'Value' => $strKey
							)
						)
					));
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
					if (!($intResult = $this->objActive->objCache->increment($strKey, $intValue, true))) {
						if ($blnCreate) {
							if ($this->save($strKey, $intValue)) {
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
					return $this->objActive->objCache->decrement($strKey, $intValue, true);
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
			return 'Cache: Dbcache';
		}
	}