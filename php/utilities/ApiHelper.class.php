<?php
	/**
	 * ApiHelper.class.php
	 *
	 * Calls local API methods by URL without having to go
	 * through an actual HTTP call by faking the URL object
	 * and calling the API controller directly.
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
	class ApiHelper {
	
		/**
		 * Spoofs an API request and retrieves the result without 
		 * having the the overhead of an extra HTTP request.
		 *
		 * @access protected
		 * @param string $strUrl The URL to get the API data from
		 * @param boolean $blnInternal Whether special internal methods are allowed
		 * @return array An array with the success flag and the results
		 * @static
		 */
		static protected function request($strUrl, $blnInternal = true) {
			CoreDebug::debug('Loading', $strUrl);
			
			//set up the URL object to contain the URL of the API call
			$objUrl = AppRegistry::get('Url');
			$objUrl->setUrl($strUrl);
			$objUrl->init();
			
			//determine if the user is logged in
			if ($objUserLogin = AppRegistry::get('UserLogin', false)) {
				$blnAuthenticated = $objUserLogin->isLoggedIn();
			}
			
			//count the number of errors
			$objError = AppRegistry::get('Error');
			$intStartErrors = count($objError->getErrors());
			
			//initialize and run the API method
			AppLoader::includeClass('php/core/', 'CoreApi');
			$objApi = new CoreApi(!empty($blnAuthenticated), $blnInternal);
			list(
				$blnSuccess, 
				$arrResult, 
				$intStatusCode
			) = $objApi->run();
			
			//if there were major errors but nothing added to the error log add a generic error
			$intEndErrors = count($objError->getErrors());
			if ($intStatusCode >= 400 && $intEndErrors <= $intStartErrors) {
				trigger_error(AppLanguage::translate('There was a fatal error'));
			}
			
			//reset the URL to the current URL
			$objUrl->setUrl(null);
			$objUrl->init();
			
			return array($blnSuccess, $arrResult, $intStatusCode);
		}
		
		
		/*****************************************/
		/**     SPOOF METHODS                   **/
		/*****************************************/
		
		
		/**
		 * Spoofs an API get and retrieves the result without 
		 * having the the overhead of an extra HTTP request.
		 *
		 * @access public
		 * @param string $strUrl The URL to get the API data from
		 * @param boolean $blnInternal Whether special internal methods are allowed
		 * @return array An array with the success flag and the results
		 * @static
		 */
		static public function get($strUrl, $blnInternal = true) {
		
			//back up the request method and any get data
			$strRequestMethod = $_SERVER['REQUEST_METHOD'];
			$arrGetBackup = $_GET;
		
			//fake the request method and get data
			$_SERVER['REQUEST_METHOD'] = 'GET';
			if ($strQueryString = substr(strstr($strUrl, '?'), 1)) {
				parse_str($strQueryString, $_GET);
			} else {
				$_GET = array();
			}
					
			//make a standard JSON call
			$arrResult = self::request($strUrl, $blnInternal);
			
			//restore the request method and get data
			$_SERVER['REQUEST_METHOD'] = $strRequestMethod;
			$_GET = $arrGetBackup;
			
			return $arrResult;
		}
		
		
		/**
		 * Spoofs an API post and retrieves the result without 
		 * having the the overhead of an extra HTTP request.
		 * 
		 * @access public
		 * @param string $strUrl The URL to get the API data from
		 * @param array $arrPost The data to post to the API
		 * @param boolean $blnInternal Whether special internal methods are allowed
		 * @return array An array with the success flag and the results
		 * @static
		 */
		static public function post($strUrl, &$arrPost, $blnInternal = true) {
			
			//back up the request method and any post data
			$strRequestMethod = $_SERVER['REQUEST_METHOD'];
			$arrPostBackup = $_POST;
		
			//fake the request method and post data
			$_SERVER['REQUEST_METHOD'] = 'POST';
			$_POST = $arrPost;
		
			//make a standard JSON call
			$arrResult = self::request($strUrl, $blnInternal);
			
			//restore the request method and post data
			$_SERVER['REQUEST_METHOD'] = $strRequestMethod;
			$_POST = $arrPostBackup;
			
			return $arrResult;
		}
		
		
		/**
		 * Spoofs an API put and retrieves the result without 
		 * having the the overhead of an extra HTTP request.
		 *
		 * @access public
		 * @param string $strUrl The URL to get the API data from
		 * @param boolean $blnInternal Whether special internal methods are allowed
		 * @return array An array with the success flag and the results
		 * @static
		 */
		static public function put($strUrl, $blnInternal = true) {
			
			//back up the request method
			$strRequestMethod = $_SERVER['REQUEST_METHOD'];
			
			//fake the request method 
			$_SERVER['REQUEST_METHOD'] = 'PUT';
			
			//make a standard JSON call
			$arrResult = self::request($strUrl, $blnInternal);
			
			//restore the request method
			$_SERVER['REQUEST_METHOD'] = $strRequestMethod;
			
			return $arrResult;
		}
		
		
		/**
		 * Spoofs an API delete and retrieves the result without 
		 * having the the overhead of an extra HTTP request.
		 *
		 * @access public
		 * @param string $strUrl The URL to get the API data from
		 * @param boolean $blnInternal Whether special internal methods are allowed
		 * @return array An array with the success flag and the results
		 * @static
		 */
		static public function delete($strUrl, $blnInternal = true) {
		
			//back up the request method
			$strRequestMethod = $_SERVER['REQUEST_METHOD'];
			
			//fake the request method 
			$_SERVER['REQUEST_METHOD'] = 'DELETE';
			
			//make a standard JSON call
			$arrResult = self::request($strUrl, $blnInternal);
			
			//restore the request method
			$_SERVER['REQUEST_METHOD'] = $strRequestMethod;
			
			return $arrResult;
		}
		
		
		/*****************************************/
		/**     RESULT NODE METHODS             **/
		/*****************************************/
		
		
		/**
		 * Parses the main result node out of a successful
		 * result and returns it. The $mxdArgs param can either
		 * be a URL or an array with the URL and the post args.
		 *
		 * @access public
		 * @param string $mxdArgs The URL (and optionally POST data) to retrieve the results from
		 * @param string $strNode The result node to return
		 * @param boolean $blnSingle If this flag is set only the first result will be returned
		 * @return mixed The result node
		 * @static
		 */
		static public function getResultNode($mxdArgs, $strNode, $blnSingle = false) {
			if ($blnPost = is_array($mxdArgs)) {
				list($strUrl, $arrPost) = $mxdArgs;
			} else {
				$strUrl = $mxdArgs;
			}
		
			list($strBaseUrl, ) = explode('?', $strUrl);
			switch (substr($strBaseUrl, strrpos($strBaseUrl, '.') + 1)) {
				case 'json':
					if ($blnPost) {
						list($blnSuccess, $arrResult) = self::post($strUrl, $arrPost);
					} else {
						list($blnSuccess, $arrResult) = self::get($strUrl);
					}
					break;
					
				default:
					trigger_error(AppLanguage::translate('Only JSON results are supported'));
					break;
			}
			
			if ($blnSuccess && !empty($arrResult)) {
				if (!empty($arrResult[$strNode])) {
					if ($blnSingle) {
						$arrReturn = array_shift($arrResult[$strNode]);
					} else {
						$arrReturn = $arrResult[$strNode];
					}
					return $arrReturn;
				}
			}
		}
		
		
		/**
		 * Parses the multiple nodes out of a result and returns
		 * them. This does not require that the result be successful.
		 * The $mxdArgs param can either be a URL or an array with
		 * the URL and the post args.
		 *
		 * @access public
		 * @param string $mxdArgs The URL (and optionally POST data) to retrieve the results from
		 * @param array $arrNodes The result nodes to return
		 * @param boolean $blnAssociative Whether to return an associative array
		 * @return array The result nodes
		 * @static
		 */
		static public function getResultNodes($mxdArgs, $arrNodes, $blnAssociative = true) {
			if ($blnPost = is_array($mxdArgs)) {
				list($strUrl, $arrPost) = $mxdArgs;
			} else {
				$strUrl = $mxdArgs;
			}
			
			list($strBaseUrl, ) = explode('?', $strUrl);
			switch (substr($strBaseUrl, strrpos($strBaseUrl, '.') + 1)) {
				case 'json':
					if ($blnPost) {
						list($blnSuccess, $arrResult) = self::post($strUrl, $arrPost);
					} else {
						list($blnSuccess, $arrResult) = self::get($strUrl);
					}
					break;
					
				default:
					trigger_error(AppLanguage::translate('Only JSON results are supported'));
					break;
			}
			
			$arrReturn = array();
			foreach ($arrNodes as $intKey=>$strNode) {
				$arrReturn[$blnAssociative ? $strNode : $intKey] = !empty($arrResult[$strNode]) ? $arrResult[$strNode] : null;
			}
			return $arrReturn;
		}
	}