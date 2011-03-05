<?php
	require_once('php/core/CoreObject.class.php');
	require_once('php/core/interfaces/Singleton.interface.php');
	
	/**
	 * CoreConfig.class.php
	 *
	 * The config class is used to store and retrieve
	 * config vars rather than having globals or constants
	 * scattered around.
	 *
	 * This is a singleton class and therefore it must
	 * be instantiated using the getInstance() method.
	 *
	 * This must be extended by an AppConfig class.
	 *
	 * Copyright 2006-2010, Phork Labs. (http://phorklabs.com)
	 *
	 * Licensed under The MIT License
	 * Redistributions of files must retain the above copyright notice.
	 *
	 * @author Elenor Collings <elenor@phork.org>
	 * @license http://www.opensource.org/licenses/mit-license.php The MIT License
	 * @package phork
	 * @subpackage core
	 * @abstract
	 */
	abstract class CoreConfig extends CoreObject implements Singleton {
		
		static protected $objInstance;
		
		protected $arrConfig;
		const DEFAULT_NAMESPACE = 'global';
		
		
		/**
		 * The constructor can't be public for a singleton.
		 *
		 * @access protected
		 */
		protected function __construct() {
			$this->arrConfig = array();
		}
		
		
		/** 
		 * Returns the instance of the singleton object. If
		 * it doesn't exist it instantiates it.
		 *
		 * @access public
		 * @return object The instance of the config object
		 * @static
		 */
		static public function getInstance() {
			if (!self::$objInstance) {
				self::$objInstance = new AppConfig();
			}
			return self::$objInstance;
		}
		
		
		/**
		 * Loads a config file and registers all the config
		 * variables here. All the variables in the config file
		 * should be named like $arrConfig['ConfigVarName'].
		 *
		 * @access public
		 * @param string $strFilePath The absolute path to the config file
		 * @param string $strNamespace The namespace to load the config data into
		 * @return array The array of config data that was loaded
		 * @static
		 */
		static public function load($strConfig, $strNamespace = self::DEFAULT_NAMESPACE) {
			if ($strFilePath = AppLoader::getIncludePath(self::get('ConfigDir') . $strConfig . '.config.php')) {
				if (include($strFilePath)) {
					if (!empty($arrConfig)) {
						$objInstance = self::getInstance();
						
						if (!empty($objInstance->arrConfig[$strNamespace])) {
							$objInstance->arrConfig[$strNamespace] = array_merge($objInstance->arrConfig[$strNamespace], $arrConfig);
						} else {
							$objInstance->arrConfig[$strNamespace] = $arrConfig;
						}
						return $arrConfig;
					}
				}
			}
		}
		
		
		/**
		 * Gets a config variable from the namespace. Optional
		 * flag to warn if the variable doesn't exist.
		 *
		 * @access public
		 * @param string $strConfig The name of the variable to get
		 * @param boolean $blnWarn Whether to trigger a notice if the variable doesn't exist
		 * @param string $strNamespace The namespace the variable is in
		 * @return mixed The variable's value
		 * @static
		 */
		static public function get($strConfig, $blnWarn = true, $strNamespace = self::DEFAULT_NAMESPACE) {
			$objInstance = self::getInstance();
			
			if (array_key_exists($strNamespace, $objInstance->arrConfig)) {
				if (array_key_exists($strConfig, $objInstance->arrConfig[$strNamespace])) {
					return $objInstance->arrConfig[$strNamespace][$strConfig];
				} else if ($blnWarn) {
					trigger_error(AppLanguage::translate('Invalid config (%s) in namespace %s', $strConfig, $strNamespace));
				}
			} else {
				trigger_error(AppLanguage::translate('Invalid config namespace (%s)', $strNamespace));
			}
		}
		
		
		/**
		 * Sets a config variable to a specific namespace.
		 *
		 * @access public
		 * @param string $strConfig The name of the variable to set
		 * @param mixed $mxdValue The value to set the variable to
		 * @param string $strNamespace The namespace the variable is in
		 * @return mixed The variable's value
		 * @static
		 */
		static public function set($strConfig, $mxdValue, $strNamespace = self::DEFAULT_NAMESPACE) {
			$objInstance = self::getInstance();
			
			if (!array_key_exists($strNamespace, $objInstance->arrConfig)) {
				$objInstance->arrConfig[$strNamespace] = array();
			}
			
			return $objInstance->arrConfig[$strNamespace][$strConfig]  = $mxdValue;
		}
	}