<?php
	/**
	 * Sql.interface.php
	 *
	 * The SQL interface for the database adaptor classes 
	 * to implement.
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
	 */
	interface Sql {
	
		public function __construct(DatabaseResource $objRead, DatabaseResource $objWrite);
		public function __destruct();
		public function initRead($blnAutoConnect = true);
		public function initWrite($blnAutoConnect = true);
		public function connect();
		public function close();
		public function selectDatabase();
		public function changeDatabase($strDatabase);
		public function escapeString($strString);
		public function read($strQuery);
		public function write($strQuery);
		public function getAffectedRows();
		public function getNumRows($mxdParam);
		public function fetchRow($mxdParam);
		public function fetchRowObject($mxdParam);
		public function fetchRowColumn($mxdParam, $strColumn);
		public function freeResult($mxdParam);
		public function getError();
		public function getTimestampFormat();
		public function getDatetimeFormat();
		public function beginTransaction();
		public function endTransaction();
		public function commitTransaction();
		public function rollbackTransaction();
		
		static public function isAvailable();
	}