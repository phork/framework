<?php
	require_once('php/core/CoreModel.class.php');
	
	/**
	 * CoreDatabaseModel.class.php
	 * 
	 * An abstract class to add, edit, delete and retrieve 
	 * one or more records in the database. Each class must
	 * have a database table defined as well as the name of
	 * the primary key column and the names of each column
	 * to insert and update. Optionally a custom record class
	 * can be defined if any values need formatting after
	 * being retrieved from the database or before being 
	 * returned.
	 *
	 * <code>
	 * $objBlog = new BlogModel();
	 * if ($objBlog->load()) {
	 *		while (list(, $objRecord) = $objBlog->each()) {
	 * 			...
	 * 		}			
	 * }
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
	 * @subpackage core
	 * @abstract
	 */
	abstract class CoreDatabaseModel extends CoreModel {
		
		protected $arrConfig;
		
		protected $strTable;
		protected $strPrimaryKey;
		
		protected $arrInsertCols;
		protected $arrUpdateCols;
		
		protected $blnAutoSanitize = true;
		
		const ID_PROPERTY = '__id';
		
		
		/**
		 * The constructor validates the data model and
		 * initializes the object.
		 *
		 * @access public
 		 */
		public function __construct($arrConfig = array()) {
			$this->arrConfig = $arrConfig;
		
			if (empty($this->strTable)) {
				throw new CoreException(AppLanguage::translate('All CoreDatabaseModel objects must have a database table defined'));
			}
			if (empty($this->strPrimaryKey)) {
				throw new CoreException(AppLanguage::translate('All CoreDatabaseModel objects must have a primary key column defined'));
			}
			if (!is_array($this->arrInsertCols)) {
				throw new CoreException(AppLanguage::translate('All CoreDatabaseModel objects must have an array of insertable columns, even if the array is empty'));
			}
			if (!is_array($this->arrUpdateCols)) {
				throw new CoreException(AppLanguage::translate('All CoreDatabaseModel objects must have an array of updateable columns, even if the array is empty'));
			}
					
			parent::__construct($arrConfig);
			
			if ($this->blnAutoSanitize && empty($arrConfig['SkipSanitize'])) {
				AppEvent::register($this->strEventKey . '.pre-save', array($this, 'sanitize'));
			}
		}
		
		
		/*****************************************/
		/**     LOAD METHODS                    **/
		/*****************************************/
		
		
		/**
		 * Loads the record(s) from the database by the params 
		 * passed. This does not clear out any previously loaded
		 * data. That should be done explicitly.
		 *
		 * The events can pass back two special variables.
		 * The $blnSkipLoad flag will bypass the actual loading
		 * process. The $blnResult flag when used in conjunction
		 * with the skip load flag will explicitly set the
		 * success or failure flag.
		 *
		 * @access public
		 * @param array $arrFilters The filters to load by
		 * @param boolean $blnCalcFoundRows Whether to calculate the total number of matching rows
		 * @return boolean True if the query executed successfully
		 */
		public function load($arrFilters = array(), $blnCalcFoundRows = false) {
			if ($blnClearLoading = !$this->arrLoading) {
				$arrFunctionArgs = func_get_args();
				$this->setLoading(__FUNCTION__, $arrFunctionArgs);
			}
			
			if (AppEvent::exists($strEvent = $this->strEventKey . '.pre-load')) {
				extract(AppEvent::run($strEvent, array(
					'objModel'		=> $this,
					'strFunction'	=> $this->arrLoading['Function'],
					'arrParams'		=> $this->arrLoading['Params']
				)));
			}
				
			if (empty($blnSkipLoad)) {
				$objDb = AppRegistry::get('Database');
				if ($objDb->initRead(true)) {
					if ($strLoadQuery = $this->getLoadQuery($arrFilters, $blnCalcFoundRows)) {
						if (($mxdResult = $objDb->read($strLoadQuery)) !== false) {
							while ($objRecord = $objDb->fetchRowObject($mxdResult, $this->strRecordClass)) {
								$objRecord->set(self::ID_PROPERTY, $objRecord->get($this->strPrimaryKey));
								$this->objRecords->append($objRecord);
							}
							$objDb->freeResult($mxdResult);
							
							if ($blnCalcFoundRows) {
								$strCountQuery = $objDb->getQuery()->buildCountQuery('count');
								if (($mxdResult = $objDb->read($strCountQuery)) !== false) {
									$this->intFoundRows = $objDb->fetchRowColumn($mxdResult, 'count');
									$objDb->freeResult($mxdResult);	
								}
							}
							
							$blnResult = true;
						}
					} else {
						throw new CoreException(AppLanguage::translate('Missing load query'));
					}
				}
			}
			
			if (AppEvent::exists($strEvent = $this->strEventKey . '.post-load')) {
				extract(AppEvent::run($strEvent, array(
					'objModel'		=> $this,
					'strFunction'	=> $this->arrLoading['Function'],
					'arrParams'		=> $this->arrLoading['Params'],
					'blnSuccess'	=> !empty($blnResult)
				)));
			}
			
			if ($blnClearLoading) {
				$this->clearLoading();
			}
			return !empty($blnResult);
		}
		
		
		/**
		 * A shortcut function to load a record or an array
		 * of records by the ID or array of IDs passed.
		 * This does not clear out any previously loaded data.
		 * That should be done explicitly.
		 *
		 * @access public
		 * @param mixed $mxdId The ID or array of IDs to load by
		 * @param array $arrFilters Any additional filters as well as the limits
		 * @param boolean $blnCalcFoundRows Whether to calculate the total number of matching rows
		 * @return boolean True if the query executed successfully
		 */
		public function loadById($mxdId, $arrFilters = array(), $blnCalcFoundRows = false) {
			$arrFunctionArgs = func_get_args();
			$this->setLoading(__FUNCTION__, $arrFunctionArgs);
			
			if (!array_key_exists('Conditions', $arrFilters)) {
				$arrFilters['Conditions'] = array();
			}
			$arrFilters['Conditions'][] = array(
				'Column'	=> $this->strPrimaryKey,
				'Value' 	=> $mxdId,
				'Operator'	=> is_array($mxdId) ? 'IN' : '='
			);
			
			$blnResult = $this->load($arrFilters, $blnCalcFoundRows);
			
			$this->clearLoading();
			return $blnResult;
		}
		
		
		/**
		 * Returns the query to load a record from the database.
		 *
		 * @access protected
		 * @param array $arrFilters The filters to load by
		 * @param boolean $blnCalcFoundRows Whether to calculate the total number of matching rows
		 * @return array The load query
		 */
		protected function getLoadQuery($arrFilters, $blnCalcFoundRows) {
			$objQuery = AppRegistry::get('Database')->getQuery()->select($blnCalcFoundRows)->from($this->strTable);
			if ($this->addQueryFilters($objQuery, $arrFilters)) {
				return $objQuery->buildQuery();
			}
		}
		
		
		/*****************************************/
		/**     SAVE METHODS                    **/
		/*****************************************/
		
		
		/**
		 * Saves a record to the database. All of the validation
		 * should be handled in an extension using an event.
		 *
		 * This saves the object that the iterator pointer is
		 * currently on. If a record has an ID it'll be updated
		 * unless the force insert flag is set to true. If the
		 * flag is true or it has no ID it'll be inserted.
		 *
		 * The events can pass back two special variables.
		 * The $blnSkipSave flag will bypass the actual saving
		 * process. The $blnResult flag when used in conjunction
		 * with the skip save flag will explicitly set the
		 * success or failure flag.
		 *
		 * @access public
		 * @param boolean $blnForceInsert Whether to force insert a record even though it has an ID
		 * @return boolean True on success
		 */
		public function save($blnForceInsert = false) {
			if (!($objRecord = $this->current())) {
				trigger_error(AppLanguage::translate('No record set to save'));
				return false;
			}
			
			if (($intId = $this->current()->get(self::ID_PROPERTY)) && !$objRecord->get($this->strPrimaryKey)) {
				$objRecord->set($this->strPrimaryKey, $intId);
			} else if (($intId = $this->current()->get($this->strPrimaryKey)) && !$objRecord->get(self::ID_PROPERTY)) {
				$objRecord->set(self::ID_PROPERTY, $intId);
			}
			$blnNewRecord = $blnForceInsert || !$objRecord->get(self::ID_PROPERTY);
			
			if (AppEvent::exists($strEvent = $this->strEventKey . '.pre-save')) {
				extract(AppEvent::run($strEvent, array(
					'objModel'			=> $this,
					'strFunction'		=> __FUNCTION__,
					'blnNewRecord'		=> $blnNewRecord,
					'blnForceInsert'	=> $blnForceInsert
				)));
			}
			
			if (empty($blnSkipSave)) {
				$objDb = AppRegistry::get('Database');
				if ($objDb->initWrite(true)) {
					if ($strSaveQuery = $this->getSaveQuery($blnForceInsert)) {
						$mxdResult = $objDb->write($strSaveQuery);
								
						if ($blnResult = ($mxdResult != false)) {
							if ($blnNewRecord) {
								$intInsertedId = $objDb->getInsertedId();
								$objRecord->set(self::ID_PROPERTY, $intInsertedId);
								$objRecord->set($this->strPrimaryKey, $intInsertedId);
							}
						}
					} else {
						throw new CoreException(AppLanguage::translate('Missing save query'));
					}
				}
			}
			
			if (AppEvent::exists($strEvent = $this->strEventKey . '.post-save')) {
				extract(AppEvent::run($strEvent, array(
					'objModel'			=> $this,
					'strFunction'		=> __FUNCTION__,
					'blnNewRecord'		=> $blnNewRecord,
					'blnForceInsert'	=> $blnForceInsert,
					'blnSuccess'		=> !empty($blnResult)
				)));
			}
				
			return !empty($blnResult);
		}
		
		
		/**
		 * Returns the query to save the data in the database.
		 *
		 * @access protected
		 * @param boolean $blnForceInsert Whether to force insert a record if it has an ID
		 * @return string The save query
		 */
		protected function getSaveQuery($blnForceInsert = false) {
			$objQuery = AppRegistry::get('Database')->getQuery();
			
			if (($intId = $this->current()->get(self::ID_PROPERTY)) && !$blnForceInsert) {
				$objQuery->update()->table($this->strTable)->where($this->strPrimaryKey, $intId);
				$arrSaveCols = $this->arrUpdateCols;
			} else {
				$objQuery->insert()->into($this->strTable);
				$arrSaveCols = $this->arrInsertCols;
				
				if ($intId && $blnForceInsert) {
					$objQuery->addColumn($this->strPrimaryKey, $intId);
				}
			}
			
			foreach ($arrSaveCols as $strColumn) {
				$objQuery->addColumn($strColumn, $this->current()->get($strColumn));
			}
			
			return $objQuery->buildQuery();
		}
		
		
		/**
		 * Returns the query to insert all the loaded records
		 * in the database. If any record has an ID it's ignored.
		 *
		 * @access protected
		 * @param boolean $blnForceInsert Whether to force insert a record if it has an ID
		 * @return string The insert query
		 */
		protected function getInsertAllQuery($blnForceInsert = false) {
			$intCursor = $this->key();
			$this->rewind();
		
			$objQuery = AppRegistry::get('Database')->getQuery();
			$objQuery->insert()->table($this->strTable);
			
			foreach ($this->arrInsertCols as $strColumn) {
				$objQuery->addColumn($strColumn);
			}
			
			$arrQuery = array();
			while (list($intKey, $objRecord) = $this->each()) {
				if ($blnForceInsert || !$objRecord->get(self::ID_PROPERTY)) {
					$objInsertQuery = clone $objQuery;
					$objInsertQuery->initInsertQuery();
					
					foreach ($this->arrInsertCols as $strColumn) {
						$objInsertQuery->addColumn($strColumn, $objRecord->get($strColumn));
					}
					
					$arrQuery[] = $objInsertQuery;
				}
			}
			
			$this->seek($intCursor);
			return $objQuery->buildInsertMultiQuery($arrQuery);
		}
		
		
		/*****************************************/
		/**     DELETE METHODS                  **/
		/*****************************************/
		
		
		/**
		 * Deletes the record(s) from the database by the filters
		 * passed.
		 *
		 * The events can pass back two special variables.
		 * The $blnSkipDelete flag will bypass the actual delete
		 * process. The $blnResult flag when used in conjunction
		 * with the skip delete flag will explicitly set the
		 * success or failure flag.
		 *
		 * @access public
		 * @param array $arrFilters The filters to delete by
		 * @return boolean True on success
		 */
		public function delete(array $arrFilters) {
			if (AppEvent::exists($strEvent = $this->strEventKey . '.pre-delete')) {
				extract(AppEvent::run($strEvent, array(
					'objModel'		=> $this,
					'strFunction'	=> __FUNCTION__,
					'arrFilters'	=> $arrFilters
				)));
			}
			
			if (empty($blnSkipDelete)) {
				$objDb = AppRegistry::get('Database');
				if ($objDb->initWrite(true)) {
					if ($strDeleteQuery = $this->getDeleteQuery($arrFilters)) { 
						$blnResult = $objDb->write($strDeleteQuery);
					} else {
						throw new CoreException(AppLanguage::translate('Missing delete query'));
					}
				}
			}
			
			if (AppEvent::exists($strEvent = $this->strEventKey . '.post-delete')) {
				extract(AppEvent::run($strEvent, array(
					'objModel'		=> $this,
					'strFunction'	=> __FUNCTION__,
					'arrFilters'	=> $arrFilters,
					'blnSuccess'	=> $blnResult
				)));
			}
			
			return !empty($blnResult);
		}
		
		
		/**
		 * A shortcut function to delete a record by its ID.
		 *
		 * @access public
		 * @param mixed $mxdId The ID or array of IDs to delete by
		 * @return boolean True on success
		 */
		public function deleteById($mxdId) {
			return $this->delete(array(
				'Conditions' => array(
					array(
						'Column' 	=> $this->strPrimaryKey,
						'Value'  	=> $mxdId,
						'Operator'	=> is_array($mxdId) ? 'IN' : '='
					)
				)			
			));
		}
		
		
		/**
		 * Returns the query to delete the record(s) from the
		 * database.
		 *
		 * @access protected
		 * @param array $arrFilters The filters to delete by
		 * @return array The delete query
		 */
		protected function getDeleteQuery($arrFilters) {
			$objQuery = AppRegistry::get('Database')->getQuery()->delete()->from($this->strTable);
			if ($this->addQueryFilters($objQuery, $arrFilters)) {
				return $objQuery->buildQuery();
			}
		}
		
		
		/*****************************************/
		/**     DESTROY METHODS                 **/
		/*****************************************/
		
		
		/**
		 * Deletes a record from both the database and the
		 * records list.
		 *
		 * This deletes the object that the iterator pointer is
		 * currently on.		
		 *
		 * @access public
		 * @return boolean True on success
		 */
		public function destroy() {
			if (!$this->current() || !($intId = $this->current()->get(self::ID_PROPERTY))) {
				trigger_error(AppLanguage::translate('No record set to destroy'));
				return false;
			}
			
			if (AppEvent::exists($strEvent = $this->strEventKey . '.pre-destroy')) {
				extract(AppEvent::run($strEvent, array(
					'objModel'		=> $this,
					'strFunction'	=> __FUNCTION__
				)));
			}
			
			if (empty($blnSkipDestroy)) {
				$blnResult = $this->deleteById($intId);
			}
			
			if (AppEvent::exists($strEvent = $this->strEventKey . '.post-destroy')) {
				extract(AppEvent::run($strEvent, array(
					'objModel'		=> $this,
					'strFunction'	=> __FUNCTION__,
					'blnSuccess'	=> $blnResult
				)));
			}
			
			$this->remove();		
			return $blnResult;
		}
		
		
		/*****************************************/
		/**     QUERY METHODS                   **/
		/*****************************************/
		
		
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
			if (!empty($arrFilters['Conditions'])) {
				foreach ($arrFilters['Conditions'] as $arrWhere) {
					array_key_exists('Operator', $arrWhere) || $arrWhere['Operator'] = '=';
					array_key_exists('NoQuote', $arrWhere) || $arrWhere['NoQuote'] = false;
					
					if ($arrWhere['Column'] == self::ID_PROPERTY) {
						$arrWhere['Column'] = $this->strPrimaryKey;
					}
					$strMethod = empty($arrWhere['Having']) ? 'addWhere' : 'addHaving';
					$objQuery->$strMethod($arrWhere['Column'], array_key_exists('Value', $arrWhere) ? $arrWhere['Value'] : null, $arrWhere['Operator'], $arrWhere['NoQuote']);
				}
			}
			
			if (!empty($arrFilters['GroupBy'])) {
				foreach ($arrFilters['GroupBy'] as $arrGroupBy) {
					if ($arrGroupBy['Column'] == self::ID_PROPERTY) {
						$arrGroupBy['Column'] = $this->strPrimaryKey;
					}
					$objQuery->addGroupBy($arrGroupBy['Column']);
				}
			}
			
			if (!empty($arrFilters['Order'])) {
				foreach ($arrFilters['Order'] as $arrOrder) {
					array_key_exists('Sort', $arrOrder) || $arrOrder['Sort'] = 'ASC';
					
					if ($arrOrder['Column'] == self::ID_PROPERTY) {
						$arrOrder['Column'] = $this->strPrimaryKey;
					}
					$objQuery->addOrderBy($arrOrder['Column'], $arrOrder['Sort']);
				}
			}
			
			if (!empty($arrFilters['Random'])) {
				$objQuery->addOrderRandom();
			}
			
			if (!empty($arrFilters['Limit'])) {
				array_key_exists('Offset', $arrFilters) || $arrFilters['Offset'] = 0;
				$objQuery->addLimit($arrFilters['Limit'], $arrFilters['Offset']);
			}
				
			return true;
		}
		
		
		/*****************************************/
		/**     GET & SET METHODS               **/
		/*****************************************/
		
		
		/**
		 * Returns the data schema including the database
		 * columns and their types.
		 *
		 * @access public
		 * @return array The data schema
		 */
		public function getSchema() {
			$objDb = AppRegistry::get('Database');
			return $objDb->getTableColumns($this->strTable);
		}
		
		
		/**
		 * Returns the name of the database table.
		 *
		 * @access public
		 * @return string The table name
		 */
		public function getTable() {
			return $this->strTable;
		}
		
		
		/**
		 * Sets the name of the database table.
		 *
		 * @access public
		 * @param string $strTable The table name
		 */
		public function setTable($strTable) {
			return $this->strTable = $strTable;
		}
	}