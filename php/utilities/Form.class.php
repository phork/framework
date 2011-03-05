<?php
	/**
	 * Form.class.php
	 * 
	 * A class to build form elements.
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
	class Form {	
		
		/**
		 * Returns a checkbox element.
		 *
		 * @access public
		 * @param string $strName The name of the form element
		 * @param mixed $mxdValue The value of the checkbox
		 * @param boolean $blnChecked True if the checkbox is checked
		 * @param array $arrParams The array of custom parameters (id, class, etc)
		 * @return string The checkbox HTML
		 * @static
		 */
		static public function getCheckbox($strName, $mxdValue, $blnChecked = false, $arrParams = null) {
			$strForm = '<input type="checkbox" name="' . $strName . '" value="' . htmlspecialchars($mxdValue) . '" ';
			
			if ($blnChecked == true) {
				$strForm .= 'checked="checked" ';
			}
			
			if (!is_null($arrParams)) {
				self::addParams($strForm, $arrParams);
			}
			
			$strForm .= '/>';
			
			return $strForm;
		}
		
		
		/**
		 * Returns a radio button element.
		 *
		 * @access public
		 * @param string $strName The name of the form element
		 * @param mixed $mxdValue The value of the radio button
		 * @param boolean $blnChecked True if the radio button is checked
		 * @param array $arrParams The array of custom parameters (id, class, etc)
		 * @return string The radio button HTML
		 * @static
		 */
		static public function getRadioButton($strName, $mxdValue, $blnChecked = false, $arrParams = null) {
			$strForm = '<input type="radio" name="' . $strName . '" value="' . htmlspecialchars($mxdValue) . '" ';
			
			if ($blnChecked == true) {
				$strForm .= 'checked="checked" ';
			}
			
			if (!is_null($arrParams)) {
				self::addParams($strForm, $arrParams);
			}
			
			$strForm .= '/>';
			
			return $strForm;
		}
		
		
		/**
		 * Returns a text area element.
		 *
		 * @access public
		 * @param string $strName The name of the form element
		 * @param mixed $strValue The value of the textarea
		 * @param array $arrParams The array of custom parameters (id, class, etc)
		 * @return string The text area HTML
		 * @static
		 */
		static public function getTextarea($strName, $strValue = null, $arrParams = null) {
			$strForm = '<textarea name="' . $strName . '" ';
			
			if (!is_null($arrParams)) {
				self::addParams($strForm, $arrParams);
			}
			
			$strForm .= '>' . htmlspecialchars($strValue) . '</textarea>';
			
			return $strForm;
		}
		
		
		/**
		 * Returns a text box element.
		 *
		 * @access public
		 * @param string $strName The name of the form element
		 * @param mixed $mxdValue The value of the text box
		 * @param array $arrParams The array of custom parameters (id, class, etc)
		 * @return string The text box HTML
		 * @static
		 */
		static public function getTextbox($strName, $mxdValue = null, $arrParams = null) {
			$strForm = '<input type="text" name="' . $strName . '" value="' . htmlspecialchars($mxdValue) . '" ';
			
			if (!is_null($arrParams)) {
				self::addParams($strForm, $arrParams);
			}
			
			$strForm .= '/>';
			
			return $strForm;
		}
		
		
		/**
		 * Returns a password element.
		 *
		 * @access public
		 * @param string $strName The name of the form element
		 * @param mixed $mxdValue The value of the password box
		 * @param array $arrParams The array of custom parameters (id, class, etc)
		 * @return string The password box HTML
		 * @static
		 */
		static public function getPasswordBox($strName, $mxdValue = null, $arrParams = null) {
			$strForm = '<input type="password" name="' . $strName . '" value="' . htmlspecialchars($mxdValue) . '" ';
			
			if (!is_null($arrParams)) {
				self::addParams($strForm, $arrParams);
			}
			
			$strForm .= '/>';
			
			return $strForm;
		}
		
		
		/**
		 * Returns a hidden element.
		 *
		 * @access public
		 * @param string $strName The name of the form element
		 * @param mixed $mxdValue The value of the hidden field
		 * @param array $arrParams The array of custom parameters (id, class, etc)
		 * @return string The hidden field HTML
		 * @static
		 */
		static public function getHidden($strName, $mxdValue, $arrParams = null) {
			$strForm = '<input type="hidden" name="' . $strName . '" value="' . htmlspecialchars($mxdValue) . '" ';
			
			if (!is_null($arrParams)) {
				self::addParams($strForm, $arrParams);
			}
			
			$strForm .= '/>';
			
			return $strForm;
		}
		
		
		/**
		 * Returns a select element.
		 *
		 * @access public
		 * @param string $strName The name of the form element
		 * @param mixed $arrValues The value of the select element
		 * @param array $arrSelected The selected elements
		 * @param array $arrParams The array of custom parameters (id, class, etc)
		 * @param string $strFirstOption The first element in the list
		 * @return string The select HTML
		 * @static
		 */
		static public function getSelect($strName, $arrValues, $arrSelected = array(), $arrParams = null, $strFirstOption = null) {
			$strForm = '<select name="' . $strName . '"';
			
			if (!is_null($arrParams)) {
				$strForm .= ' ';
				self::addParams($strForm, $arrParams);
			}
			
			$strForm .= '>';
			
			if (!empty($strFirstOption)) {
				$strForm .= '<option value=""';
				
				if (empty($arrSelected)) {
					$strForm .= ' selected="selected"';
				}
				
				$strForm .= '>' . $strFirstOption . '</option>';
			}
			
			if (is_array($arrValues)) {
				foreach ($arrValues as $mxdKey=>$mxdValue) {
					$strForm .= '<option value="' . htmlspecialchars($mxdKey) . '"';
					
					if (!empty($arrSelected) && in_array($mxdKey, $arrSelected)) {
						$strForm .= ' selected="selected"';
					}
					
					$strForm .= '>' . ($mxdValue ? $mxdValue : $mxdKey) . '</option>';
				}
			}
				
			$strForm .= '</select>';
			
			return $strForm;
		}
		
		
		/**
		 * Returns a file input element.
		 *
		 * @access public
		 * @param string $strName The name of the form element
		 * @param array $arrParams The array of custom parameters (id, class, etc)
		 * @static
		 */
		static public function getFile($strName, $arrParams = null) {
			$strForm = '<input type="file" name="' . $strName . '" ';
			
			if (!is_null($arrParams)) {
				self::addParams($strForm, $arrParams);
			}
			
			$strForm .= '/>';
			
			return $strForm;
		}
			
		
		/**
		 * Returns a button element.
		 *
		 * @access public
		 * @param string $strName The name of the form element
		 * @param mixed $mxdValue The value of the button
		 * @param array $arrParams The array of custom parameters (id, class, etc)
		 * @param boolean $blnButtonElement Whether to use input or button (true is button)
		 * @param string $strButtonChild The child element data of the button (this will not be escaped)
		 * @return string The submit button HTML
		 * @static
		 */
		static public function getButton($strName, $mxdValue, $arrParams = null, $blnButtonElement = false, $strButtonChild = null) {
			$strForm = '<' . ($blnButtonElement ? 'button' : 'input') . ' type="button" name="' . $strName . '" value="' . htmlspecialchars($mxdValue) . '" ';
			
			if (!is_null($arrParams)) {
				self::addParams($strForm, $arrParams);
			}
			
			if ($blnButtonElement && $strButtonChild) {
				$strForm .= '>' . $strButtonChild . '</button>';
			} else {
				$strForm .= '/>';
			}
			
			return $strForm;
		}
		
		
		/**
		 * Returns a submit element.
		 *
		 * @access public
		 * @param string $strName The name of the form element
		 * @param mixed $mxdValue The value of the submit button
		 * @param array $arrParams The array of custom parameters (id, class, etc)
		 * @param boolean $blnButtonElement Whether to use input or button (true is button)
		 * @param string $strButtonChild The child element data of the button (this will not be escaped)
		 * @return string The submit button HTML
		 * @static
		 */
		static public function getSubmit($strName, $mxdValue, $arrParams = null, $blnButtonElement = false, $strButtonChild = null) {
			$strForm = '<' . ($blnButtonElement ? 'button' : 'input') . ' type="submit" name="' . $strName . '" value="' . htmlspecialchars($mxdValue) . '" ';
			
			if (!is_null($arrParams)) {
				self::addParams($strForm, $arrParams);
			}
			
			if ($blnButtonElement && $strButtonChild) {
				$strForm .= '>' . $strButtonChild . '</button>';
			} else {
				$strForm .= '/>';
			}
			
			return $strForm;
		}
		
		
		/**
		 * Adds the various parameters to the form element.
		 *
		 * @access protected
		 * @param string $strForm The form element so far
		 * @param array $arrParams The parameters to add to the form element
		 * @static
		 */
		static protected function addParams(&$strForm, $arrParams) {
			foreach ($arrParams as $strParam=>$mxdValue) {
				$strForm .= $strParam . '="' . $mxdValue . '" ';
			}
		}
		
		
		/*****************************************/
		/**     CUSTOM FORM METHODS             **/
		/*****************************************/
		
		
		/**
		 * Returns a select box with the months in it.
		 *
		 * @access public
		 * @param string $strName The name of the form element
		 * @param array $arrSelected The selected elements
		 * @param array $arrParams The array of custom parameters (id, class, etc)
		 * @param string $strFirstOption The first element in the list
		 * @return string The select HTML
		 * @static
		 */
		static public function getSelectMonth($strName, $arrSelected = array(), $arrParams = null, $strFirstOption = null) {
			$arrMonths = array(
				1	=> 'January',
				2	=> 'February',
				3	=> 'March',
				4	=> 'April',
				5	=> 'May',
				6	=> 'June',
				7	=> 'July',
				8	=> 'August',
				9	=> 'September',
				10	=> 'October',
				11	=> 'November',
				12	=> 'December'
			);
			
			return self::getSelect($strName, $arrMonths, $arrSelected, $arrParams, $strFirstOption);
		}
		
		
		/**
		 * Returns a select box containing a range of numbers 
		 * with the keys and values being equal. Good for a
		 * dropdown with a list of years, for example.
		 *
		 * @access public
		 * @param string $strName The name of the form element
		 * @param integer $intStart The start of the range (can be higher or lower than $intEnd)
		 * @param integer $intEnd The end of the range (can be higher or lower than $intStart)
		 * @param array $arrSelected The selected elements
		 * @param array $arrParams The array of custom parameters (id, class, etc)
		 * @param string $strFirstOption The first element in the list
		 * @param integer $intZeroPad The string length of the values after zero padding
		 * @return string The select HTML
		 * @static
		 */
		static public function getSelectRange($strName, $intStart, $intEnd, $arrSelected = array(), $arrParams = null, $strFirstOption = null, $intZeroPad = null) {
			$arrValues = array_values(range($intStart, $intEnd));
			if ((int) $intZeroPad) {
				foreach ($arrValues as $intKey=>$intValue) {
					$arrValues[$intKey] = str_pad($intValue, $intZeroPad, '0', STR_PAD_LEFT);
				}
			}
			return self::getSelect($strName, array_combine($arrValues, $arrValues), $arrSelected, $arrParams, $strFirstOption);
		}
	}