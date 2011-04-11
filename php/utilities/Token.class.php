<?php
	/**
	 * Token.class.php
	 * 
	 * A class for setting and checking a form token
	 * to verify that the data isn't being spoofed.
	 *
	 * Copyright 2006-2011, Phork Labs. (http://phorklabs.com)
	 *
	 * Licensed under The MIT License
	 * Redistributions of files must retain the above copyright notice.
	 *
	 * @author Elenor Collings <elenor@phork.org>
	 * @license http://www.opensource.org/licenses/mit-license.php The MIT License
	 * @package phork
	 * @subpackage utilities
	 */
	class Token {
		
		/**
		 * Verifies that the request data has a form token
		 * and that it's a valid one.
		 *
		 * @access public
		 * @return boolean True if the token exists and is valid
		 * @static
		 */
		static public function verifyRequest() {
			if (!AppConfig::get('TokenIgnore', false)) {
				$strTokenField = AppConfig::get('TokenField');
				
				if (!empty($_REQUEST)) {
					if (!empty($_REQUEST[$strTokenField])) {
						if (!self::validateToken($_REQUEST[$strTokenField])) {
							trigger_error(AppLanguage::translate('Invalid form token. This may have resulted from reloading the page. Please <a href="%s">try again</a>.', AppRegistry::get('Url')->getCurrentUrl()));
							return false;
						}
					} else {
						trigger_error(AppLanguage::translate('Missing form token'));
						return false;
					}
				} else {
					trigger_error(AppLanguage::translate('Missing form token'));
					return false;
				}
			}
			return true;
		}
		
		
		/**
		 * Initializes the unique form token to be used
		 * and stores it in the session.
		 * 
		 * @access public
		 * @return string The token
		 * @static
		 */
		static public function initToken() {
			$strSessionName = AppConfig::get('TokenSessionName');
			
			//clear out the old tokens after 5 minutes
			if (!empty($_SESSION[$strSessionName])) {
				foreach ($_SESSION[$strSessionName] as $strToken=>$intTime) {
					if ($intTime < time() - 300) {
						unset($_SESSION[$strSessionName][$strToken]);
					}
				}
			}
		
			$strToken = md5(uniqid(rand(), true));
			$_SESSION[$strSessionName][$strToken] = time();
			return $strToken;
		}
		
		
		/**
		 * Reallows the current form token to be used again.
		 * This is useful when an AJAX request fails.
		 *
		 * @access public
		 * @return boolean True on success
		 * @static
		 */
		static public function reviveToken() {
			$strSessionName = AppConfig::get('TokenSessionName');
			$strTokenField = AppConfig::get('TokenField');
			
			if (!empty($_REQUEST)) {
				if (!empty($_REQUEST[$strTokenField])) {
					$strToken = $_REQUEST[$strTokenField];
					$_SESSION[$strSessionName][$strToken] = time();
					return true;
				} else {
					trigger_error(AppLanguage::translate('Missing form token'));
					return false;
				}
			} else {
				trigger_error(AppLanguage::translate('Missing form token'));
				return false;
			}
		}
		
		
		/**
		 * Verifies that the token matches up with the
		 * a token in the user's session and then clears 
		 * out the matched token so forms can't be reposted.
		 *
		 * @access public
		 * @param string $strToken The token to validate
		 * @return boolean True on success
		 * @static
		 */
		static public function validateToken($strToken) {
			$strSessionName = AppConfig::get('TokenSessionName');
			
			if ($blnResult = !empty($_SESSION[$strSessionName][$strToken])) {
				unset($_SESSION[$strSessionName][$strToken]);
			}
			return $blnResult;
		}
	}