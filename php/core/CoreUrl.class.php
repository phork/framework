<?php
	require_once('php/core/CoreObject.class.php');
	require_once('interfaces/Singleton.interface.php');
	require_once('interfaces/Url.interface.php');

	/**
	 * CoreUrl.class.php
	 * 
	 * The URL class parses and routes the URL. The base
	 * URL is the application path relative to the document
	 * root, and including the filename when not using mod
	 * rewrite (eg. /admin or index.php).
	 *
	 * This must be extended by an AppUrl class.
	 *
	 * Copyright 2006-2011, Phork Labs. (http://phorklabs.com)
	 *
	 * Licensed under The MIT License
	 * Redistributions of files must retain the above copyright notice.
	 *
	 * @author Elenor Collings <elenor@phork.org>
	 * @license http://www.opensource.org/licenses/mit-license.php The MIT License
	 * @package phork
	 * @subpackage core
	 * @abstract
	 */
	abstract class CoreUrl extends CoreObject implements Url {
	
		protected $strUrl;
		protected $strRoutedUrl;
		protected $strBaseUrl;
		protected $strQueryString;
		protected $strExtension;
		
		protected $blnInitialized;
		protected $arrSegments;
		protected $arrFilters;
		protected $arrRoutes;
		
		protected $strFilterDelimiter = '=';
		
		
		/**
		 * Initializes the URL data. Loads the current URL,
		 * routes as necessary, and parses the URL into
		 * segments and filters.
		 *
		 * @access public
		 * @param boolean $blnSkipRouting If this is set the URL won't be routed
		 */
		public function init($blnSkipRouting = false) {
			if (!$this->strUrl) {
				$this->loadUrl();
			}
			if (!$blnSkipRouting) {
				$this->routeUrl();
			}
			$this->parseUrl();
			
			$this->blnInitialized = true;
		}
		
		
		/**
		 * Checks for a PHP CLI script first, followed by the
		 * path info, and sets up the URL, the base URL, and 
		 * the complete URL which is the URL plus the base URL.
		 *
		 * @access protected
		 */
		protected function loadUrl() {
			if (empty($_SERVER['HTTP_HOST'])) {
				$this->strUrl = implode('/', array_slice($GLOBALS['argv'], 2));
			} else if (!empty($_SERVER['PATH_INFO'])) {
				$this->strUrl = $_SERVER['PATH_INFO'];
			} else {
				$this->strUrl = '/';
			}
			$this->strUrl = $this->cleanUrl($this->strUrl);
		}
		
		
		/**
		 * Cleans up the URL by replacing any double slashes with
		 * single slashes. Doesn't replace double slashes following
		 * a colon.
		 *
		 * @access protected
		 * @param string $strUrl The URL to clean
		 * @return string The cleaned URL
		 */
		protected function cleanUrl($strUrl) {
			return preg_replace('|(?<!:)/{2,}|', '/', $strUrl);
		}
		
		
		/**
		 * Splits the URL into an array of segments. If any segment
		 * contains (but does not start with) an equals sign then
		 * it will be set as a filter and removed from the URL
		 * segments array. For example: /page=1/
		 *
		 * @access protected
		 */
		protected function parseUrl() {
			$arrSegments = explode('/', ($this->strRoutedUrl ? $this->strRoutedUrl : $this->strUrl));
			$arrFilters = array();
			
			foreach ($arrSegments as $intKey=>$strSegment) {
				if ($intPos = strpos($strSegment, $this->strFilterDelimiter)) {
					$strFilter = substr($strSegment, 0, $intPos);
					$strValue = substr($strSegment, $intPos + 1);
					
					if (array_key_exists($strFilter, $arrFilters)) {
						if (!is_array($arrFilters[$strFilter])) {
							$arrFilters[$strFilter] = array($arrFilters[$strFilter]);
						}
						$arrFilters[$strFilter][] = $strValue;
					} else {
						$arrFilters[$strFilter] = $strValue;
					}
					$strSegment = null;
				}
				
				if (!$strSegment) {
					unset($arrSegments[$intKey]);
				}
			}
					
			$this->arrSegments = array_values($arrSegments);
			$this->arrFilters = $arrFilters;
			
			if (strpos($this->strUrl, '.')) {
				$arrExtSegments = explode('.', $this->strUrl);
				$this->strExtension = end($arrExtSegments);
			} else {
				$this->strExtension = null;
			}
		}
		
		
		/**
		 * Loads the routing configuration, checks for a
		 * re-routed URL and replaces any backreferences in
		 * the result.
		 *
		 * @access protected
		 */
		protected function routeUrl() {
			if ($this->arrRoutes) {
				foreach ($this->arrRoutes as $strPattern=>$strRoute) {
					if (preg_match("#{$strPattern}#", $this->strUrl, $arrMatches)) {
						if (preg_match_all('#\$([0-9+])#', $strRoute, $arrReplacements)) {
							foreach ($arrReplacements[1] as $intReplacement) {
								$strRoute = str_replace('$' . $intReplacement, !empty($arrMatches[$intReplacement]) ? $arrMatches[$intReplacement] : '', $strRoute); 
							}
							$strRoute = preg_replace('#//+#', '/', $strRoute);
						}
						$this->strRoutedUrl = $strRoute;
						break;
					}
				}
			}
		}
				
		
		/*****************************************/
		/**     GET & SET METHODS               **/
		/*****************************************/
		
		
		/**
		 * Returns the URL excluding the base URL.
		 *
		 * @access public
		 * @return string The URL
		 */
		public function getUrl() {
			$this->blnInitialized || $this->init();
			return $this->strUrl;
		}
		
		
		/**
		 * Sets the URL in case it needs to be different
		 * from the actual URL. Resets the routed URL in
		 * the event that this is overwriting an existing
		 * URL.
		 *
		 * @access public
		 * @param string $strUrl The URL
		 */
		public function setUrl($strUrl) {
			if (strstr($strUrl, '?')) {
				list($strUrl, $strQueryString) = explode('?', $strUrl);
				parse_str($strQueryString, $_GET);
			} else {
				$_GET = array();
			}
			$this->strUrl = $strUrl;
			$this->strRoutedUrl = null;
		}
		
		
		/**
		 * Returns the base URL.
		 *
		 * @access public
		 * @return string The base URL
		 */
		public function getBaseUrl() {
			return $this->strBaseUrl;
		}
		
		
		/**
		 * Sets the base URL.
		 *
		 * @access public
		 * @return string The base URL
		 */
		public function setBaseUrl($strBaseUrl) {
			$this->blnInitialized = false;
			$this->strBaseUrl = $strBaseUrl;
		}
		
		
		/**
		 * Returns the URL of the current page including
		 * the base URL.
		 *
		 * @access public
		 * @param boolean $blnQueryString Whether to include the query string
		 * @param boolean $blnCleanUrl Whether to clean the URL data
		 * @return string The current URL
		 */
		public function getCurrentUrl($blnQueryString = true, $blnCleanUrl = true) {
			$this->blnInitialized || $this->init();
			
			$strUrl = $this->strBaseUrl . $this->strUrl;
			if ($blnQueryString && count($_GET)) {
				$strAmp = $blnCleanUrl ? '&amp;' : '&';
				
				$strUrl .= (strpos($strUrl, '?') !== false ? $strAmp : '?');
				$strUrl .= http_build_query($_GET, null, $strAmp);
			}
			
			return $strUrl;
		}
		
		
		/**
		 * Returns the file extension of the current page
		 * if there is one.
		 *
		 * @access public
		 * @return string The file extension
		 */
		public function getExtension() {
			return $this->strExtension;
		}
		
		
		/**
		 * Returns the URL segment at the position passed.
		 *
		 * @access public
		 * @param integer $intPosition The position of the segment to retrieve
		 * @return string The URL segment
		 */
		public function getSegment($intPosition) {
			if (is_array($arrSegments = $this->getSegments())) {
				if (array_key_exists($intPosition, $arrSegments)) {
					return $arrSegments[$intPosition];
				}
			}
		}
		
		
		/**
		 * Returns all the URL segments. If the URL hasn't
		 * been parsed it does that here.
		 *
		 * @access public
		 * @return array The URL segments
		 */
		public function getSegments() {
			$this->blnInitialized || $this->init();
			return $this->arrSegments;
		}
		
		
		/**
		 * Returns the value of the URL filter if it exists.
		 *
		 * @access public
		 * @param string $strFilter The filter to retrieve
		 * @return mixed The filter value
		 */
		public function getFilter($strFilter) {
			if (array_key_exists($strFilter, $arrFilters = $this->getFilters())) {
				return $arrFilters[$strFilter];
			}
		}
		
		
		/**
		 * Returns all the URL filters. If the URL hasn't
		 * been parsed it does that here.
		 *
		 * @access public
		 * @return array The URL filters
		 */
		public function getFilters() {
			$this->blnInitialized || $this->init();
			return $this->arrFilters;
		}
		
		
		/**
		 * Sets the routes that determine which controller
		 * to use based on the URL.
		 *
		 * @access public
		 * @param array $arrRoutes The routes
		 */
		public function setRoutes($arrRoutes) {
			$this->arrRoutes = $arrRoutes;
		}
	}