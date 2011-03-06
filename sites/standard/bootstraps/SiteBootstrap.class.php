<?php
	require_once('php/core/CoreBootstrap.class.php');
	
	/**
	 * SiteBootstrap.class.php
	 * 
	 * The bootstrap sets up the site-wide libs and
	 * configs and delegates processing to a controller.
	 *
	 * This should also require the default controller
	 * at the top of the file. 
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
	class SiteBootstrap extends CoreBootstrap {
	
		protected $strDefaultController = 'SiteController';
		
		
		/**
		 * Sets up the configuration, loads all the libraries,
		 * sets up the error handler and the debugger, parses
		 * the URL and starts the session. This also sets up
		 * the script timer using the global timer object and
		 * registers the pre-output event.
		 *
		 * @access public
		 * @param string $arrConfig The configuration array
		 */
		public function __construct($arrConfig) {
			parent::__construct($arrConfig);
			
			AppLoader::includeUtility('Conversion');
			AppRegistry::register('Timer', $GLOBALS['objTimer']);
			AppEvent::register('display.pre-output', array($this, 'preOutput'));
		}
		
		
		/**
		 * Sets up the hooks to run during execution.
		 * Currently setting up the hook to verify the
		 * form post, track URL history, and serve and
		 * save the page cache.
		 *
		 * @access public
		 */
		public function initHooks() {
			if (AppLoader::includeHooks('CommonHooks')) {
				$objCommonHooks = new CommonHooks();
				$this->registerPreRunHook(array($objCommonHooks, 'verifyToken'));
				$this->registerPostRunHook(array($objCommonHooks, 'trackHistory'), array(5, array('css', 'js', 'xml', 'json', 'jsonp', 'html')));
			}
				
			if (AppLoader::includeHooks('CacheHooks')) {
				$objCacheHooks = new CacheHooks();
				$this->registerPreRunHook(array($objCacheHooks, 'serveCache'));
				$this->registerPostRunHook(array($objCacheHooks, 'saveCache'));
			}
		}
		
		
		/**
		 * Replaces the load time and the peak memory
		 * usage in the output. This is registered as
		 * an event in the constructor and called from
		 * the display object.
		 *
		 * Don't use AppLoader::includeUtility in this 
		 * method unless the display output class is
		 * manually called and doesn't rely on the
		 * desctructor.
		 *
		 * @access public
		 */
		public function preOutput() {
			$objDisplay = AppDisplay::getInstance();
			$objDisplay->replace('<[LOAD TIME]>', AppRegistry::get('Timer')->getTime());
			$objDisplay->replace('<[PEAK MEMORY]>', Conversion::convertBytes(memory_get_peak_usage()));
		}
	}