<?php
	require_once('php/core/CoreObject.class.php');
	
	/**
	 * CommonHooks.class.php
	 * 
	 * A collection of commonly used application hooks to
	 * be used in conjunction with the bootstrap.
	 *
	 * Copyright 2006-2010, Phork Labs. (http://phorklabs.com)
	 *
	 * Licensed under The MIT License
	 * Redistributions of files must retain the above copyright notice.
	 *
	 * @author Elenor Collings <elenor@phork.org>
	 * @license http://www.opensource.org/licenses/mit-license.php The MIT License
	 * @package phork
	 * @subpackage hooks
	 */
	class CommonHooks extends CoreObject {
	
		/**
		 * Verifies that a form token was posted and that
		 * the token is valid. Exits gracefully on failure.
		 *
		 * @access public
		 */
		public function verifyToken() {
			AppLoader::includeUtility('Token');
			if (!empty($_POST) && !Token::verifyRequest()) {
				AppRegistry::get('Bootstrap')->fatal(400);
			}
		}
		
		
		/**
		 * Tracks each URL visited by the user and stores
		 * it in their session. Used for redirecting users
		 * back to previous pages as necessary.
		 *
		 * @access public
		 * @param integer $intMax The maximum number of URLs to track
		 * @param array $arrExtSkip Any file extensions to skip (eg. js, css)
		 */
		public function trackHistory($intMax = 1, $arrExtSkip = array()) {
			if ($strHistorySessionName = AppConfig::get('HistorySessionName')) {
				if (!in_array(AppRegistry::get('Url')->getExtension(), $arrExtSkip)) {
					$strCompleteUrl = AppRegistry::get('Url')->getCompleteUrl();
					if (!empty($_SESSION[$strHistorySessionName])) {
						if (count($_SESSION[$strHistorySessionName]) == $intMax) {
							array_shift($_SESSION[$strHistorySessionName]);
						}
					} else {
						$_SESSION[$strHistorySessionName] = array();
					}
					$_SESSION[$strHistorySessionName][] = $strCompleteUrl;
				}
			}
		}
	}