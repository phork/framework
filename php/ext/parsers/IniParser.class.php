<?php
	require_once('interfaces/Parser.interface.php');

	/**
	 * IniParser.class.php
	 * 
	 * Parses ini-like files into an array. Each line
	 * to parse can have multiple levels. For example 
	 * Foo.Bar.Baz = 1 becomes $arrParsed['Foo']['Bar']['Baz'] = 1.
	 *
	 * <code>
	 * [Display]
	 * 
	 * Display.Title = "Registered Users"
	 * Display.Blocks.Edit.0 = "userlog"
	 *
	 * [List]
	 * 
	 * List.Username.Title = "Username"
	 * List.Username.Element = "Username"
	 * 
	 * List.Fullname.Title = "Full Name"
	 * List.Fullname.Element = "Fullname"
	 * 
	 * List.Email.Title = "Email"
	 * List.Email.Element = "Email"
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
	 * @subpackage parsers
	 */
	class IniParser implements Parser {
		
		protected $arrConfigRaw;
		protected $arrConfig = array();
		
		
		/**
		 * Loads the config data into the object.
		 *
		 * @access public
		 * @param string $strConfig The config data to parse
		 * @return boolean True if loaded
		 */
		public function loadFromString($strConfig) {
			if (function_exists('parse_ini_string')) {
				$this->arrConfigRaw = parse_ini_string($strConfig, true);
			} else {
				AppLoader::includeExtension('files/', 'LocalFileSystem');
				$objFileSystem = new LocalFileSystem();
				$strTempDir = $objFileSystem->useTemp();
				
				if ($strFileName = $objFileSystem->createTempFile($strConfig)) {
					$this->loadFromFile($strTempDir . $strFileName);
					$objFileSystem->deleteFile($strFileName, true);
				}
				unset($objFileSystem);
			}
			return !empty($this->arrConfigRaw);
		}
		
		
		/**
		 * Loads a config file into the object.
		 *
		 * @access public
		 * @param string $strFilePath The filepath to the config
		 * @return boolean True if loaded
		 */
		public function loadFromFile($strFilePath) {
			$this->arrConfigRaw = parse_ini_file($strFilePath, true);
			return !empty($this->arrConfigRaw);
		}
		
		
		/*****************************************/
		/**     PROCESS METHODS                 **/
		/*****************************************/
		
		
		/**
		 * Processes all of the config data.
		 *
		 * @access protected
		 */
		protected function process() {
			foreach (array_keys($this->arrConfigRaw) as $strSection) {
				$this->processSection($strSection);
			}
		}
		
		
		/**
		 * Processes the config data keys to return a 
		 * multi-dimensional array of config data.
		 *
		 * @access protected
		 * @param array $arrResult The current result so far
		 * @param string $strKey The key to process
		 * @param mixed $mxdValue The value to set once the array has been created
		 */
		protected function processKey($arrResult, $strKey, $mxdValue) {
			if (strpos($strKey, '.') !== false) {
				$arrKeys = explode('.', $strKey, 2);
				if (isset($arrKeys[1])) {
					if (!isset($arrResult[$arrKeys[0]])) {
						$arrResult[$arrKeys[0]] = array();
					} else if (!is_array($arrResult[$arrKeys[0]])) {
						throw new CoreException(AppLanguage::translate('Invalid key: %s already exists', $arrKeys[0]));
					}
					$arrResult[$arrKeys[0]] = $this->processKey($arrResult[$arrKeys[0]], $arrKeys[1], $mxdValue);
				} else {
					throw new CoreException(AppLanguage::translate('Invalid key: %s', $strKey));
				}
			} else {
				$arrResult[$strKey] = $mxdValue;
			}
			
			return $arrResult;
		}
		
		
		/**
		 * Processes a section of the config data.
		 *
		 * @access protected
		 * @param array $strSection The section of config data to process
		 * @return boolean True on success
		 */
		protected function processSection($strSection) {
			if (!empty($this->arrConfigRaw[$strSection])) {
				$arrResult = array();
				
				foreach ($this->arrConfigRaw[$strSection] as $strKey=>$mxdValue) {
					$arrResult = $this->processKey($arrResult, $strKey, $mxdValue);
				}
				
				$this->arrConfig = array_merge($this->arrConfig, $arrResult);
				unset($this->arrConfigRaw[$strSection]);
				
				return true;
			}
		}
		
		
		/*****************************************/
		/**     GET METHODS                     **/
		/*****************************************/
		
		
		/**
		 * Returns the processed configuration for the section.
		 *
		 * @access public
		 * @param string $strSection The configuration section
		 * @param boolean $blnRequired Whether the config is required to be defined
		 * @return array The configuration data, if successful
		 */
		public function getParsedSection($strSection, $blnRequired = false) {
			if (!isset($this->arrConfig[$strSection])) {
				if (isset($this->arrConfigRaw[$strSection])) {
					if (!$this->processSection($strSection)) {
						return false;
					}
				} else if ($blnRequired == true) {
					throw new CoreException(AppLanguage::translate('Invalid config section: %s', $strSection));
				}
			}
			
			if (isset($this->arrConfig[$strSection])) {
				return $this->arrConfig[$strSection];
			}
		}
		
		
		/**
		 * Returns the processed configuration.
		 *
		 * @access public
		 * @return array The configuration data, if successful
		 */
		public function getParsed() {
			$this->process();
			return $this->arrConfig;
		}
	}