<?php
	require_once('php/core/CoreObject.class.php');
	require_once('php/cache/CacheTier.class.php');
	require_once('interfaces/Cache.interface.php');
	
	/**
	 * CacheAdaptor.class.php
	 * 
	 * An adaptor for the caching classes to extend. This
	 * implements caching tiers. The default tiers are 
	 * base and presentation. The base tier is used for
	 * caching data and the presentation tier is used for
	 * caching pages. This allows the presentation tier to
	 * be flushed without affecting the data tier in the
	 * event that the page design changes.
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
	class CacheAdaptor extends CoreObject implements Cache {
		
		protected $arrTiers;
		
		protected $strActive;
		protected $objActive;
		
		protected $intMaxKeyLength = 250;
		
		
		/**
		 * The cache object's constructor initializes
		 * the cache tier, if applicable.
 		 *
		 * @access public
		 * @param object $objBase The base tier object
		 * @param object $objPresentation The presentation tier object
		 */
		public function __construct(CacheTier $objBase, CacheTier $objPresentation) {
			$this->addTier('Base', $objBase);
			$this->addTier('Presentation', $objPresentation);
		}
		
		
		/**
 		 * Closes any opened cache connections when the
		 * object is destroyed.
		 *
		 * @access public
		 */
		public function __destruct() {
			foreach ($this->arrTiers as $strTier=>$objTier) {
				if ($objTier->blnConnected) {
					if ($this->initTier($strTier, false)) {
						$this->close();
					}
				}
			}
		}
		
		
		/**
		 * Determines if the caching module is installed.
		 *
		 * @access public
		 * @return boolean True if the cache is available
		 * @static
		 */
		static public function isAvailable() {
			throw new CoreException(AppLanguage::translate('The %s method must be defined in the extension to the %s class', __METHOD__, __CLASS__));
		}
		
		
		/**
		 * Cleans the special characters out of a key or array
		 * of keys.
		 *
		 * @access public
		 * @param mixed $mxdKey The key or array of keys to clean
		 * @return mixed The cleaned key or array of keys
		 */
		public function cleanKey($mxdKey) {
			if (is_array($mxdKey)) {
				return array_map(array($this, 'cleanKey'), $mxdKey);
			} else {
				if (strlen($strCleanKey = preg_replace('/[^a-z0-9\|:]/i', '_', $mxdKey)) > $this->intMaxKeyLength) {
					$strCleanKey = substr($strCleanKey, 0, $this->intMaxKeyLength - 34) . '__' . md5($strCleanKey);
				}
				return $strCleanKey;
			}
		}
		
		
		/*****************************************/
		/**     TIER METHODS                    **/
		/*****************************************/
		
		
		/**
		 * Returns the tier object.
		 *
		 * @access public
		 * @return object The tier object
		 */
		public function getTier($strTier) {
			if (array_key_exists($strTier, $this->arrTiers)) {
				return $this->arrTiers[$strTier];
			}
		}
		
		
		/**
		 * Returns the tier types.
		 *
		 * @access public
		 * @return array The tier types
		 */
		public function getTierTypes() {
			return array_keys($this->arrTiers);
		}
		
		
		/**
		 * Makes sure that a tier is defined and that there
		 * is a valid connection to at least one server.
		 *
		 * @access protected
		 * @return boolean True if there's a valid tier and connection
		 */
		protected function checkTier() {
			if ($this->objActive) {
				if ($this->objActive->blnConnected || $this->connect()) {
					return true;
				} else {
					trigger_error(AppLanguage::translate('No cache servers available'));
				}
			} else {
				trigger_error(AppLanguage::translate('Invalid cache tier object'));
			}
		}
		
		
		/**
		 * Adds a tier to the tier pool. If the tier is
		 * overwriting an existing tier and that tier is
		 * the active one it clears the active tier data.
		 *
		 * @access public
		 * @param string $strTier The name of the tier
		 * @param object $objTier The CacheTier object
		 * @param boolean $blnOverwrite Whether the tier being added is allowed to overwrite an existing one
		 */
		public function addTier($strTier, CacheTier $objTier, $blnOverwrite = false) {
			if (!$blnOverwrite && !empty($this->arrTiers[$strTier])) {
				throw new CoreException(AppLanguage::translate('A cache tier already exists for %s', $strTier));
			}
			if ($blnOverwrite && $this->strActive == $strTier) {
				$this->objActive = null;
				$this->strActive = null;
			}
			$this->arrTiers[$strTier] = $objTier;
		}
		
		
		/**
		 * Sets up the cache for the specific tier type
		 * passed (eg. Base, Presentation). Automatically 
		 * connects to the cache if the auto connect flag 
		 * is set to true and the connection hasn't already
		 * been made.
		 * 
		 * @access public
		 * @param boolean $blnAutoConnect Whether to automatically connect to the cache
		 * @return boolean True on success
		 */
		 public function initTier($strTier, $blnAutoConnect = true) {
		 	if (!($blnResult = ($this->strActive == $strTier))) {
				if (array_key_exists($strTier, $this->arrTiers) && is_object($this->arrTiers[$strTier])) {
					$this->objActive = $this->arrTiers[$strTier];
					
					if ($blnAutoConnect && !$this->objActive->blnConnected) {
						$this->connect($strTier);
					}
					
					if ($blnResult = $this->objActive->blnConnected) {
						$this->strActive = $strTier;
					}
				}
			}
			
			return !empty($blnResult);
		}
		
		
		/**
		 * Sets up the cache for the base tier.
		 * 
		 * @access public
		 * @param boolean $blnAutoConnect Whether to automatically connect to the cache
		 * @return boolean True on success
		 */
		public function initBase($blnAutoConnect = true) {
			return $this->initTier('Base', $blnAutoConnect);
		}
		
		
		/**
		 * Sets up the cache for the presentation tier.
		 * 
		 * @access public
		 * @param boolean $blnAutoConnect Whether to automatically connect to the cache
		 * @return boolean True on success
		 */
		public function initPresentation($blnAutoConnect = true) {
			return $this->initTier('Presentation', $blnAutoConnect);
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
			throw new CoreException(AppLanguage::translate('The %s method must be defined in the extension to the %s class', __METHOD__, __CLASS__));
		}
		
		
		/**
		 * Closes the connection to the current caching
		 * tier. Persistent connections aren't closed.
		 *
		 * @access public
		 */
		public function close() {
			throw new CoreException(AppLanguage::translate('The %s method must be defined in the extension to the %s class', __METHOD__, __CLASS__));
		}
		
		
		/**
		 * Returns the server stats for the connection pool.
		 *
		 * @access public
		 * @return array The stats array
		 */
		public function getStats() {
			throw new CoreException(AppLanguage::translate('The %s method must be defined in the extension to the %s class', __METHOD__, __CLASS__));
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
			throw new CoreException(AppLanguage::translate('The %s method must be defined in the extension to the %s class', __METHOD__, __CLASS__));
		}
		
		
		/**
		 * Retrieves the cache data for the key passed.
		 *
		 * @access public
		 * @param string $strKey The key to retrieve
		 * @return mixed The retrieved data or null on failure
		 */
		public function load($strKey) {
			throw new CoreException(AppLanguage::translate('The %s method must be defined in the extension to the %s class', __METHOD__, __CLASS__));
		}
		
		
		/**
		 * Retrieves the cache data for the keys passed.
		 *
		 * @access public
		 * @param array $arrKeys The keys to retrieve
		 * @return mixed The retrieved data or null on failure
		 */
		public function loadMulti($arrKeys) {
			throw new CoreException(AppLanguage::translate('The %s method must be defined in the extension to the %s class', __METHOD__, __CLASS__));
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
			throw new CoreException(AppLanguage::translate('The %s method must be defined in the extension to the %s class', __METHOD__, __CLASS__));
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
			throw new CoreException(AppLanguage::translate('The %s method must be defined in the extension to the %s class', __METHOD__, __CLASS__));
		}
		
		
		/**
		 * Decrements the cached record.
		 * 
		 * @access public
		 * @param string $strKey The key associated with the item
		 * @param integer $intValue The amount to decrement by; if the item isn't numeric it will be set to this
		 * @param boolean $blnCreate If this is true and the item doesn't exist it will be created
		 * @return integer The new decremented value or false on failure
		 */
		public function decrement($strKey, $intValue = 1, $blnCreate = true) {
			throw new CoreException(AppLanguage::translate('The %s method must be defined in the extension to the %s class', __METHOD__, __CLASS__));
		}
		
		
		/**
		 * Flushes the tier by setting everything to expired.
		 * 
		 * @access public
		 * @return boolean True on success
		 */
		public function flush() {
			throw new CoreException(AppLanguage::translate('The %s method must be defined in the extension to the %s class', __METHOD__, __CLASS__));
		}
		
		
		/*****************************************/
		/**     NAMESPACE METHODS               **/
		/*****************************************/
		
		
		/**
		 * Saves the cache data to an associated namespace.
		 * 
		 * @access public
		 * @param string $strKey The key associated with the item
		 * @param string $strNamespace The namespace
		 * @param mixed $mxdValue The value of the cache data
		 * @param integer $intExpire The expiration time in seconds, or 0 to never expire
		 * @param integer $intSaveType The optional save type (CACHE_ADD_ONLY, CACHE_REPLACE_ONLY)
		 * @return boolean True on success
		 */
		public function saveNS($strKey, $strNamespace, $mxdValue, $intExpire = 0, $intSaveType = null) {
			if ($strKey = $this->getNamespaceItemKey($strKey, $strNamespace)) {
				return $this->save($strKey, $mxdValue, $intExpire, $intSaveType);
			}
		}
		
		
		/**
		 * Retrieves the cache data for the key(s) passed
		 * with an associated namespace.
		 *
		 * @access public
		 * @param mixed $mxdKey The key or array of keys to retrieve
		 * @param string $strNamespace The namespace
		 * @return mixed The retrieved data or false on failure
		 */
		public function loadNS($mxdKey, $strNamespace) {
			if (is_array($mxdKey)) {
				for ($i = 0, $ix = count($mxdKey); $i < $ix; $i++) {
					if ($strKey = $this->getNamespaceItemKey($mxdKey[$i], $strNamespace)) {
						$mxdKey[$i] = $strKey;
					} else {
						unset($mxdKey[$i]);
					}
				}
				$mxdKey = array_values($mxdKey);
			} else {
				$mxdKey = $this->getNamespaceItemKey($mxdKey, $strNamespace);
			}
			
			if (!empty($mxdKey)) {
				return $this->load($mxdKey);
			}
		}
		
		
		/**
		 * Retrieves the cache data for the keys passed
		 * with an associated namespace.
		 *
		 * @access public
		 * @param array $arrKeys The keys to retrieve
		 * @param string $strNamespace The namespace
		 * @return mixed The retrieved data or false on failure
		 */
		public function loadMultiNS($arrKeys, $strNamespace) {
			for ($i = 0, $ix = count($arrKeys); $i < $ix; $i++) {
				if ($strKey = $this->getNamespaceItemKey($arrKeys[$i], $strNamespace)) {
					$arrKeys[$i] = $strKey;
				} else {
					unset($arrKeys[$i]);
				}
			}
			
			$arrKeys = array_values($arrKeys);
			if (!empty($arrKeys)) {
				return $this->loadMulti($arrKeys);
			}
		}
		
		
		/**
		 * Deletes the cache data by the key passed with
		 * an associated namespace.
		 * 
		 * @access public
		 * @param string $strKey The key associated with the item
		 * @param string $strNamespace The namespace
		 * @param integer $intTimeout The number of seconds after which the item will expire
		 * @return boolean True on success
		 */
		public function deleteNS($strKey, $strNamespace, $intTimeout = null) {
			if ($strKey = $this->getNamespaceItemKey($strKey, $strNamespace)) {
				return $this->delete($strKey, $intTimeout);
			}
		}
		
		
		/**
		 * Flushes the namespace by incrementing its value.
		 * 
		 * @access public
		 * @param string $strNamespace The namespace
		 * @return integer The new namespace value
		 */
		public function flushNS($strNamespace) {
			return $this->increment($this->getNamespaceKey($strNamespace));
		}
		
		
		/**
		 * Returns the cache key for the namespace.
		 *
		 * @access protected
		 * @param string $strNamespace The namespace
		 * @return string The namespace key
		 */
		protected function getNamespaceKey($strNamespace) {
			return $strNamespace . '|__nskey';
		}
		
		
		/**
		 * Returns the cache key for the item associated with 
		 * a namespace.
		 *
		 * @access protected
		 * @param string $strKey The cache key excluding the namespace key string
		 * @param string $strNamespace The namespace
		 * @return string The namespace key
		 */
		protected function getNamespaceItemKey($strKey, $strNamespace) {
			if (!($intNamespaceValue = $this->load($strNamespaceKey = $this->getNamespaceKey($strNamespace)))) {
				if (!($intNamespaceValue = $this->increment($strNamespaceKey, rand(1,10000), true))) {
					return false;
				}
			}
			return $strNamespace . '|' . $intNamespaceValue . '|' . $strKey;
		}
	}