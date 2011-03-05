<?php
	require_once('php/core/interfaces/DebugHandler.interface.php');

	/**
	 * DebugLog.class.php
	 * 
	 * Logs the debugging data to a file. This should
	 * be used as a handler for CoreDebug.
	 *
	 * Copyright 2006-2010, Phork Labs. (http://phorklabs.com)
	 *
	 * Licensed under The MIT License
	 * Redistributions of files must retain the above copyright notice.
	 *
	 * @author Elenor Collings <elenor@phork.org>
	 * @license http://www.opensource.org/licenses/mit-license.php The MIT License
	 * @package phork
	 * @subpackage debug
	 */
	class DebugLog implements DebugHandler {
		
		protected $strLogFile;
		
		
		/**
		 * Sets up the path to the log file.
		 *
		 * @access public
		 * @param string $strLogFile The path to the log file
		 */
		public function __construct($strLogFile) {
			$this->strLogFile = $strLogFile;
		}
		
		
		/**
		 * Logs the debugging information to a file.
		 *
		 * @access public
		 * @param string $strDebug The debugging string to log
		 */
		public function handle($strDebug) {
			if (!$this->strLogFile) {
				throw new CoreException(AppLanguage::translate('Invalid debug file'));
			}
			error_log("{$strDebug}\n", 3, $this->strLogFile);
		}
	}