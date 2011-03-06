<?php
	require_once('php/core/CoreModelHelper.class.php');
	
	/**
	 * ModelCache.class.php
	 * 
	 * A model helper class to handle loading, saving,
	 * and clearing caches (eg. memcache, filecache, etc.)
	 * This registers events that are run by the model
	 * object. Any data returned from the event methods
	 * is available in the function that runs the helper
	 * event.
	 *
	 * If this class loads a cache containing a custom
	 * record relation then this should either rely on an
	 * __autoload() function to load the record object 
	 * or else the application should've included that 
	 * record object prior to this being called, otherwise
	 * the record may end up being a __PHP_Incomplete_Class
	 * object.
	 *
	 * This helper can be invoked in multiple ways. It
	 * can be appended and initialized in the constructor
	 * to cache every load method, it can be appended
	 * and initialized in individual load functions and
	 * or it can be appended and initialized outside of 
	 * the model before theload function is called and 
	 * removed right after.
	 *
	 * <code>
	 * AppLoader::includeExtension('helpers/', 'ModelCache')
	 * $objModel->appendHelper('cache', 'ModelCache');
	 * $objModel->initHelper('cache', array('preLoad', 'postLoad'), array(
	 * 		'Namespace' => 'foo', 
	 *		'Expire' => 60
	 * ));
	 * ...
	 * $objModel->destroyHelper('cache');
	 * </code>
	 *
	 * Copyright 2006-2011, Phork Labs. (http://phorklabs.com)
	 *
	 * Licensed under The MIT License
	 * Redistributions of files must retain the above copyright notice.
	 *
	 * @author Elenor Collings <elenor@phork.org>
	 * @license http://www.opensource.org/licenses/mit-license.php The MIT License
	 * @package phork
	 * @subpackage helpers
	 */
	class ModelCache extends CoreModelHelper {
		
		protected $strCacheKey;
		protected $strNamespace;
		protected $intExpire;
		protected $blnCacheFailed;
		
		const EXPIRE = 300;
		
		
		/**
		 * Initializes the helper object by registering
		 * the events to run. This should be called from
		 * a model object.
		 *
		 * @access public
		 * @param array $arrEvents The names of the events to register
		 * @param array $arrConfig An array of config vars specific to this initialization
		 */
		public function init($arrEvents, $arrConfig = array()) {
			if (AppRegistry::get('Cache', false)) {
				$this->intExpire = (array_key_exists('Expire', $arrConfig) ? $arrConfig['Expire'] : self::EXPIRE);
				$this->strNamespace = !empty($arrConfig['Namespace']) ? $arrConfig['Namespace'] : null;
			
				//attempts to load the data from the cache during the model's pre-load event
				if (in_array('preLoad', $arrEvents)) {
					$this->arrEvents[] = array(
						'Event'	=> ($strEvent = $this->strModelKey . '.pre-load'),
						'Key'	=> AppEvent::register($strEvent, array($this, 'loadFromCache'))
					);	
				} 
				
				//saves the loaded data to the cache during the model's post-load event
				if (in_array('postLoad', $arrEvents)) {
					$this->arrEvents[] = array(
						'Event'	=> ($strEvent = $this->strModelKey . '.post-load'),
						'Key'	=> AppEvent::register($strEvent, array($this, 'saveToCache'))
					);
				}
				
				//clears any necessary caches during the model's post-save event
				if (in_array('postSave', $arrEvents)) {
					$this->arrEvents[] = array(
						'Event'	=> ($strEvent = $this->strModelKey . '.post-save'),
						'Key'	=> AppEvent::register($strEvent, array($this, 'clearCachePostSave'))
					);
				}
				
				//clears any necessary caches during the model's post-delete event
				if (in_array('postDelete', $arrEvents)) {
					$this->arrEvents[] = array(
						'Event'	=> ($strEvent = $this->strModelKey . '.post-delete'),
						'Key'	=> AppEvent::register($strEvent, array($this, 'clearCachePostDelete'))
					);
				}
			}
		}
		
		
		/**
		 * Retrieves the cache object if there is one, and
		 * sets up the base tier.
		 *
		 * @access protected
		 * @return object The cache object
		 */
		protected function initCache() {
			if ($objCache = AppRegistry::get('Cache')) {
				$objCache->initBase();
				return $objCache;
			}
		}
		
		
		/**
		 * Loads the function results from the cache. If
		 * successful this sets the flag to skip loading
		 * and sets the result flag to successful.
		 *
		 * @access public
		 * @param object $objModel The model object associated with the caching
		 * @return array The result in extractable array format
		 */
		public function loadFromCache($objModel) {
			$this->blnCacheFailed = false;
			if (isset($objModel) && $objModel instanceof CoreModel) {
				$this->strCacheKey = serialize($objModel->getLoading());
				
				if ($this->strNamespace) {
					$arrContent = $this->initCache()->loadNS($this->strCacheKey, $this->strNamespace);
				} else {
					$arrContent = $this->initCache()->load($this->strCacheKey);
				}
				
				if ($arrContent) {
					if (!empty($arrContent[0]) && $arrContent[0] instanceof Iterator) {
						$objModel->setRecords($arrContent[0]);
						$objModel->setFoundRows($arrContent[1]);
						
						return array(
							'blnSkipLoad'	=> true,
							'blnResult'		=> true
						);
					}
				} else {
					$this->blnCacheFailed = true;
				}
			} else {
				throw new CoreException(AppLanguage::translate('The %s method should be passed the CoreModel object as the first argument', __FUNCTION__));
			}
		}
		
		
		/**
		 * Saves any data to the cache for any failed cache
		 * retrievals. Only saves if there were no errors.
		 * 
		 * @access public
		 * @param object $objModel The model object associated with the caching
		 * @return array The result in extractable array format
		 */
		public function saveToCache($objModel) {
			if ($this->blnCacheFailed && !AppRegistry::get('Error')->getErrorFlag()) {
				if (isset($objModel) && $objModel instanceof CoreModel) {
					$arrCache = array($objModel->getRecords(), $objModel->getFoundRows());
					
					if ($this->strNamespace) {
						$this->initCache()->saveNS($this->strCacheKey, $this->strNamespace, $arrCache, $this->intExpire);
					} else {
						$this->initCache()->save($this->strCacheKey, $arrCache, $this->intExpire);
					}
				} else {
					throw new CoreException(AppLanguage::translate('The %s method should be passed the CoreModel object as the first argument', __FUNCTION__));
				}
			}
		}
		
		
		/**
		 * Dispatches processing to a helper utility to 
		 * clear any necessary caches after the object has
		 * been saved successfully. The helper can clear
		 * both base and presentation tier caches.
		 *
		 * @access public
		 * @param object $objModel The model object associated with the caching
		 * @param string $strFunction The save function that was called
		 * @param boolean $blnNewRecord True if the record saved was inserted
		 * @return array The result in extractable array format
		 */
		public function clearCachePostSave($objModel, $strFunction, $blnNewRecord) {
			$arrFunctionArgs = func_get_args();
			if (!empty($arrFunctionArgs[4])) {
				AppLoader::includeUtility('CacheHelper');
				$blnResult = CacheHelper::clearByModel($objModel, $strFunction, $blnNewRecord, false);
			}
		}
		
		
		/**
		 * Dispatches processing to a helper utility to 
		 * clear any necessary caches after the object has
		 * been deleted successfully. The helper can clear
		 * both base and presentation tier caches.
		 *
		 * @access public
		 * @param object $objModel The model object associated with the caching
		 * @param string $strFunction The save function that was called
		 * @param array $arrFilters The filters used to delete the data
		 * @return array The result in extractable array format
		 */
		public function clearCachePostDelete($objModel, $strFunction, $arrFilters) {
			$arrFunctionArgs = func_get_args();
			if (!empty($arrFunctionArgs[3])) {
				AppLoader::includeUtility('CacheHelper');
				$blnResult = CacheHelper::clearByModel($objModel, $strFunction, false, $arrFilters);
			}
		}
	}