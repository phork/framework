<?php
	/**
	 * DatabaseFactory.class.php
	 *
	 * Loads the database configuration, instantiates
	 * and registers the correct type of database object,
	 * and sets up the connection pool. The servers are
	 * not connected to until they're needed.
	 *
	 * Copyright 2006-2010, Phork Labs. (http://phorklabs.com)
	 *
	 * Licensed under The MIT License
	 * Redistributions of files must retain the above copyright notice.
	 *
	 * @author Elenor Collings <elenor@phork.org>
	 * @license http://www.opensource.org/licenses/mit-license.php The MIT License
	 * @package phork
	 * @subpackage database
	 */
	class DatabaseFactory {
	
		const CONFIG_NS = 'database';
		protected $arrConfig;
		
		
		/**
		 * Loads the database configuration into the 
		 * database config namespace and validatates 
		 * the config.
		 *
		 * @access public
		 * @param mixed $mxdConfig The name of the config file or the array of config values
		 */
		public function __construct($mxdConfig) {
			if ((is_array($mxdConfig) && $this->arrConfig = $mxdConfig) || ($this->arrConfig = AppConfig::load($mxdConfig, self::CONFIG_NS))) {
				if (!empty($this->arrConfig['Type'])) {
					if (!empty($this->arrConfig['Connections'])) {
						if (empty($this->arrConfig['Connections']['Read'])) {
							throw new CoreException(AppLanguage::translate('Invalid read database configuration'));
						}
						if (empty($this->arrConfig['Connections']['Write'])) {
							throw new CoreException(AppLanguage::translate('Invalid write database configuration'));
						}
					}
				} else {
					throw new CoreException(AppLanguage::translate('Invalid database type'));
				}
			} else {
				throw new CoreException(AppLanguage::translate('Invalid database configuration'));
			}
		}
		
		
		/**
		 * Includes the database adaptor class and the optional
		 * query class, and initializes and returns the database
		 * object based on the config.
		 *
		 * @access public
		 * @return object The database object
		 */
		public function init() {
			if ($strType = $this->arrConfig['Type']) {
				if (AppLoader::includeClass("php/database/{$strType}/", $strType)) {
					if (call_user_func(array($strType, 'isAvailable'))) {
						$arrResources = array();
						
						//set up the database resources
						foreach ($this->arrConfig['Connections'] as $strConn=>$arrConfig) {
							$arrResources[$strConn] = new DatabaseResource($arrConfig['Database'], $arrConfig['User'], $arrConfig['Password'], $arrConfig['Host'], $arrConfig['Port'], $arrConfig['Persistent']);
						}
						
						//include the query builder if applicable
						if (!empty($this->arrConfig['QueryBuilder'])) {
							AppLoader::includeClass("database/{$strType}/", "{$strType}Query");
						}
						
						//instantiate a new database object
						$objDb = new $strType($arrResources['Read'], $arrResources['Write']);
						unset($arrResources['Read'], $arrResources['Write']);
						
						//add any other connections to it
						if (count($arrResources)) {
							foreach ($arrResources as $strConn=>$objResource) {
								$objDb->addConnection($strConn, $objResource);
							}
						}
						
						return $objDb;
					} else {
						throw new CoreException(AppLanguage::translate('The %s database extension is not installed', $strType));
					}
				}
			}
		}
	}