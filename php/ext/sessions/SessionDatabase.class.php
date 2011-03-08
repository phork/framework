<?php
	require_once('php/core/interfaces/SessionHandler.interface.php');

	/**
	 * SessionDatabase.class.php
	 * 
	 * Saves the session data in the database. This
	 * should be used as a handler for CoreSession.
	 * This requires a class called SessionModel.
	 *
	 * Copyright 2006-2011, Phork Labs. (http://phorklabs.com)
	 *
	 * Licensed under The MIT License
	 * Redistributions of files must retain the above copyright notice.
	 *
	 * @author Elenor Collings <elenor@phork.org>
	 * @license http://www.opensource.org/licenses/mit-license.php The MIT License
	 * @package phork
	 * @subpackage sessions
	 */
	class SessionDatabase implements SessionHandler {
		
		/**
		 * The open function works like a contructor and is
		 * executed when the session is open.
		 * 
		 * @access public
		 * @param string $strSavePath The save path for file-based sessions
		 * @param string $strSessionName The name of the session
		 * @return boolen True
		 */
		public function open($strSavePath, $strSessionName) {
			AppLoader::includeModel('SessionModel');
		}
		
		
		/**
		 * The close function works like a destructor and is
		 * executed when the session operation is done.
		 * 
		 * @access public
		 * @return boolen True
		 */
		public function close() {
			return true;
		}
		
		
		/**
		 * The read function retrieves and returns the session
		 * data. It must return a string.
		 *
		 * @access public
		 * @param string $strId The session ID to read
		 * @return string The session data
		 */
		public function read($strId) {
			$objSession = new SessionModel();
			if ($objSession->loadCurrentBySession($strId) && $objSessionRecord = $objSession->first()) {
				return $objSessionRecord->get('data');
			}
		}
		
		
		/**
		 * The write function writes the session data. It's not
		 * executed until after the output stream is closed. Output
		 * will never be seen in the browser.
		 *
		 * @access public
		 * @param string $strId The session ID to write
		 * @param string $strData The session data to write
		 * @return boolean True on success
		 */
		public function write($strId, $strData) {
			$objSession = new SessionModel();
			if (!($objSession->loadBySession($strId) && $objSession->count())) {
				$objSession->import(array(
					'session'	=> $strId
				));
			}
			
			$objSessionRecord = $objSession->current();
			$objSessionRecord->set('data', $strData);
			$objSessionRecord->set('expires', date(AppRegistry::get('Database')->getDatetimeFormat(), time() + AppConfig::get('SessionGcLifetime')));
			return $objSession->save();
		}
		
		
		/**
		 * The destroy function is executed when the session 
		 * is destroyed with session_destroy().
		 *
		 * @access public
		 * @param string $strId The session ID to destroy
		 * @return boolean True on success
		 */
		public function destroy($strId) {
			$objSession = new SessionModel();
			if ($objSession->loadBySession($strId) && $objSession->count()) {
				return $objSession->destroy();
			} 
		}
		
		
		/**
		 * The clean up function is a garbage collector for
		 * removing old session data.
		 *
		 * @access public
		 * @return boolen True
		 * @param integer $intLifetime The session's maximum lifetime
		 */
		public function cleanup($intLifetime) {
			$objSession = new SessionModel();
			return $objSession->deleteExpired();
		}
	}