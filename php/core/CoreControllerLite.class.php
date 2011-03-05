<?php
	require_once('php/core/CoreObject.class.php');
	require_once('php/core/interfaces/Controller.interface.php');
	
	/**
	 * CoreControllerLite.class.php
	 * 
	 * The controller class is used to pull together all
	 * the data and the templates to build the page. This
	 * works in conjunction with the bootstrap and the 
	 * display class.
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
	 */
	class CoreControllerLite extends CoreObject implements Controller {
	
		protected $strMethodPrefix = 'display';
		
		protected $strContent;
		protected $arrPageVars;
		protected $strTemplateDir;
		
		
		/**
		 * Sets up the template path.
		 *
		 * @access public
		 */
		public function __construct() {
			$this->arrPageVars = array();
			$this->strTemplateDir = AppConfig::get('TemplateDir');
		}
		
		
		/**
		 * This is called from the bootstrap and it handles
		 * any necessary processing before calling the display
		 * method. Generally this sets up the type of content
		 * to display based on the URL.
		 *
		 * @access public
		 */
		public function run() {
			$this->strContent = AppRegistry::get('Url')->getSegment(1);
			$this->display();
		}
		
		
		/**
		 * A stripped down fatal error function that just
		 * throws an exception. If the first URL segment
		 * is a valid error code this will become that
		 * error page (eg. /404/). If verbose errors are
		 * turned on then the exception will be output
		 * otherwise it should be handled gracefully.
		 *
		 * @access public
		 * @param integer $intErrorCode The HTTP status code
		 * @param string $strException The exception to throw
		 */
		public function error($intErrorCode = null, $strException = null) {
			if (!$strException) {
				$strException = AppRegistry::get('Error')->getLastError();
			}
			
			if (!($arrStatusCodes = AppConfig::get('StatusCodes', false))) {
				if ($arrHttpConfig = AppConfig::load('http')) {
					$arrStatusCodes = $arrHttpConfig['StatusCodes'];
				}
			}
				
			if (preg_match('/[0-9]{3}/', $strSection = AppRegistry::get('Url')->getSegment(0))) {
				if (!empty($arrStatusCodes[$strSection])) {
					$intErrorCode = $strSection;
				}
			}
		 
			if ($intErrorCode) {
				if (!$strException) {
					$strException = $arrStatusCodes[$intErrorCode];
				}
				AppDisplay::getInstance()->setStatusCode($intErrorCode);
				AppDisplay::getInstance()->sendHeaders();
			}
			
			throw new CoreException($strException);
		}
		
		
		/**
		 * Assigns a variable that will be accessible from all
		 * the templates.
		 *
		 * @access public
		 * @param string $strName The variable name
		 * @param mixed $mxdValue The variable value
		 */
		public function assignPageVar($strName, $mxdValue) {
			$this->arrPageVars[$strName] = $mxdValue;
		}
		
		
		/**
		 * Returns a variable that will be accessible from all
		 * the templates.
		 *
		 * @access public
		 * @param string $strName The variable name
		 * @return mixed The variable value
		 */
		public function getPageVar($strName) {
			if (array_key_exists($strName, $this->arrPageVars)) {
				return $this->arrPageVars[$strName];
			}
		}
		
		
		/*****************************************/
		/**     FILE METHODS                    **/
		/*****************************************/
		
		
		/**
		 * Returns the template path for the page templates.
		 *
		 * @access protected
		 * @param string $strTemplate The name of the template
		 * @return string The path to the template
		 */
		protected function getTemplatePath($strTemplate) {
			return $this->strTemplateDir . $strTemplate . '.phtml';
		}
		
		
		/**
		 * Makes sure that the file actually exists. If the strict
		 * path is set then it makes sure that the file exists
		 * within that directory.
		 *
		 * @access protected
		 * @param string $strFilePath The complete file path to validate
		 * @param string $strStrictPath The file must be within this directory if defined
		 * @return boolean True if valid
		 */
		protected function validateFile($strFilePath, $strStrictPath = null) {
			$blnResult = false;
			
			if ($strStrictPath) {
				$strStrictPath = realpath($strStrictPath);
				if (substr(realpath($strFilePath), 0, strlen($strStrictPath)) != $strStrictPath) {
					throw new CoreException(AppLanguage::translate('Invalid template path'));
				}
			}
			
			if (!($blnResult = (file_exists($strFilePath) && is_readable($strFilePath)))) {
				throw new CoreException(AppLanguage::translate('Invalid template path'));
			}
			
			return $blnResult;
		}
		
		
		/**
		 * Includes a template by its path and makes sure that 
		 * the template path actually exists within the template 
		 * directory. Including a template is different than
		 * appending it to the display object because it is
		 * output (or buffered for output) right away. The
		 * purpose of this method is to be called from within
		 * a template to allow it to include another template.
		 *
		 * @access public
		 * @param string $strFullPath The full path to the custom template
		 * @param array $arrLocalVars An associative array of variables that should be available to just this file
		 */
		public function includeTemplateFile($strFullPath, $arrLocalVars = array()) {
			if ($this->validateFile($strFullPath, $this->strTemplateDir)) {
				if (!empty($this->arrPageVars)) {
					extract($this->arrPageVars);
				}
				if (!empty($arrLocalVars)) {
					extract($arrLocalVars);
				}
				include($strFullPath);
			}
		}
		
		
		/*****************************************/
		/**     DISPLAY METHODS                 **/
		/*****************************************/
		
		
		/**
		 * Pulls all the templates together and builds the
		 * page. Generally this should be called from run().
		 *
		 * The page isn't output until the display object
		 * has been destroyed or its output() method has
		 * been explicitly called. To build a page means
		 * to merge all the node content into a single string.
		 *
		 * @access protected
		 */
		protected function display() {
			if (method_exists($this, $strMethod = $this->strMethodPrefix . ($this->strContent ? $this->strContent : 'Index'))) {
				$this->$strMethod();
			} else {
				$this->error(404);
			}
		}
		
		
		/**
		 * Displays the index template. This should also
		 * contain any processing necessary to get the index
		 * content.
		 *
		 * @access protected
		 */
		protected function displayIndex() {
			if ($this->validateFile($strTemplate = $this->getTemplatePath('index'))) {
				AppDisplay::getInstance()->appendTemplate('content', $strTemplate, $this->arrPageVars);
			}
		}
	}