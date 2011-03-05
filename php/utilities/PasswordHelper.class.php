<?php
	/**
	 * PasswordHelper.class.php
	 * 
	 * This creates and validates encrypted password.
	 * An encrypted password is stored in 3 parts. The
	 * first part is the algorithm, the second part is
	 * the salt and the third part is the salted and
	 * hashed password. For example:
	 * SHA-1:m7grsi:3af1abec67019c6e813306bf472671aee6de2726
	 *
	 * Copyright 2006-2010, Phork Labs. (http://phorklabs.com)
	 *
	 * Licensed under The MIT License
	 * Redistributions of files must retain the above copyright notice.
	 *
	 * @author Elenor Collings <elenor@phork.org>
	 * @license http://www.opensource.org/licenses/mit-license.php The MIT License
	 * @package phork
	 * @subpackage utilities
	 */
	class PasswordHelper {
	
		const HASH_ALGORITHM = 'SHA-1';
		const SALT_LENGTH = 6;
		const SALT_CHARS = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
		const FIELD_SEPARATOR = ':';
		
		
		/**
		 * Verifies that the raw password matches the encrypted
		 * password.
		 *
		 * @access public
		 * @param string $strEncryptedPassword The encrypted password to validate against
		 * @param string $strRawPassword The plain text password
		 * @return boolean True if the password validates
		 * @static
		 */
		static public function validatePassword($strEncryptedPassword, $strRawPassword) {
			list($strAlgorithm, $strSalt, $strHashedPassword) = explode(self::FIELD_SEPARATOR, $strEncryptedPassword);
			return $strHashedPassword == self::getPasswordHash($strAlgorithm, $strSalt, $strRawPassword);
		}
		
		
		/**
		 * Returns an encrypted password string along with the 
		 * hash type and the salt.
		 *
		 * @access public
		 * @param string $strRawPassword The plain text password to encrypt
		 * @return string The hashed password
		 * @static
		 */ 
		static public function encryptPassword($strRawPassword) {
			$strSalt = substr(str_shuffle(self::SALT_CHARS), 0, self::SALT_LENGTH);
			return self::HASH_ALGORITHM . self::FIELD_SEPARATOR . $strSalt . self::FIELD_SEPARATOR . self::getPasswordHash(self::HASH_ALGORITHM, $strSalt, $strRawPassword);
		}
		
		
		/**
		 * Generates and returns a hashed and salted password.
		 *
		 * @access protected
		 * @param string $strAlgorithm The algorithm to use
		 * @param string $strSalt The password salt
		 * @param string $strRawPassword The plain text password
		 * @return string The hashed and salted password
		 * @static
		 */
		static protected function getPasswordHash($strAlgorithm, $strSalt, $strRawPassword) {
			$strAlgorithm = strtolower(preg_replace('/\W/', '', $strAlgorithm));
			return hash($strAlgorithm, $strSalt . $strRawPassword);
		}
	}