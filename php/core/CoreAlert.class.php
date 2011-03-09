<?php
	require_once('php/core/CoreObject.class.php');
	require_once('interfaces/Singleton.interface.php');
	
	/**
	 * CoreAlert.class.php
	 *
	 * The alert class collects and returns alerts
	 * triggered from the various modules.
	 *
	 * This is a singleton class and therefore it must
	 * be instantiated using the getInstance() method.
	 *
	 * <code>
	 * CoreAlert::alert(AppLanguage::translate('Alert'));
	 * </code>
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
	class CoreAlert extends CoreObject implements Singleton {
		
		static protected $objInstance;
		
		protected $strSessionName;
		protected $arrAlerts = array();
		
		
		/**
		 * The constructor can't be public for a singleton.
		 *
		 * @access protected
		 */
		protected function __construct() {
			$this->strSessionName = AppConfig::get('AlertSessionName', false);
		}
		
		
		/** 
		 * Returns the instance of the singleton object. If
		 * it doesn't exist it instantiates it.
		 *
		 * @access public
		 * @return object The instance of the alert object
		 * @static
		 */
		static public function getInstance() {
			if (!self::$objInstance) {
				self::$objInstance = new CoreAlert();
			}
			return self::$objInstance;
		}
		
		
		/**
		 * This adds an alert to the list. If the sticky flag is
		 * set the alert will be stored in the session until it
		 * has been output. Useful for sending alerts after a
		 * user has been redirected.
		 *
		 * @access public
		 * @param string $strAlert The alert
		 * @param boolean $blnSticky Whether to save the alert until it's output
		 * @static
		 */
		static public function alert($strAlert, $blnSticky = false) {
			$objInstance = self::getInstance();
			if ($blnSticky) {
				if ($objInstance->strSessionName) {
					if (empty($_SESSION[$objInstance->strSessionName])) {
						$_SESSION[$objInstance->strSessionName] = array();
					}
					$_SESSION[$objInstance->strSessionName][] = $strAlert;
				} else {
					throw new CoreException(AppLanguage::translate('In order to set a sticky alert the AlertSessionName config must be defined'));
				}
			} else {
				$objInstance->arrAlerts[] = $strAlert;
			}
		}
		
		
		/**
		 * Returns all the alerts and clears them out.
		 *
		 * @access public
		 * @return array The alerts
		 * @static
		 */
		static public function flushAlerts() {
			$arrAlerts = self::getAlerts();
			self::getInstance()->arrAlerts = array();
			return $arrAlerts;
		}
		
		
		/**
		 * Returns the alert array as well as any sticky alerts.
		 * Clears out the sticky alerts after retrieving them.
		 *
		 * @access public
		 * @return array The alerts
		 * @static
		 */
		static public function getAlerts() {
			$objInstance = self::getInstance();
			
			if ($objInstance->strSessionName && !empty($_SESSION[$objInstance->strSessionName])) {
				$objInstance->arrAlerts = array_merge($_SESSION[$objInstance->strSessionName], $objInstance->arrAlerts);
				unset($_SESSION[$objInstance->strSessionName]);
			}
			return $objInstance->arrAlerts;
		}
		
		
		/**
		 * Sets the array of all the alerts.
		 *
		 * @access public
		 * @param array $arrAlerts The alerts to set
		 * @param boolean $blnSticky Whether to save the alert until it's output
		 * @static
		 */
		static public function setAlerts($arrAlerts, $blnSticky = false) {
			if ($blnSticky) {
				foreach ($arrAlerts as $strAlert) {
					self::alert($strAlert, true);
				}
			} else {
				self::getInstance()->arrAlerts = $arrAlerts;
			}
		}
		
		
		/**
		 * Returns true if any alerts exist.
		 *
		 * @access public
		 * @return boolean True if alerts exist
		 * @static
		 */
		static public function getAlertFlag() {
			$objInstance = self::getInstance();
			return $objInstance->arrAlerts || !empty($_SESSION[$objInstance->strSessionName]);
		}
	}