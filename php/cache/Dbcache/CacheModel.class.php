<?php
	require_once('php/core/CoreDatabaseModel.class.php');

	/**
	 * CacheModel.class.php
	 * 
	 * Used to add, edit, delete and load the cache records
	 * from the database using the database model. This is
	 * used in conjunction with the DbcacheTiered object.
	 *
	 * CREATE TABLE `cache` (
	 * 	`cacheid` int(10) unsigned NOT NULL AUTO_INCREMENT,
	 * 	`tier` varchar(10) DEFAULT NULL,
	 * 	`cachekey` varchar(255) DEFAULT NULL,
	 * 	`format` enum('raw','serialized') NOT NULL,
	 * 	`data` blob,
	 * 	`created` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
	 * 	`expires` datetime DEFAULT NULL,
	 * 	PRIMARY KEY (`cacheid`),
	 * 	UNIQUE KEY `cachekey` (`tier`,`cachekey`)
	 * ) ENGINE=InnoDB;
	 *
	 * Copyright 2006-2011, Phork Labs. (http://phorklabs.com)
	 *
	 * @author Elenor Collings <elenor@phork.org>
	 * @package phork
	 * @subpackage cache
	 */
	class CacheModel extends CoreDatabaseModel {
		
		protected $strRecordClass = 'CacheRecord';
		
		protected $strTable = 'cache';
		protected $strPrimaryKey = 'cacheid';
		
		protected $arrInsertCols = array('tier', 'cachekey', 'format', 'data', 'expires');
		protected $arrUpdateCols = array('format', 'data', 'expires');
		
		protected $blnAutoSanitize = false;
		protected $arrSaving;
		
		
		/**
		 * Includes the record class, sets up an iterator 
		 * object to hold the records, and sets up an event 
		 * key which is used to register and run events in
		 * the event object. This also sets up the validation
		 * helper.
		 *
		 * @access public
		 * @param array $arrConfig The config vars, including which helpers to use
		 */
		public function __construct($arrConfig = array()) {
			parent::__construct($arrConfig);
			$this->init($arrConfig);
		}
		
		
		/**
		 * Initializes any events and config actions. This 
		 * has been broken out from the constructor so cloned
		 * objects can use it. 
		 *
		 * @access public
		 * @param array $arrConfig The config vars, including which helpers to use
		 */
		public function init($arrConfig) {
			AppEvent::register($this->strEventKey . '.pre-save', array($this, 'setDefaults'));
		
			if (!empty($arrConfig['Validate'])) {
				if (AppLoader::includeExtension('helpers/', 'ModelValidation')) {
					$this->appendHelper('validation', 'ModelValidation', array(
						'Id'			=> array(
							'Property'		=> 'cacheid',
							'Unique'		=> true,
							'Type'			=> 'integer',
							'Error'			=> 'Invalid ID'
						),
						
						'Tier'			=> array(
							'Property'		=> 'tier',
							'Required'		=> true,
							'Type'			=> 'string',
							'Error'			=> 'Invalid tier'
						),
						
						'CacheKey'		=> array(
							'Property'		=> 'cachekey',
							'Required'		=> true,
							'Type'			=> 'string',
							'Error'			=> 'Invalid cache key'
						)
					));
					
					$this->initHelper('validation', array('validateAll'));
				}
			}
		}
		
		
		/*****************************************/
		/**     EVENT CALLBACKS                 **/
		/*****************************************/
		

		/**
		 * Sets any default values before saving including the
		 * created date and access restrictions.
		 *
		 * @access public
		 */
		public function setDefaults() {
			$objDb = AppRegistry::get('Database');
			$this->current()->set('tier', $this->arrConfig['Tier']);
		}

		
		/*****************************************/
		/**     LOAD METHODS                    **/
		/*****************************************/
		
		
		/**
		 * A shortcut function to load the unexpired cache
		 * records by the cache key. This does not clear out
		 * any previously loaded data. That should be done 
		 * explicitly.
		 *
		 * @access public
		 * @param mixed $mxdCacheKey The cache key or array of keys to load
		 * @return boolean True if the query executed successfully
		 */
		public function loadByCacheKey($mxdCacheKey) {
			$arrFunctionArgs = func_get_args();
			$this->setLoading(__FUNCTION__, $arrFunctionArgs);
			
			$blnResult = $this->load(array(
				'Conditions' => array(
					array(
						'Column'	=> 'cachekey',
						'Value'		=> $mxdCacheKey,
						'Operator'	=> is_array($mxdCacheKey) ? 'IN' : '='
					),
					array(
						'Column'	=> 'expires',
						'Value'		=> date('Y-m-d H:i:s'),
						'Operator'	=> '>='
					)
				)
			));
			
			$this->clearLoading();
			return $blnResult;
		}
		
		
		/*****************************************/
		/**     SAVE METHODS                    **/
		/*****************************************/
		
		
		/**
		 * Increments an existing cache record by cache key.
		 * This requires that a record with this cache
		 * key already exists.
		 *
		 * @access public
		 * @param string $strCacheKey The cache key to update
		 * @param integer $intAlterBy The amount to increment by
		 * @param boolean $blnReturnValue Whether to return the new value
		 * @return mixed True on success or the new value
		 */
		public function increment($strCacheKey, $intAlterBy, $blnReturnValue = false) {
			$arrFunctionArgs = func_get_args();
			$this->setSaving(__FUNCTION__, $arrFunctionArgs);
			
			if ($blnReturnValue) {
				$intCounter = $this->getDataForUpdate($strCacheKey);
				$intCounter += $intAlterBy;
			}
			
			$blnResult = $this->save();
			
			$this->clearSaving();
			return $blnResult && $blnReturnValue ? $intCounter : $blnResult;
		}
		
		
		/**
		 * Decrements an existing cache record by cache key.
		 * This requires that a record with this cache
		 * key already exists. If the return new value flag
		 * is set this will lock the row and get the current
		 * value from the write database before updating.
		 *
		 * @access public
		 * @param string $strCacheKey The cache key to update
		 * @param integer $intAlterBy The amount to decrement by
		 * @param boolean $blnReturnValue Whether to return the new value
		 * @return mixed True on success or the new value
		 */
		public function decrement($strCacheKey, $intAlterBy, $blnReturnValue = false) {
			$arrFunctionArgs = func_get_args();
			$this->setSaving(__FUNCTION__, $arrFunctionArgs);
			
			if ($blnReturnValue) {
				$intCounter = $this->getDataForUpdate($strCacheKey);
				$intCounter -= $intAlterBy;
			}
			
			$blnResult = $this->save();
			
			$this->clearSaving();
			return $blnResult && $blnReturnValue ? $intCounter : $blnResult;
		}
		
		
		/**
		 * Adds a new cache record by cache key. This requires
		 * that no record exists with this cache key.
		 *
		 * @access public
		 * @return boolean True on success
		 */
		public function add() {
			$arrFunctionArgs = func_get_args();
			$this->setSaving(__FUNCTION__, $arrFunctionArgs);
			
			$blnResult = $this->save();
			
			$this->clearSaving();
			return $blnResult;
		}
		
		
		/**
		 * Updates an existing cache record by cache key.
		 * This requires that a record with this cache
		 * key already exists.
		 *
		 * @access public
		 * @return boolean True on success
		 */
		public function update() {
			$arrFunctionArgs = func_get_args();
			$this->setSaving(__FUNCTION__, $arrFunctionArgs);
			
			$blnResult = $this->save();
			
			$this->clearSaving();
			return $blnResult;
		}
		
		
		/**
		 * Saves a record to the database. All of the validation
		 * should be handled in an extension using an event.
		 * This has special handling to se the saving function
		 * data.
		 *
		 * @access public
		 * @param boolean $blnForceInsert Whether to force insert a record even though it has an ID
		 * @return boolean True on success
		 */
		public function save($blnForceInsert = false) {
			$arrFunctionArgs = func_get_args();
			$this->setSaving(__FUNCTION__, $arrFunctionArgs);
			
			$blnResult = parent::save();
			
			$this->clearSaving();
			return $blnResult;
		}
		
		
		/**
		 * Returns the query to save the data in the database.
		 * Has special handling to only add a new record, only 
		 * update an existing record, and to increment and 
		 * decrement the data.
		 *
		 * @access protected
		 * @param boolean $blnForceInsert Whether to force insert a record if it has an ID
		 * @return string The save query
		 */
		protected function getSaveQuery($blnForceInsert = false) {
			switch ($this->arrSaving['Function']) {
				case 'increment':
					$strCacheKey = $this->arrSaving['Params'][0];
					$intAlterBy = $this->arrSaving['Params'][1];
					
					$objQuery = AppRegistry::get('Database')->getQuery();
					$objQuery->update()->table($this->strTable)->where('cachekey', $strCacheKey);
					$objQuery->addWhere('format', 'raw');
					$objQuery->addColumn('data', 'data + ' . (int) $intAlterBy, true);
					$strQuery = $objQuery->buildQuery();
					break;
					
				case 'decrement':
					$strCacheKey = $this->arrSaving['Params'][0];
					$intAlterBy = $this->arrSaving['Params'][1];
					
					$objQuery = AppRegistry::get('Database')->getQuery();
					$objQuery->update()->table($this->strTable)->where('cachekey', $strCacheKey);
					$objQuery->addWhere('format', 'raw');
					$objQuery->addColumn('data', 'data - ' . (int) $intAlterBy, true);
					$strQuery = $objQuery->buildQuery();
					break;
					
				case 'add':
					$objQuery = AppRegistry::get('Database')->getQuery();
					$objQuery->insert(true)->table($this->strTable);
					foreach ($this->arrInsertCols as $strColumn) {
						$objQuery->addColumn($strColumn, $this->current()->get($strColumn));
					}
					$strQuery = $objQuery->buildQuery();
					break;
					
				case 'update':
					$objQuery = AppRegistry::get('Database')->getQuery();
					$objQuery->update()->table($this->strTable)->where('cachekey', $this->current()->get('cachekey'));
					foreach ($this->arrUpdateCols as $strColumn) {
						$objQuery->addColumn($strColumn, $this->current()->get($strColumn));
					}
					$strQuery = $objQuery->buildQuery();
					break;
					
				default:
					$objQuery = AppRegistry::get('Database')->getQuery();
					$objQuery->insert()->table($this->strTable);
					foreach ($this->arrInsertCols as $strColumn) {
						$objQuery->addColumn($strColumn, $this->current()->get($strColumn));
					}
					$strQuery = $objQuery->buildInsertOrUpdateQuery();	
					break;
			}
			
			return $strQuery;
		}
		
		
		
		/*****************************************/
		/**     QUERY METHODS                   **/
		/*****************************************/
		
		
		/**
		 * Loads the current data and locks the table until
		 * an update is run.
		 *
		 * @access protected
		 * @param string $strCacheKey The cache key to load the data for
		 * @return mixed The data result
		 */
		protected function getDataForUpdate($strCacheKey) {
			$objDb = AppRegistry::get('Database');
			if ($objDb->initWrite(true)) {
				$strQuery = sprintf("SELECT data FROM %s WHERE tier = '%s' AND cachekey = '%s' FOR UPDATE", $this->strTable, $this->arrConfig['Tier'], $strCacheKey);
				if (($mxdResult = $objDb->read($strQuery)) !== false) {
					$intCounter = $objDb->fetchRowColumn($mxdResult, 'data');
					$objDb->freeResult($mxdResult);
					return $intCounter;	
				}
			}
		}
		
		
		/**
		 * Adds the various parameters to the query object
		 * passed. Used to add where, order by, limit, etc.
		 *
		 * @access protected
		 * @param object $objQuery The query object to add the filters to
		 * @param array $arrFilters The filters to add
		 * @return boolean True if the filters were all valid
		 */
		protected function addQueryFilters($objQuery, $arrFilters) {
			parent::addQueryFilters($objQuery, $arrFilters);
			$objQuery->addWhere('tier', $this->arrConfig['Tier']);
			return true;
		}
				
		
		/*****************************************/
		/**     CALL METHODS                    **/
		/*****************************************/
		
		
		/**
		 * Returns the name of the saving function that was
		 * called as well as the function arguments.
		 *
		 * @access public
		 * @return array The array of saving data
		 */
		public function getSaving() {
			return $this->arrSaving;
		}
		
		
		/**
		 * Sets the name of the saving function that was called
		 * as well as the function arguments.
		 *
		 * @access public
		 */
		public function setSaving($strFunction, $arrFuncArgs) {
			if (!$this->arrSaving) {
				$this->arrSaving = array(
					'Function'	=> $strFunction,
					'Params'	=> $arrFuncArgs
				);
			}
		}		
		
		
		/**
		 * Clears the saving function and args after it has
		 * been called.
		 *
		 * @access public
		 */
		public function clearSaving() {
			$this->arrSaving = null;
		}
	}