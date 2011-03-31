<?php
	require_once('php/core/CoreObject.class.php');
	require_once('interfaces/Singleton.interface.php');
	
	/**
	 * CoreLoader.class.php
	 * 
	 * The loader class is used to include files. It
	 * has special methods to include controller, api
	 * model, hook, extension, and utility classes.
	 *
	 * This is a singleton class and therefore it must
	 * be instantiated using the getInstance() method.
	 *
	 * This must be extended by an AppLoader class.
	 *
	 * Copyright 2006-2011, Phork Labs. (http://phorklabs.com)
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
	abstract class CoreLoader extends CoreObject implements Singleton {
		
		static protected $objInstance;
		
		protected $arrIncludePaths;
		protected $strClassExtension = '.class.php';
		protected $strFileExtension = '.php';
		
	
		/**
		 * The constructor can't be public for a singleton.
		 *
		 * @access protected
		 */
		protected function __construct() {
			$this->arrIncludePaths = array();
			foreach (explode(PATH_SEPARATOR, get_include_path()) as $strPath) {
				$this->arrIncludePaths[] = $strPath . (substr($strPath, -1) != DIRECTORY_SEPARATOR ? DIRECTORY_SEPARATOR : '');
			}
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
				self::$objInstance = new AppLoader();
			}
			return self::$objInstance;
		}
		
		
		/**
		 * Includes a class file and instantiates a new object.
		 * Checks all the include paths for the class.
		 *
		 * @access public
		 * @param string $strClassPath The directory path of the file
		 * @param string $strClass The class name to instantiate
		 * @param array $arrParams The array of parameters to pass to the constructor
		 * @return object The instantiated object
		 * @static
		 */
		static public function newObject($strClassPath, $strClass, $arrParams = null) {
			$objInstance = self::getInstance();
			
			if ($objInstance->includeClass($strClassPath, $strClass)) {
				if ($arrParams) {
					$objReflection = new ReflectionClass($strClass);
					if (method_exists($objReflection, 'newInstanceArgs')) {
						$objReturn = $objReflection->newInstanceArgs($arrParams);
					} else {
						$objReturn = call_user_func_array(
							array($objReflection, 'newInstance'),
							$arrParams
						);
					}
				} else {
					$objReturn = new $strClass();
				}
				
				return $objReturn;
			}
		}
		
		
		/*****************************************/
		/**     INCLUDE METHODS                 **/
		/*****************************************/
		
		
		/**
		 * Includes a controller class. This first checks the
		 * global directory and then the site-specific directory.
		 *
		 * @access public
		 * @param string $strClass The controller class name
		 * @return boolean True on success
		 * @static
		 */
		static public function includeController($strClass) {
			return self::getInstance()->includeClass(AppConfig::get('SiteDir') . 'controllers/', $strClass, false) ||
			       self::getInstance()->includeClass(AppConfig::get('InstallDir') . 'php/core/', $strClass);
		}
		
		
		/**
		 * Includes a model class.
		 *
		 * @access public
		 * @param string $strClass The model class name
		 * @return boolean True on success
		 * @static
		 */
		static public function includeModel($strClass) {
			return self::getInstance()->includeClass(AppConfig::get('SiteDir') . 'models/', $strClass);
		}
		
		
		/**
		 * Includes an API class.
		 *
		 * @access public
		 * @param string $strClass The API class name
		 * @return boolean True on success
		 * @static
		 */
		static public function includeApi($strClass) {
			return self::getInstance()->includeClass(AppConfig::get('SiteDir') . 'api/', $strClass);
		}
		
		
		/**
		 * Includes a hook class. This first checks the global
		 * directory and then the site-specific directory.
		 *
		 * @access public
		 * @param string $strClass The hook class name
		 * @return boolean True on success
		 * @static
		 */
		static public function includeHooks($strClass) {
			return self::getInstance()->includeClass(AppConfig::get('InstallDir') . 'php/hooks/', $strClass, false) ||
			       self::getInstance()->includeClass(AppConfig::get('SiteDir') . 'hooks/', $strClass);
		}
		
		
		/**
		 * Includes an extension class or extension file. If
		 * including an extension file the $strClass param
		 * should be the name of the file excluding the file
		 * extension.
		 *
		 * @access public
		 * @param string $strExtension The extension sub-directory
		 * @param string $strClass The extension class name
		 * @param boolean $blnFile Whether to use the file include method
		 * @return boolean True on success
		 * @static
		 */
		static public function includeExtension($strExtensionDir, $strClass, $blnFile = false) {
			$strMethod = $blnFile ? 'includeFile' : 'includeClass';
			return self::getInstance()->$strMethod(AppConfig::get('InstallDir') . 'php/ext/' . $strExtensionDir, $strClass);
		}
		
		
		/**
		 * Includes a utility class. This first checks the global
		 * directory and then the site-specific directory.
		 *
		 * @access public
		 * @param string $strClass The utility class name
		 * @return boolean True on success
		 * @static
		 */
		static public function includeUtility($strClass) {
			return self::getInstance()->includeClass(AppConfig::get('InstallDir') . 'php/utilities/', $strClass, false) ||
			       self::getInstance()->includeClass(AppConfig::get('SiteDir') . 'utilities/', $strClass);
		}
		
		
		/**
		 * Includes any type of class.
		 *
		 * @access public
		 * @param string $strClassPath The directory path of the file
		 * @param string $strClass The class name
		 * @param boolean $blnFatal Throws an exception if the include fails
		 * @return boolean True on success
		 * @static
		 */
		static public function includeClass($strClassPath, $strClass, $blnFatal = true) {
			$objInstance = self::getInstance();
			
			if (!($blnResult = class_exists($strClass, false))) {
				if (($strIncludePath = $objInstance->getIncludePath($strClassPath . $strClass . $objInstance->strClassExtension, $blnFatal))) {
					if (include($strIncludePath)) {
						if (!($blnResult = class_exists($strClass, false))) {
							if ($blnFatal) {
								throw new CoreException(AppLanguage::translate('Invalid class: %s', $strClass));
							}
						}
					} else {
						if ($blnFatal) {
							throw new CoreException(AppLanguage::translate('Class include error: %s', $strClass));
						}
					}
				} else {
					if ($blnFatal) {
						throw new CoreException(AppLanguage::translate('Invalid class include: %s', $strClass));
					}
				}
			}
			
			return $blnResult;
		}
		
		
		/**
		 * Includes any type of non-class file or an external class.
		 * This is similar to the includeClass method but allows 
		 * for classes to be included from files that aren't named
		 * exactly the same as the class.
		 *
		 * @access public
		 * @param string $strFilePath The directory path of the file
		 * @param string $strFile The file name
		 * @param boolean $blnFatal Throws an exception if the include fails
		 * @return boolean True on success
		 * @static
		 */
		static public function includeFile($strFilePath, $strFile, $blnFatal = true) {
			$objInstance = self::getInstance();
			
			if (($strIncludePath = $objInstance->getIncludePath($strFilePath . $strFile . $objInstance->strFileExtension, $blnFatal))) {
				if (!($blnResult = include_once($strIncludePath))) {
					if ($blnFatal) {
						throw new CoreException(AppLanguage::translate('File include error: %s', $strFile));
					}
				}
			} else {
				if ($blnFatal) {
					throw new CoreException(AppLanguage::translate('Invalid file include: %s', $strFile));
				}
			}
			
			return !empty($blnResult);
		}
		
		
		/*****************************************/
		/**     PATH METHODS                    **/
		/*****************************************/
		
		
		/**
		 * Returns the include path to a file after checking for
		 * it in all the include paths. If the path passed begins
		 * with the directory separator it's treated as an absolute
		 * path and not subjected to the include path loop.
		 *
		 * @access public
		 * @param string $strFile The file to get the include path for
		 * @param boolean $blnFatal Throws an exception if the file isn't found
		 * @return string The include path if it exists and is readable
		 * @static
		 */
		static public function getIncludePath($strFile, $blnFatal = true) {
			$objInstance = self::getInstance();
			$strReturn = null;
			
			if (DIRECTORY_SEPARATOR != '/') {
				$strFile = str_replace('/', DIRECTORY_SEPARATOR, $strFile);
			}
			
			if (!is_file($strFile)) {
				if (substr(trim($strFile), 0, 1) != DIRECTORY_SEPARATOR) {
					foreach ($objInstance->arrIncludePaths as $strPath) {
						$strFullPath = $strPath . $strFile;
						if (is_file($strFullPath)) {
							if (is_readable($strFullPath)) {
								$strReturn = $strFullPath;
							}
							break;
						}
					}
				}
			} else {
				$strReturn = $strFile;
			}
			
			if (!$strReturn && $blnFatal) {
				throw new CoreException(AppLanguage::translate('Invalid include path: %s', $strFile));
			}
			
			return $strReturn;
		}
	}