<?php
	/**
	 * JsonHelper.class.php
	 *
	 * Used to encode and decode JSON data. PHP's built in
	 * json_encode isn't enough because it only works with
	 * UTF-8 data so this uses Zend's JSON encoding if it's
	 * available.
	 *
	 * Copyright 2006-2010, Phork Software. (http://www.phork.org)
	 *
	 * Licensed under The MIT License
	 * Redistributions of files must retain the above copyright notice.
	 *
	 * @author Elenor Collings <elenor@phork.org>
	 * @license http://www.opensource.org/licenses/mit-license.php The MIT License
	 * @package phork
	 * @subpackage utilities
	 */
	class JsonHelper {
	
		/**
		 * Encodes an array of data into JSON format.
		 *
		 * @access public
		 * @param array $arrData The data to encode
		 * @return string The JSON encoded string
		 * @static
		 */
		static public function encode($arrData) {
			try {
				AppLoader::includeExtension('zend/', 'ZendLoader');
				ZendLoader::includeClass('Zend_Json');
				return Zend_Json::encode($arrData);
			} catch (Exception $objException) {
				return json_encode($arrData);
			}
		}
		
		
		/**
		 * Decodes a string of JSON data into either
		 * an object or an array.
		 *
		 * @access public
		 * @param string $strData The data to decode
		 * @param boolean $blnAssoc Whether to return an array instead of an object
		 * @return object or array The JSON decoded data
		 * @static
		 */
		static public function decode($strData, $blnAssoc = false) {
			try {
				AppLoader::includeExtension('zend/', 'ZendLoader');
				ZendLoader::includeClass('Zend_Json');
				return Zend_Json::decode($strData, $blnAssoc);
			} catch (Exception $objException) {
				return json_decode($strDecode, $blnAssoc);
			}
		}
	}