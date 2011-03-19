<?php
	require_once('php/core/CoreObject.class.php');
	
	/**
	 * CoreModel.class.php
	 * 
	 * An abstract class to add, edit, delete and retrieve 
	 * one or more records in a data source. This handles
	 * the basic iterator object set up as well as the option
	 * of using a custom record class if any values need 
	 * formatting after being retrieved or before being 
	 * returned.
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
	abstract class CoreModel extends CoreObject {
		
		protected $strRecordClass;
		protected $objRecords;
		protected $intFoundRows;
		
		protected $arrConfig;
		protected $strEventKey;
		protected $arrHelpers;
		protected $arrLoading;
		
		static protected $intCounter = 0;
		
		
		/**
		 * Includes the record class, sets up an iterator 
		 * object to hold the records, and sets up an event 
		 * key which is used to register and run events in
		 * the event object. The event key uses the static
		 * counter property which is shared across all of
		 * the extensions to this class.
		 *
		 * @access public
		 * @param array $arrConfig The config vars, including which helpers to use
		 */
		public function __construct($arrConfig = array()) {
			$this->arrConfig = $arrConfig;
			$this->strEventKey = get_class($this) . ++self::$intCounter;

			if (empty($this->objRecords)) {
				AppLoader::includeExtension('iterators/', 'ObjectIterator');
				$this->objRecords = new ObjectIterator();
			}
			$this->includeRecordClass();
			
			$this->arrHelpers = array();
			$this->arrLoading = array();
		}
		
		
		/**
		 * Destroys all the helper objects.
		 *
		 * @access public 
		 */
		public function __destruct() {
			foreach ($this->arrHelpers as $strName=>$objHelper) {
				$this->destroyHelper($strName);
			}
		}
		
		
		/**
		 * Initializes any helpers and other data that should
		 * be re-initializable by the clone method.
		 *
		 * @access protected
		 * @param array $arrConfig The config vars, including which helpers to use
		 */
		protected function init($arrConfig) {
			return;
		}
		
		
		/**
		 * Clears out the record data and the found row count.
		 *
		 * @access public
		 */
		public function clear() {
			$this->objRecords->clear();
			$this->intFoundRows = null;
		}
		
		
		/**
		 * Imports an array of data into a new object and appends
		 * the object to the list.
		 *
		 * @access public
		 * @param array $arrData The array of data to import
		 * @param boolean Whether to sanitize the data before importing it
		 * @return integer The key of the appended record
		 */
		public function import(array $arrData, $blnSanitize = false) {
			$objRecord = new $this->strRecordClass();
			foreach ($arrData as $strKey=>$mxdVal) {
				$objRecord->set($strKey, $mxdVal);
			}
			
			$intKey = $this->objRecords->append($objRecord);
			if ($blnSanitize) {
				$this->sanitize();
			}
			return $intKey;
		}
		
		
		/**
		 * Sanitizes the current record. This should be used
		 * whenever data from an untrusted source is being 
		 * set.
		 *
		 * @access public
		 */
		public function sanitize() {
			AppLoader::includeUtility('Sanitizer');
			if ($objRecord = $this->current()) {
				foreach ($objRecord as $strKey=>$mxdValue) {
					if (is_string($mxdValue)) {
						if (Sanitizer::sanitizeItem($mxdValue)) {
							$objRecord->set($strKey, $mxdValue);
						}
					}
				}
			}
		}
		
		
		/**
		 * Passes a record through to a callback function. The
		 * record is determined by the iterator method passed (eg.
		 * first, last, each). For example, to send all the records
		 * in the iterator through a processing function process()
		 * while ($mxdResult = $objModel->passthru('process', 'each'))
		 *
		 * @access public
		 * @param mixed $mxdCallback The callback function
		 * @param string $strRecordMethod The iterator method to call to get the record to use
		 * @param array $arrParams Any additional params to send to the callback
		 * @return mixed The callback result 
		 */
		public function passthru($mxdCallback, $strRecordMethod, array $arrParams = array()) {
			if ($mxdResult = $this->objRecords->$strRecordMethod()) {
				if ($strRecordMethod == 'each') {
					$objRecord = $mxdResult[1];
				} else {
					$objRecord = $mxdResult;
				}
				return is_object($objRecord) ? call_user_func_array($mxdCallback, array_merge(array($objRecord), $arrParams)) : null;
			}
		}
		
		
		/**
		 * Includes the record class to use with the model.
		 *
		 * @access public
		 */
		public function includeRecordClass() {
			if ($this->strRecordClass) {
				AppLoader::includeModel($this->strRecordClass);
			} else {
				AppLoader::includeClass('php/core/', $this->strRecordClass = 'CoreRecord');
			}
		}
		
		
		/*****************************************/
		/**     HELPER METHODS                  **/
		/*****************************************/
		
		
		/**
		 * Instantiates and appends a helper object. Helpers 
		 * are used for things like caching, validation, relations,
		 * etc. They register events to run at certain times during
		 * execution.
		 *
		 * @access public
		 * @param string $strName The name that will be used to refer to the helper
		 * @param string $strClass The name of the helper class
		 * @param string $arrConfig The config array to pass to the helper on instantiation
		 */
		public function appendHelper($strName, $strClass, $arrConfig = array()) {
			if (array_key_exists($strName, $this->arrHelpers)) {
				throw new CoreException(AppLanguage::translate('A helper named %s has already been registered', $strName));
			}
			if (!class_exists($strClass)) {
				throw new CoreException(AppLanguage::translate('Invalid helper class (%s)', $strClass));
			}
			
			$objHelper = new $strClass($this->strEventKey, $arrConfig);
			if (!($objHelper instanceof ModelHelper)) {
				throw new CoreException(AppLanguage::translate('The helper object must be an instance of ModelHelper'));
			}
			$this->arrHelpers[$strName] = $objHelper;
		}
		
		
		/**
		 * Initializes a helper object. If a helper relies
		 * on any events this will register them.
		 *
		 * @access public
		 * @param string $strName The name of the helper
		 * @param array $arrEvents The names of the events to register
		 * @param array $arrConfig An array of config vars used upon initialization
		 */
		public function initHelper($strName, $arrEvents, $arrConfig = array()) {
			if (!array_key_exists($strName, $this->arrHelpers)) {
				throw new CoreException(AppLanguage::translate('Invalid helper object (%s)', $strName));
			}
			$this->arrHelpers[$strName]->init($arrEvents, $arrConfig);
		}
		
		
		/**
		 * Returns a helper object.
		 *
		 * @access public
		 * @param string $strName The name of the helper
		 * @return object The helper object
		 */
		public function getHelper($strName) {
			if (array_key_exists($strName, $this->arrHelpers)) {
				return $this->arrHelpers[$strName];
			}
		}
		
		
		/**
		 * Removes a helper. If a helper has registered any
		 * events then this will remove them. This doesn't
		 * destroy the helper object itself, which can be
		 * reinitialized by calling initHelper() again.
		 *
		 * @access public
		 * @param string $strName The name of the helper to remove
		 * @return boolean True on success
		 */
		public function removeHelper($strName) {
			if (array_key_exists($strName, $this->arrHelpers)) {
				$this->arrHelpers[$strName]->destroy();
				return true;
			}
		}
		
		
		/**
		 * Removes a helper and destroys the helper object.
		 *
		 * @access public
		 * @param string $strName The name of the helper to destroy
		 * @return boolean True on success
		 */
		public function destroyHelper($strName) {
			if ($this->removeHelper($strName)) {
				unset($this->arrHelpers[$strName]);
				return true;
			}
		}
		
		
		/**
		 * Clears all the helpers and destroys them if the
		 * flag is set.
		 *
		 * @access public
		 * @param boolean $blnDestroy Whether to destroy the helpers
		 */
		public function clearHelpers($blnDestroy = true) {
			$strClearMethod = ($blnDestroy ? 'destroyHelper' : 'removeHelper');
			foreach ($this->arrHelpers as $strHelper=>$objHelper) {
				$this->$strClearMethod($strHelper);
			}
		}
		
		
		/*****************************************/
		/**     CALL METHODS                    **/
		/*****************************************/
		
		
		/**
		 * Returns the name of the loading function that was
		 * called as well as the function arguments.
		 *
		 * @access public
		 * @return array The array of loading data
		 */
		public function getLoading() {
			return $this->arrLoading;
		}
		
		
		/**
		 * Sets the name of the loading function that was called
		 * as well as the function arguments.
		 *
		 * @access public
		 */
		public function setLoading($strFunction, $arrFuncArgs) {
			if (!$this->arrLoading) {
				$this->arrLoading = array(
					'Function'	=> $strFunction,
					'Params'	=> $arrFuncArgs
				);
			}
		}
		
		
		/**
		 * Clears the loading function and args after it has
		 * been called.
		 *
		 * @access public
		 */
		public function clearLoading() {
			$this->arrLoading = null;
		}
		
		
		/*****************************************/
		/**     GET & SET METHODS               **/
		/*****************************************/
		
		
		/**
		 * Returns the config array so duplicate models can
		 * be created.
		 *
		 * @access public
		 * @return array The config array
		 */
		public function getConfig() {
			return $this->arrConfig;
		}
		
		
		/**
		 * Sets a config option.
		 *
		 * @access public
		 * @param string $strOption The config option to set
		 * @param mixed $mxdValue The config value
		 * @return array The config item
		 */
		public function setConfigOption($strOption, $mxdValue) {
			return $this->arrConfig[$strOption] = $mxdValue;
		}
		
		
		/**
		 * Returns the iterator object containing all the 
		 * records.
		 *
		 * @access public
		 * @return object The iterator object
		 */
		public function getRecords() {
			return $this->objRecords;
		}
		
		
		/**
		 * Sets the iterator object containing all the 
		 * records. Usually this shouldn't be done manually
		 * but this can be used with things like the cache
		 * helper.
		 *
		 * @access public
		 * @param object $objRecords The iterator object
		 * @return object The iterator object
		 */
		public function setRecords($objRecords) {
			return $this->objRecords = $objRecords;
		}
		
		
		/**
		 * Returns the number of found rows. This is the total
		 * amount of matches from a load even if only a subset
		 * has been retrieved.
		 *
		 * @access public
		 * @return integer The number of found rows
		 */
		public function getFoundRows() {
			return $this->intFoundRows;
		}
		
		
		/**
		 * Sets the number of found rows. Usually this shouldn't
		 * be done manually but this can be used with things 
		 * like the cache helper.
		 *
		 * @access public
		 * @param integer $intFoundRows The number of found rows
		 * @return integer The number of found rows
		 */
		public function setFoundRows($intFoundRows) {
			return $this->intFoundRows = $intFoundRows;
		}
		
		
		/**
		 * Returns the data schema (eg. the database columns
		 * and their types).
		 *
		 * @access public
		 * @return array The data schema
		 */
		public function getSchema() {
			throw new CoreException(AppLanguage::translate('The %s method must be defined in the extension to the %s class', __METHOD__, __CLASS__));	
		}
		
		
		/*****************************************/
		/**     MAGIC METHODS                   **/
		/*****************************************/
		
		
		/**
		 * Method called when an unknown method is called.
		 * Currently used as a pass through method to the
		 * records iterator.
		 *
		 * @access public
		 * @param string $strMethodName The method called
		 * @param array $arrParameters The parameters passed to the method
		 * @return string The value of the element
		 */
		public function __call($strMethodName, $arrParameters) {
			return call_user_func_array(array($this->objRecords, $strMethodName), $arrParameters);
		}
		
		
		/**
		 * Method called when the object is cloned. Resets
		 * the event key and helpers and then calls init()
		 * to re-initialize them with a new event key.
		 *
		 * @access public
		 */
		public function __clone() {
			$this->objRecords = new ObjectIterator();
			$this->strEventKey = get_class($this) . ++self::$intCounter;
			$this->arrLoading = array();
			$this->arrHelpers = array();
			
			$this->init($this->arrConfig);
		}
	}