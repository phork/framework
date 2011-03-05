<?php
	require_once('php/core/CoreObject.class.php');
	require_once('php/core/interfaces/Singleton.interface.php');
		
	/**
	 * CoreLanguage.class.php
	 *
	 * The language class is used to translate strings
	 * into different languages. Each language should
	 * have its own translation files in the lang directory
	 * named with a .lang extension. Any file without
	 * a .lang extension won't be loaded.
	 *
	 * This is a singleton class and therefore it must
	 * be instantiated using the getInstance() method.
	 *
	 * This must be extended by an AppLanguage class.
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
	abstract class CoreLanguage extends CoreObject implements Singleton {
	
		static protected $objInstance;
		
		protected $strLanguage;
		protected $arrFilePath;
		protected $strCachePath;
		protected $arrStrings;
		
		
		/**
		 * The constructor can't be public for a singleton.
		 *
		 * @access protected
		 */
		protected function __construct() {}
		
		
		/** 
		 * Returns the instance of the singleton object. If
		 * it doesn't exist it instantiates it.
		 *
		 * @access public
		 * @return object The instance of the language object
		 * @static
		 */
		static public function getInstance() {
			if (!self::$objInstance) {
				self::$objInstance = new AppLanguage();
			}
			return self::$objInstance;
		}
		
		
		/**
		 * Returns a translated string. This can have multiple
		 * arguments passed in addition to the string in a printf
		 * format. This allows translation of the main error
		 * without having to translate every variation.
		 *
		 * @access public
		 * @param string $strString The string to translate
		 * @return string The translated string
		 * @static
		 */
		static public function translate($strString) {
			$objInstance = self::getInstance();
			
			if (!empty($objInstance->arrStrings[$strString])) {
				$strString = $objInstance->arrStrings[$strString];
			}
			if (count($arrFunctionArgs = func_get_args()) > 1) {
				$strString = call_user_func_array('sprintf', $arrFunctionArgs);
			}
			
			return $strString;
		}
		
		
		/**
		 * Returns the list of files in the file path passed.
		 *
		 * @access protected
		 * @param string $strFilePath The absolute file path excluding the language dir
		 * @return array The array of absolute file paths
		 */
		protected function listFiles($strFilePath) {
			if ($strFilePath && $this->strLanguage) {
				return glob($strFilePath . $this->strLanguage . '/*.lang');
			}
		}
		
		
		/**
		 * Loads the replacements into the object from the
		 * cache or set of language files. This creates an 
		 * array of replacement values.
		 *
		 * @access protected
		 */
		protected function loadReplacements() {
			$this->arrStrings = array();
			
			if ($this->strCachePath) {
				if (file_exists($strCache = $this->strCachePath . '/' . $this->strLanguage . '.php')) {
					$this->arrStrings = include($strCache);
				}
			} else {
				if (!empty($this->arrFilePath)) {
					foreach ($this->arrFilePath as $strFilePath) {
						if ($arrFilePaths = $this->listFiles($strFilePath)) {
							foreach ($arrFilePaths as $strFile) {
								$strContents = file_get_contents($strFile);
								
								preg_match_all('/^default: (.*)$/Um', $strContents, $arrDefault);
								$arrDefault = $arrDefault[1];
								
								preg_match_all('/^replace: (.*)$/Um', $strContents, $arrReplace);
								$arrReplace = $arrReplace[1];
								
								if (count($arrDefault) == count($arrReplace)) {
									$this->arrStrings = array_merge($this->arrStrings, array_combine($arrDefault, $arrReplace));
								}
							}
						}
					}
				}
			}
		}
		
		
		/**
		 * Sets the language to use for the translations
		 * and includes and parses the language file.
		 *
		 * @access public
		 * @param string $strLanguage The language to use
		 */
		public function setLanguage($strLanguage) {
			$this->strLanguage = $strLanguage;
			$this->loadReplacements();
		}
		
		
		/**
		 * Sets the file paths to the language directories, 
		 * excluding the specific language directory. For
		 * example /path/to/lang not /path/to/lang/english.
		 *
		 * @access public
		 * @param array $arrFilePath The file paths to the language directories
		 */
		public function setFilePath($arrFilePath) {
			$this->arrFilePath = $arrFilePath;
		}
		
		
		/**
		 * Sets the path to the cached language files.
		 *
		 * @access public
		 * @param string $strCachePath The file path to the cache directory
		 */
		public function setCachePath($strCachePath) {
			$this->strCachePath = $strCachePath;
		}
	}