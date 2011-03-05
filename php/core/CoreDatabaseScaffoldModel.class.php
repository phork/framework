<?php
	require_once('php/core/CoreDatabaseModel.class.php');

	/**
	 * CoreDatabaseScaffoldModel.class.php
	 * 
	 * Used to add, edit, delete and load the user records
	 * from the database using the database model. This sets
	 * up a dynamic data model based on a table definition.
	 *
	 * Copyright 2006-2010, Phork Labs. (http://phorklabs.com)
	 *
	 * Licensed under The MIT License
	 * Redistributions of files must retain the above copyright notice.
	 *
	 * @author Elenor Collings <elenor@phork.org>
	 * @license http://www.opensource.org/licenses/mit-license.php The MIT License
	 * @package phork
	 * @subpackage models
	 */
	class CoreDatabaseScaffoldModel extends CoreDatabaseModel {
	
		protected $arrColumns;
		
		
		/**
		 * Loads the table definition and sets up the
		 * object to insert and update all the columns.
		 *
		 * @access public
		 * @param array $arrConfig The config vars, including the table name
		 */
		public function __construct($arrConfig = array()) {
			if (empty($arrConfig['Table'])) {
				throw new CoreException(AppLanguage::translate('Missing scaffolding table'));
			}
			
			$objDb = AppRegistry::get('Database');
			if ($arrCols = $objDb->getTableColumns($arrConfig['Table'])) {
				$this->strTable = $arrConfig['Table'];
				$this->arrColumns = array();
				$this->arrInsertCols = array();
				$this->arrUpdateCols = array();
				
				foreach ($arrCols as $arrCol) {
					$this->arrColumns[$arrCol['Name']] = $arrCol;
				
					$this->arrInsertCols[] = $arrCol['Name'];
					$this->arrUpdateCols[] = $arrCol['Name'];
					
					if ($arrCol['Primary']) {
						$this->strPrimaryKey = $arrCol['Name'];
					}
				}
			} else {
				throw new CoreException(AppLanguage::translate('Unable to scaffold the %s table', $arrConfig['Table']));
			}
			parent::__construct($arrConfig);	
		}
		
		
		/*****************************************/
		/**     SAVE METHODS                    **/
		/*****************************************/
		
		
		/**
		 * Returns the query to save the data in the database.
		 *
		 * @access protected
		 * @param boolean $blnForceInsert Whether to force insert a record if it has an ID
		 * @return string The save query
		 */
		protected function getSaveQuery($blnForceInsert = false) {
			$objQuery = AppRegistry::get('Database')->getQuery();
			
			if (!$blnForceInsert && ($intId = $this->current()->get($this->strPrimaryKey))) {
				$objQuery->update()->table($this->strTable)->where($this->strPrimaryKey, $intId);
				$arrSaveCols = $this->arrUpdateCols;
			} else {
				$objQuery->insert()->into($this->strTable);
				$arrSaveCols = $this->arrInsertCols;
			}
			
			foreach ($arrSaveCols as $strColumn) {
				if ($this->arrColumns[$strColumn]['Type'] == 'multiselect') {
					$objQuery->addColumn($strColumn, implode(',', $this->current()->get($strColumn)));
				} else {
					$objQuery->addColumn($strColumn, $this->current()->get($strColumn));
				}
			}
			return $objQuery->buildQuery();
		}
	}