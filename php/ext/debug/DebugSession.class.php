<?php
	require_once('php/core/interfaces/DebugHandler.interface.php');

	/**
	 * DebugSession.class.php
	 * 
	 * Logs the debugging data to a session to be used
	 * with an AJAX popup. This should be used as a 
	 * handler for CoreDebug.
	 *
	 * Copyright 2006-2011, Phork Labs. (http://phorklabs.com)
	 *
	 * Licensed under The MIT License
	 * Redistributions of files must retain the above copyright notice.
	 *
	 * @author Elenor Collings <elenor@phork.org>
	 * @license http://www.opensource.org/licenses/mit-license.php The MIT License
	 * @package phork
	 * @subpackage debug
	 */
	class DebugSession implements DebugHandler {
		
		protected $strSessionName;
		
		
		/**
		 * Determines which session variable should contain
		 * the debugging data.
		 *
		 * @access public
		 */
		public function __construct() {
			$this->strSessionName = AppConfig::get('DebugSessionName');
		}
		
		
		/**
		 * Saves the debugging data to the session.
		 *
		 * @access public
		 * @param string $strDebug The debugging string to save
		 */
		public function handle($strDebug) {
			$_SESSION[$this->strSessionName][] = array(microtime(true), $strDebug);
		}
	}