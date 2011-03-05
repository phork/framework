<?php
	/**
	 * CoreError.class.php
	 * 
	 * The error class handles all errors called from the 
	 * trigger_error() function. This should be used for
	 * all errors caused by the user. The errors are stored
	 * in the $arrErrors array.
	 * 
	 * It's also possible to store errors for just one
	 * particular section by using the error groups.
	 *
	 * <code>
	 * $objError->startGroup('Database', false);
	 * ...
	 * if ($objError->endGroup('Database') > 0) {
	 *	 $arrErrors = $objError->getGroupErrors('Database');
	 *	 $objError->clearGroupErrors('Database');
	 * }
	 * </code>
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
	 */
	class CoreError {
		
		protected $arrErrors = array();
		protected $arrErrorsByGroup = array();
		protected $arrGroupStack = array();
		
		protected $blnDebugMode;
		
		protected $strLogFile;
		protected $blnLogNotice;
		protected $blnLogWarning;
		protected $blnLogError;
		
		protected $blnErrorFlag = false;

		
		/**
		 * Sets up itself to be the error handler.
		 *
		 * @access public
		 * @param boolean $blnDebugMode Whether the error mode should display verbose errors
		 * @param string $strLogFile The file to log the errors to
		 * @param boolean $blnLogNotice Whether to log notices
		 * @param boolean $blnLogWarning Whether to log warnings
		 * @param boolean $blnLogError Whether to log errors
		 */
		public function __construct($blnDebugMode = false, $strLogFile = null, $blnLogNotice = false, $blnLogWarning = false, $blnLogError = false) {
			set_error_handler(array($this, 'handler'));
					
			$this->blnDebugMode = $blnDebugMode;
			$this->strLogFile = $strLogFile;
			
			$this->blnLogNotice = $blnLogNotice && $this->strLogFile;
			$this->blnLogWarning = $blnLogWarning && $this->strLogFile;
			$this->blnLogError = $blnLogError && $this->strLogFile;
		}
		
		
		/**
		 * Handles the error information. The user errors
		 * triggered by trigger_error() are automatically
		 * handled regardless of the error reporting.
		 * The following error levels can't be handled:
		 * E_ERROR, E_PARSE, E_CORE_ERROR, E_CORE_WARNING
		 * E_COMPILE_ERROR, E_COMPILE_WARNING.
		 *
		 * @access public
		 * @param integer $intErrorNo The error number
		 * @param string $strError The error description
		 * @param string $strFile The file containing the error
		 * @param integer $intLine The line number of the error
		 * @return boolean True
		 */
		public function handler($intErrorNo, $strError, $strFile, $intLine) {
			$blnUserError = ($intErrorNo == E_USER_NOTICE || $intErrorNo == E_USER_WARNING || $intErrorNo == E_USER_ERROR);
			if ($blnUserError || $intErrorNo & error_reporting()) {
				switch($intErrorNo) {
					
					case E_STRICT:
					case E_USER_NOTICE:
					case E_NOTICE:
						$strError = $this->handleNotice($intErrorNo, $strError, $strFile, $intLine);
						break;
					
					case E_USER_WARNING:
					case E_COMPILE_WARNING:
					case E_CORE_WARNING:
					case E_WARNING: 
						$strError = $this->handleWarning($intErrorNo, $strError, $strFile, $intLine);
						break;
					 
					case E_PARSE:
					case E_USER_ERROR:
					case E_COMPILE_ERROR:
					case E_CORE_ERROR:
					case E_ERROR:
					default:
						$strError = $this->handleError($intErrorNo, $strError, $strFile, $intLine);
						break;
				}
				
				if ($strError) {
					if ($strGroup = $this->getGroup()) {
						$this->arrErrorsByGroup[$strGroup][] = $strError;
					}
					
					if (!$strGroup || $this->arrGroupStack[$strGroup]) {
						$this->error($strError);
					}
				}
			}
			return true;
		}
		
		
		/**
		 * Appends an error to the errors array. If the first
		 * flag is set the error is added to the beginning of 
		 * the array.
		 *
		 * @access public
		 * @param string $strError The error to append
		 * @param boolean $blnFirst Whether to prepend the error to the beginning of the array
		 */
		public function error($strError, $blnFirst = false) {
			$this->blnErrorFlag = true;
			$strFunction = $blnFirst ? 'array_unshift' : 'array_push';
			$strFunction($this->arrErrors, $strError);
		}
		
		
		/*****************************************/
		/**     ERROR HANDLERS                  **/
		/*****************************************/	
		
		
		/**
		 * Handles a notice by logging it if applicable and returning
		 * either the standard or verbose error.
		 *
		 * @access protected
		 * @param integer $intErrorNo The error number
		 * @param string $strError The error
		 * @param string $strFile The file containing the error
		 * @param integer $intLine The line number of the error
		 * @return string The error message or false
		 */
		protected function handleNotice($intErrorNo, $strError, $strFile, $intLine) {
			$strVerbose = AppLanguage::translate('Notice: %s in %s on line %d', $strError, $strFile, $intLine);
			
			if ($this->blnLogNotice) {
				$this->logError($strVerbose);
			}
			
			if ($this->blnDebugMode) {
				return $strVerbose;
			} else {
				return $strError;
			}
		}
		
		
		/**
		 * Handles a warning by logging it if applicable and returning
		 * either the standard or verbose error.
		 *
		 * @access protected
		 * @param integer $intErrorNo The error number
		 * @param string $strError The error
		 * @param string $strFile The file containing the error
		 * @param integer $intLine The line number of the error
		 * @return string The error message or false
		 */
		protected function handleWarning($intErrorNo, $strError, $strFile, $intLine) {
			$strVerbose = AppLanguage::translate('Warning: %s in %s on line %d', $strError, $strFile, $intLine);
			
			if ($this->blnLogWarning) {	
				$this->logError($strVerbose);
			}
			
			if ($this->blnDebugMode) {
				return $strVerbose;
			} else {
				return $strError;
			}
		}
		
		
		/**
		 * Handles an error by logging it if applicable and returning
		 * either the standard or verbose error.
		 *
		 * @access protected
		 * @param integer $intErrorNo The error number
		 * @param string $strError The error
		 * @param string $strFile The file containing the error
		 * @param integer $intLine The line number of the error
		 */
		protected function handleError($intErrorNo, $strError, $strFile, $intLine) {
			$strVerbose = AppLanguage::translate('Fatal Error: %s in %s on line %d', $strError, $strFile, $intLine);
			
			if ($this->blnLogError) {	
				$this->logError($strVerbose);
			}
			
			if ($this->blnDebugMode) {
				print $strVerbose;
			} else {
				print AppLanguage::translate('Fatal error - Script terminating');
			}
			
			exit(1);
		}
		
		
		/**
		 * Logs the error to a file.
		 *
		 * @access protected
		 * @param string $strError
		 */
		protected function logError($strError) {
			error_log(date('m.d.y H:i:s') . " {$strError}\n", 3, $this->strLogFile);
		}
		
		
		/*****************************************/
		/**     ERROR GROUPS                    **/
		/*****************************************/
		
		
		/**
		 * Starts a new error group if one doesn't already exist.
		 *
		 * @access public
		 * @param string $strGroup The name of the group to start
		 * @param boolean $blnGlobal Whether to also insert the group error in the global errors array
		 */
		public function startGroup($strGroup, $blnGlobal = true) {
			if (array_key_exists($strGroup, $this->arrGroupStack)) {
				throw new CoreException(AppLanguage::translate('The error group (%s) has already been started', $strGroup));
			}
		
			$this->arrGroupStack[$strGroup] = $blnGlobal;
			if (!isset($this->arrErrorsByGroup[$strGroup])) {
				$this->arrErrorsByGroup[$strGroup] = array();
			}
		}
		
		
		/**
		 * Ends the error group and returns the number of errors.
		 *
		 * @access public
		 * @param string $strGroup The name of the group to end
		 * @return integer The total number of errors in the group
		 */
		public function endGroup($strGroup) {
			if ($strGroup != $this->getGroup()) {
				throw new CoreException(AppLanguage::translate('Incorrect error group ended (%s)', $strGroup));
			}
		
			$intErrorCount = count($this->arrErrorsByGroup[$strGroup]);
			array_pop($this->arrGroupStack);
			
			return $intErrorCount;
		}
		
		
		/**
		 * Returns the name of the current error group.
		 *
		 * @access public
		 * @return string The name of the current error group
		 */
		public function getGroup() {
			if ($arrGroups = array_keys($this->arrGroupStack)) {
				return array_pop($arrGroups);
			}
		}
		
		
		/** 
		 * Gets the group errors for the group passed.
		 *
		 * @access public
		 * @param string $strGroup The group to retrieve the errors for
		 * @return array The errors or false if no errors
		 */
		public function getGroupErrors($strGroup) {
			if (!empty($this->arrErrorsByGroup[$strGroup])) {
				return $this->arrErrorsByGroup[$strGroup];
			}
			return false;
		}
		
		
		/** 
		 * Clears the errors for the group passed.
		 *
		 * @access public
		 * @param string $strGroup The group to clear
		 */
		public function clearGroupErrors($strGroup) {
			if (isset($this->arrErrorsByGroup[$strGroup])) {
				unset($this->arrErrorsByGroup[$strGroup]);
			}
		}
		
		
		/*****************************************/
		/**     GET & SET METHODS               **/
		/*****************************************/
		
		
		/**
		 * Returns all the errors and clears them out.
		 *
		 * @access public
		 * @return array The errors
		 */
		public function flushErrors() {
			$arrErrors = $this->arrErrors;
			$this->arrErrors = array();
			return $arrErrors;
		}
		
		
		/**
		 * Returns the last error that was set.
		 * 
		 * @access public
		 * @return string The error message
		 */
		public function getLastError() {
			$arrErrors = $this->arrErrors;
			return array_pop($arrErrors);
		}
		
		
		/**
		 * Returns the array of all the errors.
		 *
		 * @access public
		 * @return array The errors
		 */
		public function getErrors() {
			return $this->arrErrors;
		}
		
		
		/**
		 * Sets the array of all the errors.
		 *
		 * @access public
		 * @param array $arrErrors The errors to set
		 */
		public function setErrors($arrErrors) {
			$this->arrErrors = $arrErrors;
		}
		
		
		/**
		 * Gets the debug mode.
		 *
		 * @access public
		 * @return boolen If debug mode is on or off
		 */
		public function getDebugMode() {
			return $this->blnDebugMode;
		}
		
		
		/**
		 * Sets the debug mode.
		 *
		 * @access public
		 */
		public function setDebugMode($blnDebugMode) {
			$this->blnDebugMode = $blnDebugMode;
		}
		
		
		/**
		 * Returns true if any errors have occured.
		 *
		 * @access public
		 * @return boolean True if errors
		 */
		public function getErrorFlag() {
			return $this->blnErrorFlag;
		}
		
		
		/*****************************************/
		/**     MAGIC METHODS                   **/
		/*****************************************/
		
		
		/**
		 * Returns the error object info.
		 *
		 * @access public
		 * @return string
		 */
		public function __toString() {
			return 'CoreError: Debug=' . $this->blnDebugMode . ' LogFile=' . $this->strLogFile . ' LogNotice=' . $this->blnLogNotice . ' LogWarning=' . $this->blnLogWarning . ' LogError=' . $this->blnLogError;
		}
	}