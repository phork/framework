<?php
	require_once('php/core/CoreObject.class.php');
	require_once('DatabaseResource.class.php');
	require_once('interfaces/Sql.interface.php');
	
	/**
	 * DatabaseAdaptor.class.php
	 *
	 * An adaptor for the SQL database classes to extend.
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
	 * @abstract
	 */
	abstract class DatabaseAdaptor extends CoreObject implements Sql {
	
		protected $blnForceMaster;
		
		protected $arrConns;
		protected $strActive;
		protected $objActive;
		
		protected $objQuery;
			
		
		/**
		 * Sets up the read and write connections and the
		 * query object.
		 *
		 * @access public
		 * @param object $objRead The DatabaseResource object for reads
		 * @param object $objWrite The DatabaseResource object for writes
		 */
		public function __construct(DatabaseResource $objRead, DatabaseResource $objWrite) {
			$this->addConnection('Read', $objRead);
			$this->addConnection('Write', $objWrite);
			
			$strQueryClass = get_class($this) . 'Query';
			$this->objQuery = new $strQueryClass($this);
		}
		
		
		/**
		 * Closes any opened database connections when the
		 * object is destroyed.
		 *
		 * @access public
		 */
		public function __destruct() {
			foreach ($this->arrConns as $strConn=>$objConn) {
				if ($objConn->blnConnected) {
					if ($this->initConnection($strConn, false)) {
						$this->close();
					}
				}
			}
		}
		
		
		/**
		 * Determines if the database module is installed.
		 *
		 * @access public
		 * @return boolean True if available
		 * @static
		 */
		static public function isAvailable() {
			throw new CoreException(AppLanguage::translate('The %s method must be defined in the extension to the %s class', __METHOD__, __CLASS__));
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
		 * Makes sure that a connection is defined and that
		 * it's connected.
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
		 * Database resources can share the same connection 
		 * so when one is connected or disconnected another 
		 * may automatically be as well. This should be called
		 * whenever a connection or disconnection happens so
		 * it can update the other resources.
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
		 * Adds a connection to the connection pool. If the 
		 * connection  is overwriting an existing connection and
		 * that connection is the active one it clears the active
		 * connection data.
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
			throw new CoreException(AppLanguage::translate('The %s method must be defined in the extension to the %s class', __METHOD__, __CLASS__));
		}
		
		
		/**
		 * Closes the database connection and sets the connected
		 * flag to false.
		 *
		 * @access public
		 */
		public function close() {
			throw new CoreException(AppLanguage::translate('The %s method must be defined in the extension to the %s class', __METHOD__, __CLASS__));
		}
		
		
		/**
		 * Selects the database based on the database name in
		 * the resource object.
		 *
		 * @access public
		 * @return boolean True on success
		 */
		public function selectDatabase() {
			throw new CoreException(AppLanguage::translate('The %s method must be defined in the extension to the %s class', __METHOD__, __CLASS__));
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
			throw new CoreException(AppLanguage::translate('The %s method must be defined in the extension to the %s class', __METHOD__, __CLASS__));
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
			throw new CoreException(AppLanguage::translate('The %s method must be defined in the extension to the %s class', __METHOD__, __CLASS__));
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
		 * Gets the last inserted ID
		 *
		 * @access public
		 * @return integer The inserted ID
		 */
		public function getInsertedId() {
			throw new CoreException(AppLanguage::translate('The %s method must be defined in the extension to the %s class', __METHOD__, __CLASS__));
		}
		
		
		/**
		 * Gets the number of affected rows.
		 *
		 * @access public
		 * @return integer The number of rows
		 */
		public function getAffectedRows() {
			throw new CoreException(AppLanguage::translate('The %s method must be defined in the extension to the %s class', __METHOD__, __CLASS__));
		}
		
		
		/**
		 * Gets the number of rows in the result.
		 *
		 * @access public
		 * @param object $objResult The result object
		 * @return integer The number of rows
		 */
		public function getNumRows($objResult) {
			throw new CoreException(AppLanguage::translate('The %s method must be defined in the extension to the %s class', __METHOD__, __CLASS__));
		}
		
		
		/**
		 * Fetches the row as a numeric array.
		 *
		 * @access public
		 * @param object $objResult The result object
		 * @return array The result array, or null
		 */
		public function fetchRow($objResult) {
			throw new CoreException(AppLanguage::translate('The %s method must be defined in the extension to the %s class', __METHOD__, __CLASS__));
		}
		
		
		/**
		 * Fetches the row as an associative array.
		 *
		 * @access public
		 * @param object $objResult The result object
		 * @return array The result array, or null
		 */
		public function fetchRowAssoc($objResult) {
			throw new CoreException(AppLanguage::translate('The %s method must be defined in the extension to the %s class', __METHOD__, __CLASS__));
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
			if ($arrRow = $this->fetchRowAssoc($objResult)) {
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
			throw new CoreException(AppLanguage::translate('The %s method must be defined in the extension to the %s class', __METHOD__, __CLASS__));
		}
		

		/*****************************************/
		/**     TRANSACTION METHODS             **/
		/*****************************************/
		
		
		/**
		 * Begins the transaction.
		 *
		 * @access public
		 * @return boolean True on success
		 */
		public function beginTransaction() {
			throw new CoreException(AppLanguage::translate('The %s method must be defined in the extension to the %s class', __METHOD__, __CLASS__));
		}
		
		
		/**
		 * Ends the transactions. Does not commit or rollback anything.
		 *
		 * @access public
		 * @return boolean True on success
		 */
		public function endTransaction() {
			throw new CoreException(AppLanguage::translate('The %s method must be defined in the extension to the %s class', __METHOD__, __CLASS__));
		}
		
		
		/**
		 * Commits the current transaction.
		 *
		 * @access public
		 * @return boolean True on success
		 */
		public function commitTransaction() {
			throw new CoreException(AppLanguage::translate('The %s method must be defined in the extension to the %s class', __METHOD__, __CLASS__));
		}
		
		
		/**
		 * Rolls back the current transaction.
		 *
		 * @access public
		 * @return boolean True on success
		 */
		public function rollbackTransaction() {
			throw new CoreException(AppLanguage::translate('The %s method must be defined in the extension to the %s class', __METHOD__, __CLASS__));
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
			throw new CoreException(AppLanguage::translate('The %s method must be defined in the extension to the %s class', __METHOD__, __CLASS__));
		}
		
		
		/**
		 * Returns the timestamp format to be used with the 
		 * date function.
		 *
		 * @access public
		 * @return string The date format
		 */
		public function getTimestampFormat() {
			throw new CoreException(AppLanguage::translate('The %s method must be defined in the extension to the %s class', __METHOD__, __CLASS__));
		}
		
		
		/**
		 * Returns the datetime format to be used with the 
		 * date function.
		 *
		 * @access public
		 * @return string The date format
		 */
		public function getDatetimeFormat() {
			throw new CoreException(AppLanguage::translate('The %s method must be defined in the extension to the %s class', __METHOD__, __CLASS__));
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
		 * run on the master database, which is assume to be the
		 * writable database.
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