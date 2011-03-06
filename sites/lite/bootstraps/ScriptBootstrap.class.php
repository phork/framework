<?php
	require_once('php/core/CoreBootstrap.class.php');
	
	/**
	 * ScriptBootstrap.class.php
	 * 
	 * The bootstrap class parses the CLI args to determine which
	 * controller to use and delegates processing to it.
	 *
	 * Copyright 2006-2011, Phork Labs. (http://phorklabs.com)
	 *
	 * Licensed under The MIT License
	 * Redistributions of files must retain the above copyright notice.
	 *
	 * @author Elenor Collings <elenor@phork.org>
	 * @license http://www.opensource.org/licenses/mit-license.php The MIT License
	 * @package phork
	 * @subpackage bootstraps
	 */
	class ScriptBootstrap extends CoreBootstrap {
	
		protected $strDefaultController = 'ScriptController';
		
		
		/**
		 * Loads the common configuration files and sets up
		 * the script flag.
		 *
		 * @access protected
		 */
		protected function loadConfig() {
			AppConfig::load('global');
			AppConfig::load('script');
			
			AppConfig::set('AllowScripts', true);
		}
		
		
		/**
		 * Initializes the debugging dispatcher and adds
		 * the handler objects to it.
		 *
		 * @access protected
		 */
		protected function initDebugging() {
			if (AppConfig::get('DebugEnabled')) {
				AppLoader::includeExtension('debug/', 'DebugDisplay');
			
				$objDebug = CoreDebug::getInstance();
				$objDebug->addHandler('display', new DebugDisplay(false));
			}
		}
	}