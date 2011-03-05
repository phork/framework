<?php
	/**
	 * Markup.class.php
	 * 
	 * A class for stripping tags and replacing
	 * BBcode with regular HTML.
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
	class Markup {

		//the array of patterns and replacements
		static protected $arrTagReplacement = array(
			'b'		=> array(
				'/\[b\](.*)\[\/b\]/Ui',
				'<strong>$1</strong>'
			),
			
			'u'		=> array(
				'/\[u\](.*)\[\/u\]/Ui',
				'<u>$1</u>'
			),
			
			'i'		=> array(
				'/\[i\](.*)\[\/i\]/Ui',
				'<em>$1</em>'
			),
			
			'url' 	=> array(
				'/\[url=(https?:\/\/[^"]*)\](.*)\[\/url\]/Ui',
				'<a href="$1" rel="external">$2</a>'
			),
			
			'img' 	=> array(
				'/\[img\](.*)\[\/img\]/Ui',
				'<img src="$1" />'
			),
			
			'tab'	=> array(
				'/\[tab\]/Ui',
				'&nbsp;&nbsp;&nbsp;&nbsp;'
			)
		);
		

		/**
		 * Replaces all the BBcode tags with the HTML equivalent
		 * equivalent and strips the rest of the HTML, if necessary.
		 *
		 * @access public
		 * @param string $strString The string to format
		 * @param boolean $blnStripTags Whether to strip any HTML tags
		 * @param string $arrAllowedTags The tags that are allowed, otherwise it'll replace them all
		 * @return string The formatted string
		 * @static
		 */
		static public function replaceTags($strString, $blnStripTags = false, $arrAllowedTags = null) {
			
			//strip the tags, if necessary
			if ($blnStripTags) {
				$strString = strip_tags($strString);
			}
			
			//get the array of allowed patterns and replacements
			$arrPattern = $arrReplacement = array();
			foreach (self::$arrTagReplacement as $strKey=>$arrValue) {
				if (!$arrAllowedTags || in_array($strKey, $arrAllowedTags)) {
					$arrPattern[] = $arrValue[0];
					$arrReplacement[] = $arrValue[1];
				}
			}
			
			return preg_replace($arrPattern, $arrReplacement, $strString);
		}
		
		
		/**
		 * Strips HTML tags and attributes from the data passed.
		 *
		 * @access public
		 * @param string $strString The string to clean
		 * @param array $arrAllowedTags The array of allowed tags (ie. <p>)
		 * @param array $arrDisabledTags The array of disabled tags (ie. <script>)
		 * @param array $arrDisabledAttributes The array of disabled attributes (ie. onclick - good for stripping javascript)
		 * @return string The cleaned string
		 * @static
		 */
		static public function cleanString($strString, $arrAllowedTags = array(), $arrDisabledTags = array('<script>'), $arrDisabledAttributes = array('onclick', 'ondblclick', 'onkeydown', 'onkeypress', 'onkeyup', 'onload', 'onmousedown', 'onmousemove', 'onmouseout', 'onmouseover', 'onmouseup', 'onunload')) {
			if (!empty($arrAllowedTags)) {
				$strString = strip_tags($strString, implode('', $arrAllowedTags));
			}
			
			if (!empty($arrDisabledTags)) {
				foreach ($arrDisabledTags as $intKey=>$strTag) {
					$arrDisabledTags[$intKey] = substr(trim($strTag), 1, -1);
				}
				$strString = preg_replace('/(<\/?' . implode('|', $arrDisabledTags) . '>)/iusU', '', $strString);
			}
			
			if (!empty($arrDisabledAttributes)) {
				$strString = preg_replace('/<(.*?)>/ie', "'<' . preg_replace(array('/javascript:[^\"\']*/i', '/(" . implode('|', $arrDisabledAttributes) . ")=[\"\'][^\"\']*[\"\']/i', '/\s+/'), array('', '', ' '), stripslashes('\\1')) . '>'", $strString);
			}
			
			return $strString;
		}
		
		
		/**
		 * Returns a formatted string of code with the tabs
		 * replaced with spaces.
		 *
		 * @access public
		 * @param string $strCode The code to format
		 * @param boolean $blnTags Whether to convert tabs to spaces
		 * @return string The formatted code
		 * @static
		 */
		static public function formatCode($strCode, $blnTabs = false) {
			$strCode = str_replace('<', '&lt;', $strCode);
			$strCode = str_replace('>', '&gt;', $strCode);
			if ($blnTabs) {
				$strCode = str_replace("\t", '&nbsp;&nbsp;&nbsp;&nbsp;', $strCode);
			}
			$strCode = str_replace(' ', '&nbsp;', $strCode);
			$strCode = nl2br(trim($strCode));
			return "<code>{$strCode}</code>";
		}
	}