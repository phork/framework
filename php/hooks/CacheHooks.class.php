<?php
	require_once('php/core/CoreObject.class.php');
	
	/**
	 * CacheHooks.class.php
	 * 
	 * A collection of hooks to serve and save page caches.
	 * To be used in conjunction with the bootstrap. In order
	 * for pages to be cached the output buffer must be used
	 * in the display object. The URLs to cache should be
	 * defined as regular expression patterns in the site
	 * config files. This caches complete pages. If only
	 * certain nodes should be cached then the CoreController
	 * class should be used.
	 *
	 * <code>
	 * $arrConfig['CacheUrls'] = array(
	 * 		'|(/manual/[^/]+/[^/]+/)|'	=> array(
	 * 			'Namespace'	=> null,
	 * 			'Expire'	=> 300
	 * 		)
	 * );
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
	 * @subpackage hooks
	 */
	class CacheHooks extends CoreObject {
	
		protected $arrCacheParams;
		protected $blnCacheExists;
		
		
		/**
		 * Retrieves the cache object if there is one, and
		 * sets up the presentation tier.
		 *
		 * @access public
		 * @return object The cache object
		 */
		public function initCache() {
			if ($objCache = AppRegistry::get('Cache', false)) {
				AppDisplay::getInstance()->setBuffer(true);
				$objCache->initPresentation();
				return $objCache;
			}
		}
		
		
		/**
		 * Serves a full page from the cache if it exists.
		 * If a page is served this turns on the skip run
		 * flag in the bootstrap to prevent the page from
		 * being built normally.
		 *
		 * @access public
		 * @param boolean $blnExit Whether to serve the page and exit immediately
		 */
		public function serveCache($blnExit = false) {
			if ($arrCacheUrls = AppConfig::get('CacheUrls', false)) {
				$strMatchAgainst = AppRegistry::get('Url')->getCurrentUrl(true, false);
				foreach ($arrCacheUrls as $strUrl=>$arrCacheConfig) {
					if (preg_match($strUrl, $strMatchAgainst, $arrMatches)) {
						if ($objCache = $this->initCache()) {
							$this->arrCacheParams = $arrCacheConfig;
							$this->arrCacheParams['CacheKey'] = $strMatchAgainst ? $strMatchAgainst : 'index';
							
							if (!empty($this->arrCacheParams['Namespace'])) {
								$arrContent = $objCache->loadNS($this->arrCacheParams['CacheKey'], $this->arrCacheParams['Namespace']);
							} else {
								$arrContent = $objCache->load($this->arrCacheParams['CacheKey']);
							}
								
							if ($arrContent) {
								$this->blnCacheExists = true;
								list($arrHeaders, $strOutput) = $arrContent;
								
								$objDisplay = AppDisplay::getInstance();
								if (!empty($this->arrCacheParams['Compress'])) {
									$objDisplay->setCompress(true);
								}
								$objDisplay->setHeaders($arrHeaders);
								$objDisplay->setOutput($strOutput);
								
								if ($blnExit) {
									die(0);
								}
								
								AppRegistry::get('Bootstrap')->setSkipRun(true);
							}
						}
						break;
					}
				}
			}
		}
		
		
		/**
		 * Caches the full page output. This should not be
		 * used on any pages that have any differences for
		 * different users. In those cases the node caches
		 * should be used. If any errors have occurred the
		 * cache is not saved.
		 *
		 * @access public
		 */
		public function saveCache() {
			if ($this->arrCacheParams && !$this->blnCacheExists) {
				if (!AppRegistry::get('Error')->getErrorFlag() && !(class_exists('CoreAlert', false) && CoreAlert::getAlertFlag())) {
					if ($objCache = $this->initCache()) {
						$intExpire = !empty($this->arrCacheParams['Expire']) ? $this->arrCacheParams['Expire'] : 0;
						
						$objDisplay = AppDisplay::getInstance(); 
						$objDisplay->build();
						
						$arrHeaders = $objDisplay->getHeaders();
						$strOutput = $objDisplay->getOutput();
						$arrContent = array($arrHeaders, $strOutput);
						
						if (!empty($this->arrCacheParams['Namespace'])) {
							$objCache->saveNS($this->arrCacheParams['CacheKey'], $this->arrCacheParams['Namespace'], $arrContent, $intExpire);
						} else {
							$objCache->save($this->arrCacheParams['CacheKey'], $arrContent, $intExpire);
						}
					}
				}
			}
		}
	}