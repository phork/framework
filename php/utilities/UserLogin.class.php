<?php
	require_once('php/core/CoreObject.class.php');
	
	/**
	 * UserLogin.class.php
	 * 
	 * Determines whether a user is logged in or not and
	 * handles the log in and log out process. Requires 
	 * that a user model and a user login model be defined
	 * in the config.
	 *
	 * Copyright 2006-2011, Phork Labs. (http://www.phorklabs.com)
	 *
	 * Licensed under The MIT License
	 * Redistributions of files must retain the above copyright notice.
	 *
	 * @author Elenor Collings <elenor@phork.org>
	 * @license http://www.opensource.org/licenses/mit-license.php The MIT License
	 * @package phork
	 * @subpackage utilities
	 */
	class UserLogin extends CoreObject {
	
		protected $strUserCookieName;
		protected $strUserIdSessionName;
		protected $strUserObjectSessionName;
		protected $strFingerprintSessionName;
		
		protected $objUser;
		protected $strUserModel;
		protected $strUserLoginModel;
		protected $strLoginMethod;
		
		const LOGIN_METHOD_COOKIE = 'cookie';
		const LOGIN_METHOD_FORM = 'form';
		const COOKIE_SEPARATOR = ':';
		
		
		/**
		 * The constructor includes the user model for when
		 * when the user record is unserialized.
		 *
		 * @access public
		 */
		public function __construct() {
			$this->strUserCookieName = AppConfig::get('UserCookieName');
			$this->strUserIdSessionName = AppConfig::get('UserIdSessionName');
			$this->strUserObjectSessionName = AppConfig::get('UserObjectSessionName');
			$this->strFingerprintSessionName = AppConfig::get('FingerprintSessionName');
			
			$this->strUserModelName = AppConfig::get('UserModel');
			$this->strUserLoginModelName = AppConfig::get('UserLoginModel');
			
			AppLoader::includeModel($this->strUserModelName);
		}
		
		
		/*****************************************/
		/**      LOGIN METHODS                  **/
		/*****************************************/
		
		
		/**
		 * Checks if a user is logged in using the user ID
		 * set in the session and makes sure the session is
		 * valid.
		 *
		 * @access public
		 * @return boolean True if logged in
		 */
		public function isLoggedIn() {
			if (!empty($_SESSION[$this->strUserIdSessionName])) {
				if (!empty($_SESSION[$this->strFingerprintSessionName])) {
					if ($_SESSION[$this->strFingerprintSessionName] == $this->getSessionFingerprint()) {
						return true;
					}
				}
				$this->handleLogout();
			}
		}
		
		
		/**
		 * Logs the user out by destroying all the session
		 * and cookie data. Also clears out the user model
		 * object.
		 *
		 * @access public
		 */
		public function handleLogout() {
			$this->getUserModel()->clear();
			
			if (isset($_COOKIE[$strSessionName = session_name()])) {
				setcookie($strSessionName, '', time() - 3600, '/', AppConfig::get('CookieDomain'));
				unset($_COOKIE[$strSessionName]);
			}
			$this->deleteCookie();
			
			$_SESSION = array();
			session_destroy();
		}
		
		
		/**
		 * When all login data has been verified this logs
		 * the user in by setting the necessary cookie and
		 * session data.
		 *
		 * @access public
		 * @param object $objUser The user model containing the user data of the user to login
		 * @return boolean True
		 */
		public function handleLogin($objUser) {		
			$this->objUser = $objUser;
			$objUserRecord = $objUser->first();
			$intUserId = $objUserRecord->get('__id');
			
			session_regenerate_id(true);
			$_SESSION[$this->strFingerprintSessionName] = $this->getSessionFingerprint();
			$_SESSION[$this->strUserIdSessionName] = $intUserId;
			
			if (!AppConfig::get('NoLoginCookie', false) && $this->strLoginMethod != self::LOGIN_METHOD_COOKIE) {
				$this->setCookie($intUserId);
			}
			
			return true;
		}
		
		
		/**
		 * Logs the user in using the form data. This works 
		 * with a clone of the user model object to prevent 
		 * invalid data being loaded into the real object.
		 * The specific error messages have been commented 
		 * out for security reasons. 
		 *
		 * @access public
		 * @param string $strUsername The username to login with
		 * @param string $strPassword The password to login with (encrypted)
		 * @return boolean True on success
		 */
		public function handleFormLogin($strUsername, $strPassword) {
			$this->strLoginMethod = self::LOGIN_METHOD_FORM;
			if ($strUsername && $strPassword) {
				$objUser = clone $this->getUserModel(true);	
				if ($objUser->loadByUsername($strUsername) && ($objUserRecord = $objUser->first())) {
					if (PasswordHelper::validatePassword($objUserRecord->get('password'), $strPassword)) {
						return $this->handleLogin($objUser);			
					} else {
						//trigger_error(AppLanguage::translate('Invalid password'));
					}
				} else {
					//trigger_error(AppLanguage::translate('Invalid username'));
				}
			}		
			trigger_error(AppLanguage::translate('Invalid login credentials'));
			return false;
		}
		
		
		/**
		 * Logs the user in using their cookie data. This
		 * works with a clone of the the user model object
		 * to prevent invalid data being loaded into the real
		 * object. If the cookie login fails the cookie is
		 * deleted.
		 *
		 * @access public
		 * @return boolean True on success
		 */
		public function handleCookieLogin() {
			$this->strLoginMethod = self::LOGIN_METHOD_COOKIE;
			if ($intUserId = $this->getCookie()) {
				$objUser = clone $this->getUserModel(true);
				if ($objUser->loadById($intUserId) && ($objUserRecord = $objUser->first())) {
					return $this->handleLogin($objUser);
				} else {
					trigger_error(AppLanguage::translate('Invalid cookie data'));
				}
			}
			$this->deleteCookie();	
			return false;
		}
		
		
		/*****************************************/
		/**      COOKIE METHODS                 **/
		/*****************************************/
		
		
		/**
		 * Determines if the user has a login cookie.
		 *
		 * @access public
		 * @return boolean True if cookied
		 */
		public function hasCookie() {
			return !empty($_COOKIE[$this->strUserCookieName]);
		}
		
		
		/**
		 * Gets the user ID from the login cookie, validates
		 * it, and returns the ID if valid. Re-saves the valid
		 * login record to update the accessed date and deletes
		 * any invalid records.
		 *
		 * @access protected
		 * @return integer $intUserId The user ID from a valid cookie
		 */
		protected function getCookie() {
			if (!empty($_COOKIE[$this->strUserCookieName])) {
				list($intUserId, $strPublicKey) = explode(self::COOKIE_SEPARATOR, $_COOKIE[$this->strUserCookieName]);
				if ($intUserId && $strPublicKey) {
					AppLoader::includeModel($this->strUserLoginModelName);
					$objUserLogin = new $this->strUserLoginModelName();
					if ($objUserLogin->loadByUserIdAndPublicKey($intUserId, $strPublicKey) && $objUserLoginRecord = $objUserLogin->first()) {
						if ($objUserLoginRecord->get('privatekey') == $objUserLogin->getPrivateKey($intUserId)) {
							$objUserLogin->save();
							return $intUserId;
						} else {
							$objUserLogin->destroy();
						}
					}
				}
			}
		}
		
		
		/**
		 * Sets the login cookie for the user record passed.
		 * If the user ID and private key already exist this
		 * will just update the accessed date.
		 *
		 * @access public
		 * @param integer $intUserId The user ID to set the cookie for
		 */
		public function setCookie($intUserId) {
			AppLoader::includeModel($this->strUserLoginModelName);
			$objUserLogin = new $this->strUserLoginModelName(array('Validate' => true));
			if ($objUserLogin->loadByUserIdAndPrivateKey($intUserId, $objUserLogin->getPrivateKey($intUserId))) {
				if (!$objUserLogin->count()) {
					$objUserLogin->import(array(
						'userid' => $intUserId
					));
				}
				
				if ($objUserLogin->save()) {
					$strValue = $intUserId . self::COOKIE_SEPARATOR . $objUserLogin->first()->get('publickey');
					setcookie($this->strUserCookieName, $strValue, time() + (86400 * 365), '/', AppConfig::get('CookieDomain'));
				}
			}
		}
		
		
		/**
		 * Deletes the login cookie. Doesn't delete the
		 * login record because theoretically the record
		 * could be shared between computers.
		 * 
		 * @access protected
		 */
		protected function deleteCookie() {
			if (isset($_COOKIE[$this->strUserCookieName])) {
				setcookie($this->strUserCookieName, '', time() - 3600, '/', AppConfig::get('CookieDomain'));
			}
		}
		
		
		/*****************************************/
		/**      GET & SET METHODS              **/
		/*****************************************/
		
		
		/**
		 * Returns the user ID from the session.
		 *
		 * @access public
		 * @return integer The user Id
		 */
		public function getUserId() {
			if ($this->isLoggedIn()) {
				return $_SESSION[$this->strUserIdSessionName];
			}
		}
		
		
		/**
		 * Gets the user model object. If the object hasn't been
		 * created it does so automatically and appends the user
		 * record to it.
		 *
		 * @access public
		 * @param boolean $blnSkipAppend If set this won't populate new models
		 * @return object The user model object
		 */
		public function getUserModel($blnSkipAppend = false) {
			if (!$this->objUser) {
				$this->objUser = new $this->strUserModelName();
				if (!$blnSkipAppend) {
					$this->getUserRecord();
				}
			}
			return $this->objUser;
		}
		
		
		/**
		 * Gets the user record object. If the user object hasn't 
		 * been retrieved and decrypted from the session this
		 * attempts do so. User objects are encrypted for use in
		 * shared hosting environments.
		 *
		 * @access public
		 * @return object The user record object
		 */
		public function getUserRecord() {
			if ($blnSharedHosting = AppConfig::get('SharedHosting', false)) {
				AppLoader::includeUtility('Security');
			}
		
			if (!($objUserRecord = $this->getUserModel(true)->first())) {
				if (!empty($_SESSION[$this->strUserObjectSessionName]) && $this->isLoggedIn()) {
					if ($objUserRecord = unserialize($blnSharedHosting ? Security::decrypt($_SESSION[$this->strUserObjectSessionName]) : $_SESSION[$this->strUserObjectSessionName])) {
						if ($objUserRecord instanceof CoreRecord) {
							$this->objUser->append($objUserRecord);
						} else {
							$objUserRecord = null;
						}
					}
				}
				
				if (!$objUserRecord && ($intUserId = $this->getUserId())) {
					if ($this->objUser->loadById($intUserId) && ($objUserRecord = $this->objUser->first())) {
						$_SESSION[$this->strUserObjectSessionName] = $blnSharedHosting ? Security::encrypt(serialize($objUserRecord)) : serialize($objUserRecord);
					}
				}
			}
			return $objUserRecord;
		}
		
		
		/**
		 * Sets the user record object. Useful after a user has
		 * updated their profile or settings.
		 *
		 * @access public
		 * @param object $objUserRecord The user record object
		 */
		public function setUserRecord(CoreRecord $objUserRecord) {
			$this->clearUserRecord();
			$this->objUser->append($objUserRecord);
		}
		
		
		/**
		 * Clears out the user object.
		 *
		 * @access public
		 */
		public function clearUserRecord() {
			$this->getUserModel()->clear();
			$_SESSION[$this->strUserObjectSessionName] = null;
		}
		
		
		/**
		 * Returns the login method used to log in the user.
		 *
		 * @access public
		 * @return string The login method
		 */
		public function getLoginMethod() {
			return $this->strLoginMethod;
		}
		
		
		/**
		 * Returns the fingerprint used to validate the user's
		 * session to make sure it hasn't been spoofed.
		 *
		 * @access protected
		 * @return string The session fingerprint
		 */
		protected function getSessionFingerprint() {
			return md5(AppConfig::get('FingerprintSessionSalt') . $_SERVER['HTTP_USER_AGENT'] . session_id());
		}
	}