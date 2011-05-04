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
		 * @param object $objUrl The URL object set up with the URL of the API call 
		 * @param boolean $blnInternal Whether special internal methods are allowed
		 * @return array An array with the success flag and the results
		 * @static
		 */
		static protected function request(Url $objUrl, $blnInternal = true) {
			CoreDebug::debug('Loading', $objUrl->getUrl());
			
			//determine if the user is logged in
			if ($objUserLogin = AppRegistry::get('UserLogin', false)) {
				$blnAuthenticated = $objUserLogin->isLoggedIn();
			}
			
			//count the number of errors
			$objError = AppRegistry::get('Error');
			$intStartErrors = count($objError->getErrors());
			
			//initialize and run the API method
			AppLoader::includeClass('php/core/', 'CoreApi');
			$objApi = new CoreApi($objUrl, !empty($blnAuthenticated), $blnInternal);
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
			if (strstr($strUrl, '?') !== false) {
				list($strUrl, $strQueryString) = explode('?', $strUrl);
				parse_str($strQueryString, $arrVariables);
			}
			
			$objUrl = clone AppRegistry::get('Url');
			$objUrl->init('GET', $strUrl, isset($arrVariables) ? $arrVariables : array());
			
			return self::request($objUrl, $blnInternal);
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
		static public function post($strUrl, $arrPost, $blnInternal = true) {
			$objUrl = clone AppRegistry::get('Url');
			$objUrl->init('POST', $strUrl, $arrPost);
			
			return self::request($objUrl, $blnInternal);
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
			$objUrl = clone AppRegistry::get('Url');
			$objUrl->init('PUT', $strUrl, array());
			
			return self::request($objUrl, $blnInternal);
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
			$objUrl = clone AppRegistry::get('Url');
			$objUrl->init('DELETE', $strUrl, array());
			
			return self::request($objUrl, $blnInternal);
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
		 * @param boolean $blnInternal Whether special internal methods are allowed
		 * @return mixed The result node
		 * @static
		 */
		static public function getResultNode($mxdArgs, $strNode, $blnSingle = false, $blnInternal = true) {
			if ($blnPost = is_array($mxdArgs)) {
				list($strUrl, $arrPost) = $mxdArgs;
			} else {
				$strUrl = $mxdArgs;
			}
		
			list($strBaseUrl, ) = explode('?', $strUrl);
			switch (substr($strBaseUrl, strrpos($strBaseUrl, '.') + 1)) {
				case 'json':
					if ($blnPost) {
						list($blnSuccess, $arrResult) = self::post($strUrl, $arrPost, $blnInternal);
					} else {
						list($blnSuccess, $arrResult) = self::get($strUrl, $blnInternal);
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
		 * @param boolean $blnInternal Whether special internal methods are allowed
		 * @return array The result nodes
		 * @static
		 */
		static public function getResultNodes($mxdArgs, $arrNodes, $blnAssociative = true, $blnInternal = true) {
			if ($blnPost = is_array($mxdArgs)) {
				list($strUrl, $arrPost) = $mxdArgs;
			} else {
				$strUrl = $mxdArgs;
			}
			
			list($strBaseUrl, ) = explode('?', $strUrl);
			switch (substr($strBaseUrl, strrpos($strBaseUrl, '.') + 1)) {
				case 'json':
					if ($blnPost) {
						list($blnSuccess, $arrResult) = self::post($strUrl, $arrPost, $blnInternal);
					} else {
						list($blnSuccess, $arrResult) = self::get($strUrl, $blnInternal);
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