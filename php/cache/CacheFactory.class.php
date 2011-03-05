<?php
	/**
	 * CacheFactory.class.php
	 *
	 * Loads the cache configuration, then instantiates
	 * and registers the correct type of cache object,
	 * and sets up the connection pool. The servers are
	 * not connected to until they're needed.
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
	class CacheFactory {
		
		const CONFIG_NS = 'cache';
		protected $arrConfig;
		
		
		/**
		 * Loads the cache configuration into the cache 
		 * config namespace and validatates the config.
		 *
		 * @access public
		 * @param string $strConfig The name of the config file
		 */
		public function __construct($strConfig) {
			if ($this->arrConfig = AppConfig::load($strConfig, self::CONFIG_NS)) {
				if (!empty($this->arrConfig['Type'])) {
					if (!empty($this->arrConfig['Tiers'])) {
						if (empty($this->arrConfig['Tiers']['Base'])) {
							throw new CoreException(AppLanguage::translate('Invalid base cache configuration'));
						}
						if (empty($this->arrConfig['Tiers']['Presentation'])) {
							throw new CoreException(AppLanguage::translate('Invalid presentation cache configuration'));
						}
					}
				} else {
					throw new CoreException(AppLanguage::translate('Invalid cache type'));
				}
			} else {
				throw new CoreException(AppLanguage::translate('Invalid cache configuration'));
			}
		}
		
		
		/**
		 * Initializes and returns the appropriate cache
		 * object based on the config.
		 *
		 * @access public
		 * @return object The cache object
		 */
		public function init() {
			if ($strType = $this->arrConfig['Type']) {
				if (AppLoader::includeClass("php/cache/{$strType}/", $strType = "{$strType}Tiered")) {
					if (call_user_func(array($strType, 'isAvailable'))) {
						$arrResources = array();
						
						//set up the cache tiers
						foreach ($this->arrConfig['Tiers'] as $strTier=>$arrConfig) {
							$arrResources[$strTier] = new CacheTier($arrConfig);
						}
						
						//instantiate a new cache object
						$objCache = new $strType($arrResources['Base'], $arrResources['Presentation']);
						unset($arrResources['Base'], $arrResources['Presentation']);
						
						//add any other tiers to it
						if (count($arrResources)) {
							foreach ($arrResources as $strTier=>$objTier) {
								$objCache->addTier($strTier, $objTier);
							}
						}
						
						return $objCache;
					} else {
						throw new CoreException(AppLanguage::translate('The %s cache extension is not installed', $strType));
					}
				}
			}
		}
	}