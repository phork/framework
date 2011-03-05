<?php
	/**
	 * ConcatHelper.class.php
	 *
	 * Returns the HTML to include the concatenated CSS
	 * and Javascript. If the no concat flag is set this
	 * just outputs a tag for each file.
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
	class ConcatHelper {
		
		protected $blnConcat;
		
		
		/**
		 * Determines whether concatenation should be
		 * used based on the config or a GET override.
		 *
		 * @access public
		 */
		public function __construct() {
			$this->blnConcat = !AppConfig::get('NoConcat') && empty($_GET['raw']);
		}
		
	
		/**
		 * Returns the HTML tag for embedding the concatenated
		 * CSS files.
		 *
		 * @access public
		 * @param array $arrUrls The array of CSS URLs to concat
		 * @param string $strMedia The media for which the CSS is appropriate (print, screen, etc.)
		 * @param string $strOutput The name of the generated CSS file
		 * @return string The HTML tag
		 */
		public function css(array $arrUrls, $strMedia = 'all', $strOutput = 'output') {
			$strReturn = '';
			if ($arrUrls) {
				if (!$this->blnConcat || count($arrUrls) == 1) {
					foreach ($arrUrls as $strUrl) {
						if ($strUrl) {
							$strReturn .= sprintf('<link rel="stylesheet" type="text/css" href="%s" media="%s" />', $strUrl, $strMedia) . "\n";
						}
					}
				} else {
					$strReturn = sprintf('<link rel="stylesheet" type="text/css" href="%s/concat/css/%s/%s/%s.css" media="%s" />', AppConfig::get('BaseUrl'), AppConfig::get('CssVersion'), urlencode(base64_encode(implode(',', $arrUrls))), $strOutput, $strMedia) . "\n";
				}
			}
			return $strReturn;
		}
		
		
		/**
		 * Returns the HTML tag for embedding the concatenated
		 * Javascript files.
		 *
		 * @access public
		 * @param array $arrUrls The array of Javascript URLs to concat
		 * @param string $strOutput The name of the generated Javascript file
		 * @return string The HTML tag
		 */
		public function js(array $arrUrls, $strOutput = 'output') {
			$strReturn = '';
			if ($arrUrls) {
				if (!$this->blnConcat || count($arrUrls) == 1) {
					foreach ($arrUrls as $strUrl) {
						$strReturn .= sprintf('<script type="text/javascript" src="%s"></script>', $strUrl) . "\n";
					}
				} else {
					foreach ($arrUrls as $intKey=>$strUrl) {
						if ($intPos = strpos($strUrl, '?')) {
							if (isset($strQueryString)) {
								$strQueryString .= '&' . substr($strUrl, $intPos + 1);
							} else {
								$strQueryString = substr($strUrl, $intPos);
							}
							$arrUrls[$intKey] = substr($strUrl, 0, $intPos);
						}
					}
					$strReturn = sprintf('<script type="text/javascript" src="%s/concat/js/%s/%s/%s.js%s"></script>', AppConfig::get('BaseUrl'), AppConfig::get('JsVersion'), urlencode(base64_encode(implode(',', $arrUrls))), $strOutput, isset($strQueryString) ? $strQueryString : '') . "\n";
				}
			}
			return $strReturn;
		}
		
		
		/**
		 * Returns whether the flag to concat is set.
		 *
		 * @access public
		 * @return boolean True if concatenating
		 */
		public function getConcat() {
			return $this->blnConcat;
		}
	}