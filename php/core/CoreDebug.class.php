<?php
	require_once('php/core/CoreObject.class.php');
	require_once('php/core/interfaces/Singleton.interface.php');
	
	/**
	 * CoreDebug.class.php
	 * 
	 * The debug class is used to output or log any debugging
	 * data by dispatching the debugging data to one or more 
	 * handler classes. If no handlers have been defined then 
	 * the debugging data is disregarded.
	 * 
	 * This is a singleton class and therefore it must be
	 * instantiated using the getInstance() method.
	 *
	 * <code>
	 * $objDebug = CoreDebug::getInstance();
	 * $objDebug->addHandler('log', new DebugLog('/path/to/logfile'));
	 * $objDebug->addHandler('output', new DebugOutput());
	 *
	 * CoreDebug::debug(time(), 'Debug me');
	 * </code>
	 *
	 * Copyright 2006-2010, Phork Labs. (http://phorklabs.com)
	 *
	 * Licensed under The MIT License
	 * Redistributions of files must retain the above copyright notice.
	 *
	 * @author Elenor Collings <elenor@phork.org>
	 * @license http://www.opensource.org/licenses/mit-license.php The MIT License
	 * @package phork
	 * @subpackage core
	 * @final
	 */
	final class CoreDebug extends CoreObject implements Singleton {
		
		static private $objInstance;
		
		private $intCounter;
		private $arrHandlers;
		
		
		/**
		 * The constructor can't be public for a singleton.
		 *
		 * @access private
		 */
		private function __construct() {
			$this->intCounter = 0;
			$this->arrHandlers = array();
		}
		
		
		/** 
		 * Returns the instance of the singleton object. If
		 * it doesn't exist it instantiates it.
		 *
		 * @access public
		 * @return object The instance of the debug object
		 * @static
		 */
		static public function getInstance() {
			if (!self::$objInstance) {
				self::$objInstance = new CoreDebug();
			}
			return self::$objInstance;
		}
		
		
		/**
		 * Builds the debugging string and dispatches the 
		 * debugging to the instance handler. This can be 
		 * passed as many args as necessary.
		 *
		 * @access public
		 * @static
		 */
		static public function debug() {
			$arrDebug = func_get_args();
			$strDebug = implode(': ', $arrDebug);
			self::getInstance()->handleDebug($strDebug);
		}
		
		
		/**
		 * Dispatches the debugging to each debugging
		 * handler. If this is the first debug output
		 * of the instance then display the notice.
		 *
		 * @access private
		 * @param string $strDebug The string to handle
		 */
		private function handleDebug($strDebug) {
			if ($this->arrHandlers) {
				if (++$this->intCounter == 1) {
					$this->handleDebug('Debug Initialized' . (!empty($_SERVER['REQUEST_URI']) ? ' (' . $_SERVER['REQUEST_URI'] . ')' : null));
				}
				
				foreach ($this->arrHandlers as $objHandler) {
					$objHandler->handle($strDebug);
				}
			}
		}
		
		
		/**
		 * Adds a debugging handler. The handlers do the
		 * actual processing and output and/or storage of
		 * the debugging data.
		 *
		 * @access public
		 * @param string $strHandler The name of the handler
		 * @param object $objHandler The handler object
		 */
		public function addHandler($strHandler, $objHandler) {
			if ($objHandler instanceof DebugHandler) {
				$this->arrHandlers[$strHandler] = $objHandler;
			} else {
				throw new CoreException(AppLanguage::translate('The debugging handlers must implement the DebugHandler interface'));
			}
		}
		
		
		/**
		 * Removes a debugging handler.
		 *
		 * @access public
		 * @param string $strHander The name of the handler to remove
		 */
		public function removeHandler($strHandler) {
			if (array_key_exists($strHandler, $this->arrHandlers)) {
				unset($this->arrHandlers[$strHandler]);
			} else {
				throw new CoreException(AppLanguage::translate('Invalid debugging handler (%s)', $strHandler));
			}
		}
	}