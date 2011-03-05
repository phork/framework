<?php
	require_once('php/core/interfaces/DebugHandler.interface.php');

	/**
	 * DebugDisplay.class.php
	 * 
	 * Displays the debugging data on the screen. This should
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
	class DebugDisplay implements DebugHandler {
		
		protected $blnHtml;
		
		
		/**
		 * Determines whether the debugging display should use
		 * an HTML delimiter.
		 *
		 * @access public
		 * @param boolean $blnHtml Whether to use HTML after each output
		 */
		public function __construct($blnHtml = true) {
			$this->blnHtml = $blnHtml;
		}
		
		
		/**
		 * Displays the debugging output to the screen.
		 *
		 * @access public
		 * @param string $strDebug The debugging string to display
		 */
		public function handle($strDebug) {
			$strDelimiter = ($this->blnHtml ? "<br />\n" : "\n");
			echo($strDebug.$strDelimiter);
		}
	}