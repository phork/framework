<?php
	require_once('php/core/CoreObject.class.php');
	require_once('php/core/interfaces/Singleton.interface.php');
		
	/**
	 * CoreRegistry.class.php
	 *
	 * The registry is used to store objects to make
	 * them accessible throughout the system.
	 *
	 * This is a singleton class and therefore it must
	 * be instantiated using the getInstance() method.
	 *
	 * This must be extended by an AppRegistry class.
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
	abstract class CoreRegistry extends CoreObject implements Singleton {
		
		static protected $objInstance;
		
		protected $arrObjects;
		protected $arrReserved;
		
		
		/**
		 * The constructor can't be public for a singleton.
		 *
		 * @access protected
		 */
		protected function __construct() {
			$this->arrObjects = array();
			$this->arrReserved = array(
				'Error'			=> 'CoreError',
				'Database'		=> 'Sql',
				'Cache'			=> 'Cache',
				'Controller'	=> 'Controller',
				'Url'			=> 'Url'
			);
		}
		
		
		/** 
		 * Returns the instance of the singleton object. If
		 * it doesn't exist it instantiates it.
		 *
		 * @access public
		 * @return object The instance of the registry object
		 * @static
		 */
		static public function getInstance() {
			if (!self::$objInstance) {
				self::$objInstance = new AppRegistry();
			}
			return self::$objInstance;
		}
		
		
		/**
		 * Registers the object by storing it in the
		 * object array. Has additional checks to make
		 * sure that certain reserved names are of the
		 * expected object type.
		 *
		 * @access public
		 * @param string $strName The name of the registered object
		 * @param object $objObject The object to register
		 * @static
		 */
		static public function register($strName, $objObject) {
			$objInstance = self::getInstance();
			
			//make sure the object is in fact an object
			if (!is_object($objObject)) {
				throw new CoreException(AppLanguage::translate('Only objects can be added to the registry'));
			}
		
			//make sure the object hasn't been registered already
			if (isset($objInstance->arrObjects[$strName])) {
				throw new CoreException(AppLanguage::translate('An object named %s has already been registered', $strName));
			}
			
			//make sure if the object is using a reserved name that it's the right type
			if (array_key_exists($strName, $objInstance->arrReserved)) {
				if (!($objObject instanceof $objInstance->arrReserved[$strName])) {
					throw new CoreException(AppLanguage::translate('The %s object is a reserved object and must implement the %s interface', $strReserved, $objInstance->arrReserved[$strName]));
				}
			}
			
			$objInstance->arrObjects[$strName] = $objObject;
		}
		
		
		/**
		 * Returns the object registered with the key
		 * passed.
		 *
		 * @access public
		 * @param string $strName The name of the registered object
		 * @param boolean $blnFatal Throws an exception if the object isn't found
		 * @return object The object
		 * @static
		 */
		static public function get($strName, $blnFatal = true) {
			$objInstance = self::getInstance();
			
			if (!isset($objInstance->arrObjects[$strName])) {
				if ($blnFatal) {
					throw new CoreException(AppLanguage::translate('No object named %s has been registered', $strName));
				} else {
					return false;
				}
			}
			
			return $objInstance->arrObjects[$strName];
		}
		
		
		/**
		 * Removes the object from the registry.
		 *
		 * @access public
		 * @param string $strName The name of the object to remove
		 * @static
		 */
		static public function destroy($strName) {
			$objInstance = self::getInstance();
			if (array_key_exists($strName, $objInstance->arrObjects)) {
				unset($objInstance->arrObjects[$strName]);
			}
		}
	}