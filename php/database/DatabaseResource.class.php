<?php
	/**
	 * DatabaseResource.class.php
	 *
	 * This stores the database connection variables,
	 * a flag to determine if the server is connected
	 * and the database resource (either a resource or
	 * an object) in order to allow the database class
	 * to read from and write to different servers.
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
	final class DatabaseResource {
		
		public $rscDb;
		public $blnConnected;
		
		public $strDatabase;
		public $strUser;
		public $strPassword;
		public $strHost;
		public $intPort;
		public $blnPersistent;
		
		
		/**
		 * The database resource object's constructor. 
		 * Sets up the connection vars.
		 *
		 * @access public
		 * @param string $strDatabase The database to connect to
		 * @param string $strUser The username
		 * @param string $strPassword The password
		 * @param string $strHost The hostname
		 * @param integer $intPort The port
		 * @param boolean $blnPersistent Use persistent connections
		 */
		public function __construct($strDatabase, $strUser, $strPassword, $strHost, $intPort, $blnPersistent = false) {
			$this->blnConnected = false;
			$this->strDatabase = $strDatabase;
			$this->strUser = $strUser;
			$this->strPassword = $strPassword;
			$this->strHost = $strHost;
			$this->intPort = $intPort;
			$this->blnPersistent = $blnPersistent;
		}
	}