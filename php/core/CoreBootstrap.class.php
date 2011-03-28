<?php
	class_exists('CoreObject', false) || require('php/core/CoreObject.class.php');
	class_exists('CoreControllerLite', false) || require('php/core/CoreControllerLite.class.php');
	class_exists('CoreDebug', false) || require('php/core/CoreDebug.class.php');
	class_exists('CoreError', false) || require('php/core/CoreError.class.php');
	class_exists('CoreException', false) || require('php/core/CoreException.class.php');	
	class_exists('AppConfig', false) || require('php/app/AppConfig.class.php');
	class_exists('AppDisplay', false) || require('php/app/AppDisplay.class.php');
	class_exists('AppEvent', false) || require('php/app/AppEvent.class.php');
	class_exists('AppLanguage', false) || require('php/app/AppLanguage.class.php');
	class_exists('AppLoader', false) || require('php/app/AppLoader.class.php');
	class_exists('AppRegistry', false) || require('php/app/AppRegistry.class.php');
	class_exists('AppUrl', false) || require('php/app/AppUrl.class.php');
	
	/**
	 * CoreBootstrap.class.php
	 * 
	 * The bootstrap class sets up the configuration,
	 * loads any language translation files, includes
	 * the shared libraries, sets up the error handler
	 * and the debugging, parses the URL, and dispatches
	 * to a controller. 
	 *
	 * This has been broken up into several methods for
	 * easier extension.
	 *
	 * Copyright 2006-2011, Phork Labs. (http://phorklabs.com)
	 *
	 * Licensed under The MIT License
	 * Redistributions of files must retain the above copyright notice.
	 *
	 * @author Elenor Collings <elenor@phork.org>
	 * @license http://www.opensource.org/licenses/mit-license.php The MIT License
	 * @package phork
	 * @subpackage core
	 */
	class CoreBootstrap extends CoreObject {
		
		protected $blnSkipRun;
		
		protected $strDefaultController = 'CoreControllerLite';
		protected $strController;
		
		const HOOK_PRE_RUN = 'bootstrap.pre-run';
		const HOOK_PRE_INITIALIZE = 'bootstrap.pre-init';
		const HOOK_POST_INITIALIZE = 'bootstrap.post-init';
		const HOOK_POST_RUN = 'bootstrap.post-run';
		
		
		/**
		 * Sets up the configuration, loads all the libraries,
		 * sets up the error handler and the debugger, parses
		 * the URL and starts the session. The config array
		 * must contain the following variables:
		 *
		 * - $strInstallDir The absolute path to the phork directory
		 * - $strSiteDir The absolute path to the site directory
		 * - $strConfigDir The absolute path to the config directory
		 *
		 * @access public
		 * @param string $arrConfig The configuration array
		 */
		public function __construct($arrConfig) {
			extract($arrConfig);
			
			AppConfig::set('InstallDir', $strInstallDir);
			AppConfig::set('SiteDir', $strSiteDir);
			AppConfig::set('ConfigDir', $strConfigDir);
			
			$this->setPaths();
			$this->loadConfig();
			$this->loadLanguage();
			$this->loadLibs();
			
			AppRegistry::register('Error', new CoreError(
				AppConfig::get('ErrorVerbose'),
				AppConfig::get('ErrorLogFile'),
				AppConfig::get('ErrorLogNotice'),
				AppConfig::get('ErrorLogWarning'),
				AppConfig::get('ErrorLogError')
			));
			
			$this->initDebugging();
			$this->parseUrl();
			$this->startSession();
		}
		
		
		/*****************************************/
		/**     SETUP METHODS                   **/
		/*****************************************/
		
		
		/**
		 * Runs the processes to build and output the page
		 * and calls any hooks set earlier in the execution.
		 *
		 * @access public
		 */
		public function run() {
			if (method_exists($this, 'initHooks')) {
				$this->initHooks();
			}
			
			AppEvent::run(self::HOOK_PRE_RUN);
			AppEvent::destroy(self::HOOK_PRE_RUN);
			
			$this->strController = $this->determineController();
			
			AppEvent::run(self::HOOK_PRE_INITIALIZE);
			AppEvent::destroy(self::HOOK_PRE_INITIALIZE);
			
			$objController = $this->initController();
			
			AppEvent::run(self::HOOK_POST_INITIALIZE);
			AppEvent::destroy(self::HOOK_POST_INITIALIZE);
			
			$this->blnSkipRun || $objController->run();
			
			AppEvent::run(self::HOOK_POST_RUN);
			AppEvent::destroy(self::HOOK_POST_RUN);
		}
		
		
		/**
		 * Exits cleanly after calling the post run events.
		 * This should be called from a controller.
		 *
		 * @access public
		 */
		public function close() {
			AppEvent::run(self::HOOK_POST_RUN);
			AppEvent::destroy(self::HOOK_POST_RUN);
			exit;
		}
		
		
		/**
		 * Displays a fatal error via the default controller
		 * which also halts execution.
		 *
		 * @access public
		 * @param integer $intErrorCode The error code to send
		 */
		public function fatal($intErrorCode = 500) {
			$this->strController = $this->strDefaultController;
			$this->initController(true)->error($intErrorCode);
		}
		
		
		/**
		 * Sets the paths to all the site directories. The
		 * default is /path/to/phork/sites/[sitetype]/[dir]/.
		 * Generally it's a good idea to leave these as the
		 * default, however in the case where multiple sites
		 * share the same components these paths can be set
		 * accordingly.
		 *
		 * @access protected
		 */
		protected function setPaths() {
			$strSiteDir = AppConfig::get('SiteDir');
			
			AppConfig::set('TemplateDir', "{$strSiteDir}templates/");
			AppConfig::set('LangDir', "{$strSiteDir}lang/");
			AppConfig::set('FilesDir', "{$strSiteDir}files/");
			AppConfig::set('ScriptDir', "{$strSiteDir}scripts/");
		}
		
		
		/**
		 * Loads the common configuration files.
		 *
		 * @access protected
		 */
		protected function loadConfig() {
			AppConfig::load('global');
			AppConfig::load('site');
		}
		
		
		/**
		 * Sets the file path(s) to the language files
		 * and loads the language translations for the
		 * language defined in the config.
		 *
		 * @access protected
		 */
		protected function loadLanguage() {
			if ($strLanguage = AppConfig::get('Language', false)) {
				$arrFilePaths = array();
				if ($strInstallDir = AppConfig::get('InstallDir')) {
					$arrFilePaths[] = "{$strInstallDir}/lang/";
				}
				if ($strLangDir = AppConfig::get('LangDir')) {
					$arrFilePaths[] = $strLangDir;
				}
			
				$objLanguage = AppLanguage::getInstance();
				$objLanguage->setFilePath($arrFilePaths);
				$objLanguage->setCachePath(AppConfig::get('LangCache', false));
				$objLanguage->setLanguage($strLanguage);
			}
		}
		
		
		/**
		 * Loads the additional libraries. In this case the
		 * database and cache libs, if enabled.
		 *
		 * @access protected
		 */
		protected function loadLibs() {
			if (AppConfig::get('DatabaseEnabled')) {
				AppLoader::includeClass('php/database/', 'DatabaseFactory');
				$objDatabaseFactory = new DatabaseFactory('database');
				AppRegistry::register('Database', $objDatabaseFactory->init());
			}
	
			if (AppConfig::get('CacheEnabled')) {
				AppLoader::includeClass('php/cache/', 'CacheFactory');
				$objCacheFactory = new CacheFactory('cache');
				AppRegistry::register('Cache', $objCacheFactory->init());
			}
		}
		
		
		/**
		 * Initializes the debugging dispatcher and adds
		 * the handler objects to it. The default handler
		 * logs the debugging data to a file.
		 *
		 * @access protected
		 */
		protected function initDebugging() {
			if (AppConfig::get('DebugEnabled')) {
				AppLoader::includeExtension('debug/', 'DebugLog');
			
				$objDebug = CoreDebug::getInstance();
				$objDebug->addHandler('log', new DebugLog(AppConfig::get('DebugFile')));
			}
		}
		
		
		/**
		 * Initializes and registers the URL object and
		 * sets it up for parsing.
		 *
		 * @access protected
		 */
		protected function parseUrl() {
			AppRegistry::register('Url', $objUrl = new AppUrl(AppConfig::get('BaseUrl')));	
			$objUrl->setRoutes(AppConfig::get('Routes', false));
		}
		
		
		/**
		 * Starts the session if sessions are enabled.
		 * Any custom session handling should be set up
		 * in an extension of this.
		 *
		 * @access protected
		 */
		protected function startSession() {
			if (AppConfig::get('SessionsEnabled', false)) {
				if ($strSessionName = AppConfig::get('SessionName', false)) {
					session_name($strSessionName);
				}
				session_start();
			}
		}
		
		
		/*****************************************/
		/**     HOOK METHODS                    **/
		/*****************************************/
		
		
		/**
		 * Registers a hook to be called at the top of the
		 * run() method.
		 *
		 * @access public
		 * @param mixed $mxdCallback The name of the callback function, or an array for a class method
		 * @param array $arrParams The array of parameters to be passed to the callback
		 */
		public function registerPreRunHook($mxdCallback, array $arrParams = array()) {
			AppEvent::register(self::HOOK_PRE_RUN, $mxdCallback, $arrParams);
		}
		
		
		/**
		 * Registers a hook to be called before the controller
		 * is initialized.
		 *
		 * @access public
		 * @param mixed $mxdCallback The name of the callback function, or an array for a class method
		 * @param array $arrParams The array of parameters to be passed to the callback
		 */
		public function registerPreInitializeHook($mxdCallback, array $arrParams = array()) {
			AppEvent::register(self::HOOK_PRE_INITIALIZE, $mxdCallback, $arrParams);
		}
		
		
		/**
		 * Registers a hook to be called after the controller
		 * is run.
		 *
		 * @access public
		 * @param mixed $mxdCallback The name of the callback function, or an array for a class method
		 * @param array $arrParams The array of parameters to be passed to the callback
		 */
		public function registerPostInitializeHook($mxdCallback, array $arrParams = array()) {
			AppEvent::register(self::HOOK_POST_INITIALIZE, $mxdCallback, $arrParams);
		}
		
		
		/**
		 * Registers a hook to be called at the end of the
		 * run() method.
		 *
		 * @access public
		 * @param mixed $mxdCallback The name of the callback function, or an array for a class method
		 * @param array $arrParams The array of parameters to be passed to the callback
		 */
		public function registerPostRunHook($mxdCallback, array $arrParams = array()) {
			AppEvent::register(self::HOOK_POST_RUN, $mxdCallback, $arrParams);
		}
		
		
		/*****************************************/
		/**     CONTROLLER METHODS              **/
		/*****************************************/
		
		
		/**
		 * Determines which controller to use based on the
		 * parsed URL.
		 *
		 * @access public
		 * @return string The controller to use
		 */
		public function determineController() {
			if ($strController = AppRegistry::get('Url')->getSegment(0)) {
				return ucfirst(preg_replace('/[^a-z0-9]/i', '', $strController)) . 'Controller';
			} else {
				return $this->strDefaultController;
			}			
		}
		
		
		/**
		 * Sets up the controller that will handle the page.
		 * If no controller is found this displays either an
		 * exception backtrace if verbose errors are turned
		 * on or a 404 page if verbose errors are off.
		 *
		 * @access protected
		 * @param boolean $blnOverwrite Whether the controller can overwrite a previously registered controller
		 * @return object The controller
		 */
		protected function initController($blnOverwrite = false) {
			try {
				AppLoader::includeController($this->strController);
				return $this->registerController(new $this->strController(), $blnOverwrite);
			} catch (CoreException $objException) {
				$objDisplay = AppDisplay::getInstance();
				if (!($intStatusCode = $objDisplay->getStatusCode())) {
					$intStatusCode = 404;
				}
				
				if (AppConfig::get('ErrorVerbose')) {
					AppDisplay::getInstance()->setStatusCode($intStatusCode);
					$objException->handleException();
				} else {
					$this->fatal($intStatusCode);
				}
			}
		}
		
		
		/**
		 * Adds the controller to the object registry.
		 *
		 * @access protected
		 * @param object The controller
		 * @param boolean $blnOverwrite Whether the controller can overwrite a previously registered controller
		 * @return object The controller
		 */
		protected function registerController($objController, $blnOverwrite = false) {
			if ($blnOverwrite && AppRegistry::get('Controller', false)) {
				AppRegistry::destroy('Controller');
			}
			
			AppRegistry::register('Controller', $objController);
			return $objController;
		}
		
		
		/**
		 * Changes the controller when processing needs to
		 * be forwarded to a different controller and runs
		 * the controller.
		 *
		 * @access public
		 * @param string $strController The name of the new controller
		 */
		public function changeController($strController) {
			$this->strController = $strController;
			$this->initController(true)->run();
		}
	
		
		/*****************************************/
		/**     GET & SET METHODS               **/
		/*****************************************/
		
		
		/**
		 * Gets whether or not the run() method should be 
		 * skipped. Useful when displaying a cached page.
		 *
		 * @access public
		 * @return boolean Whether to skip the run() method
		 */
		public function getSkipRun() {
			return $this->blnSkipRun;
		}
		
		
		/**
		 * Sets whether or not the run() method should be
		 * skipped. Useful when displaying a cached page.
		 *
		 * @access public
		 * @param boolean $blnSkipRun Whether to skip the run() method
		 */
		public function setSkipRun($blnSkipRun) {
			$this->blnSkipRun = $blnSkipRun;
		}
	}