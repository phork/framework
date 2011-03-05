<?php
	require_once('php/core/CoreObject.class.php');
	require_once('php/database/MySqli/MySqliQuery.class.php');
	require_once('php/database/DatabaseResource.class.php');
	require_once('php/database/interfaces/Sql.interface.php');
	
	/**
	 * MySQLi.class.php
	 *
	 * An adaptor for the MySQLi database functions.
	 *
	 * Copyright 2006-2010, Phork Labs. (http://phorklabs.com)
	 *
	 * Licensed under The MIT License
	 * Redistributions of files must retain the above copyright notice.
	 *
	 * @author Elenor Collings <elenor@phork.org>
	 * @license http://www.opensource.org/licenses/mit-license.php The MIT License
	 * @package phork
	 * @subpackage database
	 *
	 * @todo For dbBindParam try call_user_func_array(array(&$this->objActive->rscDb, 'bind_param'), $args);
	 * @todo Build MySQLi query class
	 */
	class MySqli extends CoreObject implements Sql {
	
		protected $arrConns;
		
		protected $strActive;
		protected $objActive;
		protected $objQuery;
		protected $blnForceMaster;
		
	
		/**
		 * Sets up the read and write connections and the query
		 * object.
		 *
		 * @access public
		 * @param object $objRead The DatabaseResource object for reads
		 * @param object $objWrite The DatabaseResource object for writes
		 */
		public function __construct(DatabaseResource $objRead, DatabaseResource $objWrite) {
			$this->addConnection('Read', $objRead);
			$this->addConnection('Write', $objWrite);
			
			$this->objQuery = new MySqlQuery($this);
		}
		
		
		/**
		 * Closes any opened database connections when the
		 * object is destroyed.
		 *
		 * @access public
		 */
		public function __destruct() {
			foreach ($this->arrConns as $strConn=>$objResource) {
				if ($objResource->blnConnected) {
					if ($this->initConnection($strConn, false)) {
						$this->close();
					}
				}
			}
		}
		
		
		/**
		 * Determines if the MySQLi module is installed.
		 *
		 * @access public
		 * @return boolean True if available
		 * @static
		 */
		static public function isAvailable() {
			return class_exists('MySQLi', false);
		}
		
		
		/*****************************************/
		/**     CONNECTION METHODS              **/
		/*****************************************/
		
		
		/**
		 * Returns the connection object.
		 *
		 * @access public
		 * @return object The connection object
		 */
		public function getConnection($strConn) {
			if (array_key_exists($strConn, $this->arrConns)) {
				return $this->arrConns[$strConn];
			}
		}
		
		
		/**
		 * Returns the connection types.
		 *
		 * @access public
		 * @return array The connection types
		 */
		public function getConnectionTypes() {
			return array_keys($this->arrConns);
		}
		
		
		/**
		 * Makes sure that a connection is defined and that it's
		 * connected.
		 *
		 * @access protected
		 * @return boolean True if there's a valid and open connection
		 */
		protected function checkConnection() {
			if ($this->objActive) {
				if ($this->objActive->blnConnected || $this->connect()) {
					return true;
				} else {
					trigger_error(AppLanguage::translate('No database server available'));
				}
			} else {
				trigger_error(AppLanguage::translate('Invalid database resource object'));
			}
		}
		
		
		/**
		 * Database resources can share the same connection so when
		 * one is connected or disconnected another may automatically
		 * be as well. This should be called whenever a connection or
		 * disconnection happens so it can update the other resources.
		 *
		 * @access protected
		 */
		protected function groupConnections() {
			foreach ($this->arrConns as $strConn=>$objConn) {
				if ($this->strActive && $strConn != $this->strActive) {
					$blnGrouped = $objConn->strDatabase == $this->objActive->strDatabase &&
					              $objConn->strUser == $this->objActive->strUser &&
					              $objConn->strPassword == $this->objActive->strPassword &&
					              $objConn->strHost == $this->objActive->strHost &&
					              $objConn->intPort == $this->objActive->intPort &&
					              $objConn->blnPersistent == $this->objActive->blnPersistent;
					
					if ($blnGrouped) {
						$objConn->rscDb = $this->objActive->rscDb;
						$objConn->blnConnected = $this->objActive->blnConnected;
					}
				}
			}
		}
		
		
		/**
		 * Adds a connection to the connection pool. If the connection 
		 * is overwriting an existing connection and that connection 
		 * is the active one it clears the active connection data.
		 *
		 * @access public
		 * @param string $strConn The name of the connection
		 * @param object $objConn The DatabaseResource object
		 * @param boolean $blnOverwrite Whether the connection being added is allowed to overwrite an existing one
		 */
		public function addConnection($strConn, DatabaseResource $objConn, $blnOverwrite = false) {
			if (!$blnOverwrite && !empty($this->arrConns[$strConn])) {
				throw new CoreException(AppLanguage::translate('A database connection already exists for %s', $strConn));
			}
			if ($blnOverwrite && $this->strActive == $strConn) {
				$this->objActive = null;
				$this->strActive = null;
			}
			$this->arrConns[$strConn] = $objConn;
		}
		
		
		/**
		 * Sets up the database for the specific connection type
		 * passed (eg. Read, Write). Automatically connects to the 
		 * database if the auto connect flag is set to true and
		 * the connection hasn't already been made.
		 * 
		 * @access public
		 * @param boolean $blnAutoConnect Whether to automatically connect to the database
		 * @return boolean True on success
		 */
		 public function initConnection($strConn, $blnAutoConnect = true) {
		 	if (!($blnResult = ($this->strActive == $strConn))) {
				if (array_key_exists($strConn, $this->arrConns) && is_object($this->arrConns[$strConn])) {
					$this->objActive = $this->arrConns[$strConn];
					$this->strActive = $strConn;
					
					if ($blnAutoConnect && !$this->objActive->blnConnected) {
						$this->connect();
					}
					
					$blnResult = $this->objActive->blnConnected;
				}
			}
			
			return $blnResult;
		}
		
		
		/**
		 * Sets up the database for a read query.
		 * 
		 * @access public
		 * @param boolean $blnAutoConnect Whether to automatically connect to the database
		 * @return boolean True on success
		 */
		public function initRead($blnAutoConnect = true) {
			return $this->initConnection('Read', $blnAutoConnect);
		}
		
		
		/**
		 * Sets up the database for a write query.
		 * 
		 * @access public
		 * @param boolean $blnAutoConnect Whether to automatically connect to the database
		 * @return boolean True on success
		 */
		public function initWrite($blnAutoConnect = true) {
			return $this->initConnection('Write', $blnAutoConnect);
		}
		
		
		/**
		 * Connects to the database and selects the database
		 * defined in the resource object.
		 *
		 * @access public
		 * @return boolean True on success
		 */
		public function connect() {
			//CoreDebug::debug($this, 'Connect ' . $this->strActive);
			
			//make sure that the necessary MySQLi functions exist
			if (!$this->isAvailable()) {
				return false;
			}
			
			//make sure that the active resource object has been set
			if (!$this->objActive) {
				throw new CoreException(AppLanguage::translate('Invalid database resource object'));
			}
			
			//if a connection doesn't already exist, connect now
			if (!$this->objActive->blnConnected) {
				
				//make sure a database is set
				if (empty($this->objActive->strDatabase)) {
					throw new CoreException(AppLanguage::translate('No database selected'));
				}
			
				//connect to the database
				$this->objActive->rscDb = new MySQLi($this->objActive->strHost, $this->objActive->strUser, $this->objActive->strPassword, $this->objActive->strDatabase, $this->objActive->intPort);
				
				//check for errors
				if (!mysqli_connect_errno()) {
					if ($this->selectDatabase()) {
						$this->objActive->blnConnected = true;
					}
					$this->groupConnections();
				}
			}
		
			//set the connection flag
			if (!$this->objActive->blnConnected) {
				trigger_error(AppLanguage::translate('Could not connect to the database: %s', mysql_error()));
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
			//CoreDebug::debug($this, 'Close ' . $this->strActive);
			
			if ($this->objActive) {
				if ($this->objActive->blnConnected) {
					if ($this->objActive->rscDb->close()) {
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
		public function selectDatabase($strDatabase) {
			if ($this->objActive->rscDb->select_db($strDatabase)) {
				return true;
			} else {
				trigger_error(AppLanguage::translate('Could not select the database: %s', $this->getError()));
			}
		}
		
		
		/**
		 * Permanently changes the database in the resource
		 * object, and the database connection.
		 *
		 * @access public
		 * @param string $strDatabase The new database
		 * @return boolean True on success
		 */
		public function changeDatabase($strDatabase) {
			$this->objActive->strDatabase = $strDatabase;
			$this->selectDatabase();
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
		
			//make sure there's a database object
			if (!$this->objActive->blnConnected && !$this->connect()) {
				trigger_error(AppLanguage::translate('Could not escape string'));
				return false;
			}
			
			return $this->objActive->rscDb->real_escape_string($strString);
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
			CoreDebug::debug($this, "Query (" . $this->strActive . ' @ ' . $this->objActive->strDatabase . "): $strQuery");
			
			//make sure there's a connection
			if ($this->checkConnection()) {
				$mxdResult = $this->objActive->rscDb->query($strQuery);]
				if (!$mxdResult) {
					trigger_error(AppLanguage::translate('Could not perform query: %s', $this->getError()));
				}
			} else {
				trigger_error(AppLanguage::translate('No database connection'));
			}
			
			return (isset($mxdResult) ? $mxdResult : false);
		}
		
		
		/**
		 * Performs a read query.
		 *
		 * @access public
		 * @param string $strQuery The query to execute
		 * @param boolean $blnFromMaster Whether to read from the master database
		 * @return object The result object
		 */
		public function read($strQuery, $blnFromMaster = false) {
			if ($blnFromMaster || $this->blnForceMaster) {
				$this->initWrite(true);
			} else {
				$this->initRead(true);
			}
			return $this->query($strQuery);
		}
		
		
		/**
		 * Performs a write query.
		 *
		 * @access public
		 * @param string $strQuery The query to execute
		 * @return boolean True on success
		 */
		public function write($strQuery) {
			$this->initWrite(true);
			return $this->query($strQuery);
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
			return $this->objActive->rscDb->insert_id;
		}
		
		
		/**
		 * Gets the number of affected rows.
		 *
		 * @access public
		 * @param object $objResult The result object
		 * @return integer The number of rows
		 */
		public function getAffectedRows($objResult) {
			return $objResult->affected_rows;
		}
		
		
		/**
		 * Gets the number of rows in the result.
		 *
		 * @access public
		 * @param object $objResult The result object
		 * @return integer The number of rows
		 */
		public function getNumRows($objResult) {
			return $objResult->num_rows;
		}
		
		
		/**
		 * Fetches the row as an associative array.
		 *
		 * @access public
		 * @param object $objResult The result object
		 * @return array The result array, or null
		 */
		public function fetchRow($objResult) {
			return $objResult->fetch_assoc();
		}
		
		
		/**
		 * Fetches a single column from the row.
		 *
		 * @access public
		 * @param object $objResult The result object
		 * @param string $strColumn The column to fetch
		 * @return mixed The column data
		 */
		public function fetchRowColumn($objResult, $strColumn) {
			if ($arrRow = $this->fetchRow($objResult)) {
				if (isset($arrRow[$strColumn])) {
					return $arrRow[$strColumn];
				}
			}
		}
		
		
		/**
		 * Frees all resources associated with the result.
		 *
		 * @access public
		 * @param object $objResult The result object
		 */
		public function freeResult($objResult) {
			$objResult->free_result();
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
			return $this->objActive->rscDb->autocommit(FALSE);
		}
		
		
		/**
		 * Ends transactions by setting auto commit to true.
		 * Note: Does not commit or rollback anything.
		 *
		 * @access public
		 * @return boolean True on success
		 */
		public function endTransaction() {
			return $this->objActive->rscDb->autocommit(TRUE);
		}
		
		
		/**
		 * Commits the current transaction.
		 *
		 * @access public
		 * @return boolean True on success
		 */
		public function commitTransaction() {
			CoreDebug::debug($this, 'Commit transaction');
			
			if (!($blnResult = $this->objActive->rscDb->commit())) {
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
			
			if (!($blnResult = $this->objActive->rscDb->rollback())) {
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
			return $this->objActive->rscDb->error;
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
		 * Returns the query builder object.
		 *
		 * @access public
		 * @return object The query builder object
		 */
		public function getQuery() {
			return $this->objQuery;
		}
		

		/**
		 * When this is set to true it forces all the queries to
		 * run on the master.
		 *
		 * @access public
		 * @param boolean $blnForceMaster
		 */
		public function setForceMaster($blnForceMaster) {
			$this->blnForceMaster = $blnForceMaster;
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
			return 'Db: MySQLi';
		}
		
		
		/**
		 * Returns the list of variables that should be stored
		 * when the object is serialized. Don't serialize anything.
		 *
		 * @access public
		 * @return array The array of vars
		 */
		public function __sleep() {
			return array();
		}
	}