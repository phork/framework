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
	
		protected $strMethod;
		protected $strUrl;
		protected $strRoutedUrl;
		protected $strBaseUrl;
		protected $strExtension;
		protected $blnEndSlash;
		
		protected $blnInitialized;
		protected $arrSegments;
		protected $arrFilters;
		protected $arrVariables;
		protected $arrRoutes;
		
		protected $strFilterDelimiter = '=';
		
		
		/**
		 * Sets up the base URL. The rest of the set up is
		 * handled by the init() method.
		 *
		 * @access public
		 * @param string $strBaseUrl The base path of the system relative to the doc root
		 * @param string $blnEndSlash Whether to force the URL to end with a slash 
		 */
		public function __construct($strBaseUrl, $blnEndSlash = false) {
			$this->strBaseUrl = $strBaseUrl;
			$this->blnEndSlash = $blnEndSlash;
		}
		
		
		/**
		 * Initializes the URL data. Loads the current URL,
		 * routes as necessary, and parses the URL into
		 * segments and filters.
		 *
		 * @access public
		 * @param string $strMethod The request method (GET, POST, PUT, DELETE, HEAD)
		 * @param string $strUrl The URL of the request relative to the base URL
		 * @param array $arrVariables Any request variables (eg. override $_GET)
		 */
		public function init($strMethod = null, $strUrl = null, $arrVariables = null) {
			$this->blnInitialized = false;
			$this->strRoutedUrl = null;
			
			if ($strMethod !== null) {
				$this->strMethod = $strMethod;
			} else {
				$this->strMethod = $_SERVER['REQUEST_METHOD'];
			}
			
			if ($strUrl !== null) {
				$this->strUrl = $strUrl;
			} else if (!$this->strUrl) {
				$this->detectUrl();
			}
			
			if ($arrVariables !== null) {
				$this->arrVariables = $arrVariables;
			} else {
				$this->detectVariables();
			}
			
			$this->routeUrl();
			$this->parseUrl();
			$this->slashUrl();
			
			$this->blnInitialized = true;
		}
		
		
		/**
		 * Makes adjustments to account for the URL using
		 * a query string. This can be in either the format
		 * /index.php?/path/to/page/ if using mod rewrite or
		 * /index.php?url=/path/to/page/ if not using mod
		 * rewrite. When using the first format no variable
		 * should be passed. When using the second format
		 * the variable containing the URL should be passed
		 * (eg. url). This reset the $_GET array and removes
		 * any effect the URL may have had on it.
		 *
		 * @access public
		 * @param string $strVariable The variable name, if the URL isn't the full query string
		 */
		public function useQueryString($strVariable = null) {
			$strUrl = '/';
			$strQueryString = '';
			
			if (!empty($_SERVER['QUERY_STRING'])) {
				if ($strVariable) {
					if (preg_match('/(' . $strVariable . '=([^&]*))?&?(.*)/', $_SERVER['QUERY_STRING'], $arrMatches)) {
						if (!empty($arrMatches[2])) {
							$strUrl .= $arrMatches[2];
						}
						if (!empty($arrMatches[3])) {
							$strQueryString .= $arrMatches[3];
						}
					}
				} else {
					if ($intPos = strpos($_SERVER['QUERY_STRING'], '&')) {
						$strUrl .= substr($_SERVER['QUERY_STRING'], 0, $intPos);
						$strQueryString .= substr($_SERVER['QUERY_STRING'], $intPos + 1);
					} else if (empty($_SERVER['REQUEST_URI']) || !strstr($_SERVER['REQUEST_URI'], '?')) {
						$strUrl .= $_SERVER['QUERY_STRING'];
					} else {
						$strQueryString .= $_SERVER['QUERY_STRING'];
					}
				}
			}
			
			$this->strUrl = $strUrl;
			parse_str($strQueryString, $_GET);
		}
		
		
		/**
		 * Checks for a PHP CLI script first, followed by the
		 * path info, and sets up the URL.
		 *
		 * @access public
		 */
		protected function detectUrl() {
			if (empty($_SERVER['HTTP_HOST'])) {
				$strUrl = implode('/', array_slice($GLOBALS['argv'], 2));
			} else if (!empty($_SERVER['PATH_INFO'])) {
				$strUrl = $_SERVER['PATH_INFO'];
			} else if (!empty($_SERVER['REQUEST_URI'])) {
				$strUrl = $_SERVER['REQUEST_URI'];
			} else {
				$strUrl = '/';
			}
			$this->strUrl = $this->cleanUrl($strUrl);
		}
		
		
		/**
		 * Sets the request variables based on the request
		 * method.
		 *
		 * @access protected
		 */
		protected function detectVariables() {
			switch (strtolower($this->strMethod)) {
				case 'get':
					$this->arrVariables = $_GET;
					break;
					
				case 'post':
					$this->arrVariables = $_POST;
					break;
				
				case 'put':
					parse_str(file_get_contents('php://input'), $this->arrVariables);
					break;
					
				case 'delete':
					$this->arrVariables = array();
					break;
					
				case 'head':
					$this->arrVariables = $_GET;
					break;
			}
		}
		
		
		/**
		 * Cleans up the URL by replacing any double slashes
		 * with single slashes. Doesn't replace double slashes
		 * following a colon.
		 *
		 * @access protected
		 * @param string $strUrl The URL to clean
		 * @return string The cleaned URL
		 */
		protected function cleanUrl($strUrl) {
			return preg_replace('|(?<!:)/{2,}|', '/', trim($strUrl));
		}
		
		
		/**
		 * Adds a trailing slash to the URL if it doesn't
		 * have an extension.
		 *
		 * @access protected
		 */
		protected function slashUrl() {
			if ($this->blnEndSlash && !$this->strExtension && substr($this->strUrl, -1) != '/') {
				$this->strUrl .= '/';
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
				
		
		/**
		 * Splits the URL into an array of segments. If the URL has
		 * been routed then this uses the routed URL. If any segment
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
		
		
		/*****************************************/
		/**     GET & SET METHODS               **/
		/*****************************************/
		
		
		/**
		 * Returns the request method.
		 *
		 * @access public
		 * @return string The request method
		 */
		public function getMethod() {
			$this->blnInitialized || $this->init();
			return $this->strMethod;
		}
		
		
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
		 * Returns the base URL.
		 *
		 * @access public
		 * @return string The base URL
		 */
		public function getBaseUrl() {
			return $this->strBaseUrl;
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
		public function getCurrentUrl($blnQueryString = true, $blnCleanUrl = false) {
			$this->blnInitialized || $this->init();
			
			$strUrl = $this->strBaseUrl . $this->strUrl;
			if ($blnQueryString && strtolower($this->strMethod) == 'get' && count($this->arrVariables)) {
				$strAmp = $blnCleanUrl ? '&amp;' : '&';
				
				$strUrl .= (strpos($strUrl, '?') !== false ? $strAmp : '?');
				$strUrl .= http_build_query($this->arrVariables, null, $strAmp);
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
			$this->blnInitialized || $this->init();
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
			if (array_key_exists($intPosition, $arrSegments = $this->getSegments())) {
				return $arrSegments[$intPosition];
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
		 * Returns the value of the request variable if it
		 * exists.
		 *
		 * @access public
		 * @param string $strVariable The variable to retrieve
		 * @return mixed The variable value
		 */
		public function getVariable($strVariable) {
			if (array_key_exists($strVariable, $arrVariables = $this->getVariables())) {
				return $arrVariables[$strVariable];
			}
		}
		
		
		/**
		 * Returns all the request variables. If the URL hasn't
		 * been parsed it does that here.
		 *
		 * @access public
		 * @return array The URL filters
		 */
		public function getVariables() {
			$this->blnInitialized || $this->init();
			return $this->arrVariables;
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