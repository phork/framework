<?php
	require_once('php/database/MySql/MySqlQuery.class.php');
	require_once('php/database/DatabaseAdaptor.class.php');
	
	/**
	 * MySql.class.php
	 *
	 * An adaptor for the MySQL database functions.
	 *
	 * Copyright 2006-2011, Phork Labs. (http://phorklabs.com)
	 *
	 * Licensed under The MIT License
	 * Redistributions of files must retain the above copyright notice.
	 *
	 * @author Elenor Collings <elenor@phork.org>
	 * @license http://www.opensource.org/licenses/mit-license.php The MIT License
	 * @package phork
	 * @subpackage database
	 */
	class MySql extends DatabaseAdaptor {
		
		/**
		 * Determines if the MySQL module is installed.
		 *
		 * @access public
		 * @return boolean True if available
		 * @static
		 */
		static public function isAvailable() {
			return function_exists('mysql_connect');
		}
		
		
		/*****************************************/
		/**     CONNECTION METHODS              **/
		/*****************************************/
		
		
		/**
		 * Connects to the database and selects the database
		 * defined in the resource object. If a connection
		 * has already been established this does nothing.
		 *
		 * @access public
		 * @return boolean True on success
		 */
		public function connect() {
			if (!$this->objActive) {
				throw new CoreException(AppLanguage::translate('Invalid database resource object'));
			}
			
			if (!$this->objActive->blnConnected) {
				if (empty($this->objActive->strDatabase)) {
					throw new CoreException(AppLanguage::translate('No database selected'));
				}
				
				$strFunction = $this->objActive->blnPersistent ? 'mysql_pconnect' : 'mysql_connect';
				if ($this->objActive->rscDb = $strFunction($this->objActive->strHost . ($this->objActive->intPort ? ':' . $this->objActive->intPort : ''), $this->objActive->strUser, $this->objActive->strPassword)) {
					if ($this->selectDatabase()) {
						$this->objActive->blnConnected = true;
					}
					$this->groupConnections();
				} else {
					trigger_error(AppLanguage::translate('Could not connect to the %s database', $this->strActive));
				}
			}
		
			return $this->objActive->blnConnected;
		}
		
		
		/**
		 * Closes the database connection and sets the connected
		 * flag to false.
		 *
		 * @access public
		 */
		public function close() {
			if ($this->objActive) {
				if ($this->objActive->blnConnected) {
					if (@mysql_close($this->objActive->rscDb)) {
						$this->objActive->rscDb = null;
						$this->objActive->blnConnected = false;
						
						$this->groupConnections();
					}
				}
			}
		}
		
		
		/**
		 * Selects the database based on the database name in
		 * the resource object.
		 *
		 * @access public
		 * @return boolean True on success
		 */
		public function selectDatabase() {
			if ($this->objActive) {
				if (mysql_select_db($this->objActive->strDatabase, $this->objActive->rscDb)) {
					return true;
				} else {
					trigger_error(AppLanguage::translate('Could not select the database: %s', $this->getError()));
				}
			}
		}
		
		
		/*****************************************/
		/**     QUERY METHODS                   **/
		/*****************************************/
		
		
		/**
		 * Escapes special characters in the string to prevent
		 * SQL injection.
		 *
		 * @access public
		 * @param string $strString The string to escape
		 * @return string The escaped string
		 */
		public function escapeString($strString) {
			if ($this->objActive && ($this->objActive->blnConnected || $this->connect())) {
				$strFunction = 'mysql_real_escape_string';
			} else {
				$strFunction = 'mysql_escape_string';
			}
			
			return $strFunction($strString);
		}
		
		
		/**
		 * Performs a query.
		 *
		 * @access protected
		 * @param string $strQuery The query to execute
		 * @return object The result object for SELECT, SHOW, DESCRIBE or EXPLAIN
		 *         boolean True on success for all other query types
		 */
		protected function query($strQuery) {
			CoreDebug::debug($this, "Query (" . $this->strActive . ' @ ' . $this->objActive->strDatabase . "): {$strQuery}");
			
			if ($this->checkConnection()) {
				$mxdResult = mysql_query($strQuery, $this->objActive->rscDb);		
				if (!$mxdResult) {
					throw new CoreException(AppLanguage::translate('Could not perform query: %s', $this->getError()));
				}
			} else {
				trigger_error(AppLanguage::translate('No database connection'));
			}
			
			return (isset($mxdResult) ? $mxdResult : false);
		}
		
		
		/*****************************************/
		/**     RESULT METHODS                  **/
		/*****************************************/
		
		
		/**
		 * Gets the last inserted ID.
		 *
		 * @access public
		 * @return integer The inserted ID
		 */
		public function getInsertedId() {
			return mysql_insert_id($this->objActive->rscDb);
		}
		
		
		/**
		 * Gets the number of affected rows.
		 *
		 * @access public
		 * @return integer The number of rows
		 */
		public function getAffectedRows() {
			return mysql_affected_rows($this->objActive->rscDb);
		}
		
		
		/**
		 * Gets the number of rows in the result.
		 *
		 * @access public
		 * @param object $objResult The result object
		 * @return integer The number of rows
		 */
		public function getNumRows($objResult) {
			return mysql_num_rows($objResult);
		}
		
		
		/**
		 * Fetches the row as a numeric array.
		 *
		 * @access public
		 * @param object $objResult The result object
		 * @return array The result array, or null
		 */
		public function fetchRow($objResult) {
			return mysql_fetch_row($objResult);
		}
		
		
		/**
		 * Fetches the row as an associative array.
		 *
		 * @access public
		 * @param object $objResult The result object
		 * @return array The result array, or null
		 */
		public function fetchRowAssoc($objResult) {
			return mysql_fetch_assoc($objResult);
		}
		
		
		/**
		 * Fetches the row as an object.
		 *
		 * @access public
		 * @param object $objResult The result object
		 * @param string $strClass The class to create the result object from
		 * @param array $arrParams Any params to pass to the result object constructor
		 * @return object The result object, or null
		 */
		public function fetchRowObject($objResult, $strClass = null, $arrParams = array()) {
			if ($strClass) {
				if ($arrParams) {
					return mysql_fetch_object($objResult, $strClass, $arrParams);
				} else {
					return mysql_fetch_object($objResult, $strClass);
				}
			} else {
				return mysql_fetch_object($objResult);
			}
		}
		
		
		/**
		 * Frees all resources associated with the result.
		 *
		 * @access public
		 * @param object $objResult The result object
		 */
		public function freeResult($objResult) {
			mysql_free_result($objResult);
		}
		

		/*****************************************/
		/**     TRANSACTION METHODS             **/
		/*****************************************/
		
		
		/**
		 * Begins the transaction by setting auto commit to false.
		 *
		 * @access public
		 * @return boolean True on success
		 */
		public function beginTransaction() {
			CoreDebug::debug($this, 'Begin transaction');
			return mysql_query('BEGIN', $this->objActive->rscDb);
		}
		
		
		/**
		 * Ends transactions by setting auto commit to true.
		 * Note: Does not commit or rollback anything.
		 *
		 * @access public
		 * @return boolean True on success
		 */
		public function endTransaction() {
			return;
		}
		
		
		/**
		 * Commits the current transaction.
		 *
		 * @access public
		 * @return boolean True on success
		 */
		public function commitTransaction() {
			CoreDebug::debug($this, 'Commit transaction');
			
			if (!($blnResult = mysql_query('COMMIT', $this->objActive->rscDb))) {
				trigger_error(AppLanguage::translate('Could not commit transaction: %s', $this->getError()));
			}
			
			return $blnResult;
		}
		
		
		/**
		 * Rolls back the current transaction.
		 *
		 * @access public
		 * @return boolean True on success
		 */
		public function rollbackTransaction() {
			CoreDebug::debug($this, 'Rollback transaction');
			
			if (!($blnResult = mysql_query('ROLLBACK', $this->objActive->rscDb))) {
				trigger_error(AppLanguage::translate('Could not roll back transaction: %s', $this->getError()));
			}
			
			return $blnResult;
		}
		
		
		/*****************************************/
		/**     GET & SET METHODS               **/
		/*****************************************/
		
		
		/**
		 * Gets the error message string.
		 *
		 * @access public
		 * @return string The error message
		 */
		public function getError() {
			if ($this->objActive->rscDb) {
				return mysql_error($this->objActive->rscDb);
			} else {
				return mysql_error();
			}
		}
		
		
		/**
		 * Returns the MySQL timestamp format to be used with the 
		 * date function.
		 *
		 * @access public
		 * @return string The date format
		 */
		public function getTimestampFormat() {
			return 'Y-m-d H:i:s';
		}
		
		
		/**
		 * Returns the MySQL datetime format to be used with the 
		 * date function.
		 *
		 * @access public
		 * @return string The date format
		 */
		public function getDatetimeFormat() {
			return 'Y-m-d H:i:s';
		}
		
		
		/**
		 * Returns the table columns and their descriptions.
		 *
		 * @access public
		 * @param string $strTable The table to get the columns from
		 * @return array The table columns and their types
		 */
		public function getTableColumns($strTable) {
			$strQuery = "SHOW COLUMNS FROM " . $this->escapeString($strTable);
			if ($mxdResult = $this->read($strQuery)) {
				$arrResult = array();
				while ($arrRow = $this->fetchRowAssoc($mxdResult)) {
					$intLength = $arrOptions = $strType = null;
					if (preg_match('/(.*)\((.+)\)/', $arrRow['Type'], $arrMatches)) {
						$strType = $arrMatches[1];
						
						if ($strType == 'set' || $strType == 'enum') {
							$arrOptions = explode(',', $arrMatches[2]);
							foreach ($arrOptions as $intKey=>$strVal) {
								$arrOptions[$intKey] = substr(str_replace("''", "'", $strVal), 1, -1);
							}
							$intLength = null;
						} else {
							$intLength = $arrMatches[2];
						}
					} else {
						$strType = $arrRow['Type'];
						$intLength = null;
					}
					
					if (in_array($strType, array('char', 'varchar', 'binary', 'varbinary', 'blob', 'text'))) {
						$strType = 'string';
					} else if (in_array($strType, array('int', 'bigint', 'mediumint', 'smallint', 'tinyint', 'decimal', 'float', 'double'))) {
						$strType = 'integer';
					} else if (in_array($strType, array('date', 'datetime', 'timestamp', 'time', 'year'))) {
						//leave as is
					} else if ($strType == 'enum') {
						$strType = 'select';
					} else if ($strType == 'set') {
						$strType = 'multiselect';
					} else {
						$strType = null;
					}
				
					$arrResult[] = array(
						'Raw'		=> $arrRow,
						'Name'		=> $arrRow['Field'],
						'Type'		=> $strType,
						'Length'	=> $intLength,
						'Options'	=> $arrOptions,
						'Default'	=> $arrRow['Default'],
						'Required'	=> $arrRow['Null'] == 'NO',
						'Primary'	=> !empty($arrRow['Key']) && $arrRow['Key'] == 'PRI'
					);
				}
				return $arrResult;
			}
		}
		
		
		/*****************************************/
		/**     MAGIC METHODS                   **/
		/*****************************************/
		
		
		/**
		 * Returns the database object's pretty name.
		 *
		 * @access public
		 * @return string The object's name
		 */
		public function __toString() {
			return 'Db: MySQL';
		}
	}