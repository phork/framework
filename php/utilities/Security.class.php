<?php
	/**
	 * Security.class.php
	 *
	 * A utility for handling various security
	 * procedures. In the future this will use the
	 * password for encryption. Currently it just
	 * uses a translated base 64 encoded string.
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
	 * @todo Implement real encryption and decryption using the password
	 */
	class Security {
	
		/**
		 * Encrypts the string (2 way) using a pre-defined password.
		 *
		 * @access public
		 * @param string $strDecrypted The data to encrypt
		 * @param string $strPassword The password to use for encryption to override the default
		 * @static
		 */
		static public function encrypt($strDecrypted, $strPassword = null) {
			return strtr(base64_encode($strDecrypted), AppConfig::get('EncryptInput'), AppConfig::get('EncryptOutput'));
		}
		
		
		/**
		 * Decrypts the string using a pre-defined password.
		 *
		 * @access public
		 * @param string $strDecrypted The data to encrypt
		 * @param string $strPassword The password to use for decryption to override the default
		 * @static
		 */
		static public function decrypt($strEncrypted, $strPassword = null) {
			return base64_decode(strtr($strEncrypted, AppConfig::get('EncryptOutput'), AppConfig::get('EncryptInput')));
		}
	}