<?php
	require_once('php/core/CoreObject.class.php');
	require_once('php/app/AppDisplay.class.php');
	require_once('php/core/interfaces/Controller.interface.php');
	
	/**
	 * ScriptController.class.php
	 * 
	 * This controller handles the scripts. It must
	 * implement the Controller interface.
	 *
	 * Copyright 2006-2010, Phork Labs. (http://phorklabs.com)
	 *
	 * Licensed under The MIT License
	 * Redistributions of files must retain the above copyright notice.
	 *
	 * @author Elenor Collings <elenor@phork.org>
	 * @license http://www.opensource.org/licenses/mit-license.php The MIT License
	 * @package phork-lite
	 * @subpackage controllers
	 */
	class ScriptController extends CoreObject implements Controller {
		
		protected $strMethodPrefix = 'run';
		
		
		/**
		 * This is called from the bootstrap and it handles
		 * any necessary processing before calling the display
		 * method. Generally this sets up the type of content
		 * to display based on the URL. If this is missing the
		 * AllowScripts flag (set in the bootstrap) then this
		 * will fail.
		 *
		 * @access public
		 */
		public function run() {
			if (AppConfig::get('AllowScripts', false)) {
				$strContent = AppRegistry::get('Url')->getSegment(1);
				if ($strContent) {
					if (method_exists($this, $strMethod = $this->strMethodPrefix . $strContent)) {
						$this->$strMethod();
						return;
					}
				}
			}
			$this->error(404);
		}
		
		
		/**
		 * Displays the system error output. This can be
		 * be called from the bootstrap for fatal errors.
		 *
		 * @access public
		 * @param integer $intErrorCode The error code to send
		 */
		public function error($intErrorCode = 500) {
			switch ($intErrorCode) {
				case 403:
					trigger_error($strError = AppLanguage::translate('Permission denied'));
					break;
					
				case 404:
					trigger_error($strError = AppLanguage::translate('Page not found'));
					break;
					
				default:
					trigger_error($strError = AppLanguage::translate('Fatal error'));
					break;
			}
			throw new CoreException($strError);
		}
	}