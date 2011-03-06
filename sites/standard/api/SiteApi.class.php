<?php
	require_once('php/core/CoreApi.class.php');
	
	/**
	 * SiteApi.class.php
	 * 
	 * This class handles all the site API calls. This is
	 * an extension of the CoreApi class with additional
	 * handling for caching. All API caches are saved in 
	 * the presentation layer.
	 *
	 * Copyright 2006-2011, Phork Labs. (http://phorklabs.com)
	 *
	 * @author Elenor Collings <elenor@phork.org>
	 * @package phork
	 * @subpackage api
	 */
	class SiteApi extends CoreApi {
		
		protected $blnNoCache;
		
		
		/**
		 * Returns the cache key consisting of the current URL
		 * including the query string.
		 *
		 * @access protected
		 * @return string The cache key
		 */
		protected function getCacheKey() {
			return AppRegistry::get('Url')->getCurrentUrl(true, false);
		}
		
	
		/**
		 * Loads the API data from the cache. Has the option
		 * to load from a namespace.
		 *
		 * @access protected
		 * @param string $strNamespace The optional namespace to load from
		 * @return boolean True on success
		 */
		protected function loadFromCache($strNamespace = null) {
			$blnResult = false;
			if (!$this->blnNoCache) {
				if ($objCache = AppRegistry::get('Cache', false)) {
					$objCache->initPresentation();
					if ($strNamespace) {
						$arrResult = $objCache->loadNS($this->getCacheKey(), $strNamespace);
					} else {
						$arrResult = $objCache->load($this->getCacheKey());
					}
					
					if ($arrResult) {
						$this->blnSuccess = true;
						$this->arrResult = $arrResult;
						$blnResult = true;
					}
				}
			}
			return $blnResult;
		}
		
		
		/**
		 * Saves the API data to the cache. Has the option
		 * to save to a namespace.
		 *
		 * @access protected
		 * @param integer $intExpire The expiration time in seconds, or 0 to never expire
		 * @param string $strNamespace The optional namespace to save to
		 * @return boolean True on success
		 */
		protected function saveToCache($intExpire, $strNamespace = null) {
			$blnResult = true;
			if ($objCache = AppRegistry::get('Cache', false)) {
				$objCache->initPresentation();
				if ($strNamespace) {
					$blnResult = $objCache->saveNS($this->getCacheKey(), $strNamespace, $this->arrResult, $intExpire);
				} else {
					$blnResult = $objCache->save($this->getCacheKey(), $this->arrResult, $intExpire);
				}
			}
			return $blnResult;
		}
		
		
		/**
		 * Caches a deleted ID in a user-specific cache so that
		 * the item can appear deleted to them before the other
		 * caches it's in have been refreshed.
		 *
		 * @access protected
		 * @param integer $intId The ID of the item that was deleted
		 * @param integer $intExpire The expiration time in seconds, or 0 to never expire
		 * @return boolean True on success
		 */
		protected function cacheDeletedId($intId, $intExpire) {
			if ($intUserId = AppRegistry::get('UserLogin')->getUserId()) {
				if ($objCache = AppRegistry::get('Cache', false)) {
					$objCache->initPresentation();
					$strCacheKey = sprintf(AppConfig::get('DeletedItemCache'), "$this", $intUserId);
					
					$arrCache = $objCache->load($strCacheKey);
					$arrCache[$intId] = time();
					
					return $objCache->save($strCacheKey, $arrCache, $intExpire);
				}
			}
		}
		
		
		/**
		 * Removes any deleted items by ID.
		 *
		 * @access protected
		 * @param array $arrRecords The item list to filter
		 * @return array The array of filtered items
		 */
		protected function removeDeleted(&$arrRecords) {
			if ($intUserId = AppRegistry::get('UserLogin')->getUserId()) {
				if ($objCache = AppRegistry::get('Cache', false)) {
					$objCache->initPresentation();
					$strCacheKey = sprintf(AppConfig::get('DeletedItemCache'), "$this", $intUserId);
					if ($arrCache = $objCache->load($strCacheKey)) {
						foreach ($arrRecords as $intKey=>$arrRecord) {
							if (!empty($arrCache[$arrRecord['id']])) {
								unset($arrRecords[$intKey]);
							}
						}
						$arrRecords = array_values($arrRecords);
					}
				}
			}
		}
	}