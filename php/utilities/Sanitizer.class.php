<?php
	/**
	 * Sanitizer.class.php
	 *
	 * Used to sanitize data from untrusted sources (eg. POST).
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
	class Sanitizer {
	
		/**
		 * Strips tags and removes any other unwanted strings
		 * from the value passed.
		 *
		 * @access public
		 * @param string $strValue The value to sanitize
		 * @return boolean True if changed
		 * @static
		 */
		static public function sanitizeItem(&$strValue) {
			$strSanitized = strip_tags($strValue);
			if ($strValue != $strSanitized) {
				$strValue = $strSanitized;
				return true;
			}
		}
		
	
		/**
		 * Strips tags and removes any other unwanted strings from
		 * the array of data passed and returns any values that
		 * are unsanitary. This is a recursive function.
		 *
		 * @access public
		 * @param array $arrData The data to sanitize
		 * @return array The array of unsanitary data
		 */
		static public function sanitizeArray(&$arrData) {
			$arrUnsanitary = array();
			foreach ($arrData as $strKey=>$mxdValue) {
				if (is_array($mxdValue)) {
					$arrUnsanitary = array_merge($arrUnsanitary, self::sanitizeArray($mxdValue));
				} else {
					if (self::sanitizeItem($mxdValue)) {
						$arrUnsanitary[] = $arrData[$strKey];
						$arrData[$strKey] = $mxdValue;
					}
				}
			}
			return $arrUnsanitary;
		}
	}