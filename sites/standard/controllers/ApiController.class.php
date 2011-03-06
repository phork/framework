<?php
	require_once('php/core/CoreControllerLite.class.php');
	
	/**
	 * ApiController.class.php
	 * 
	 * This controller handles all the API calls. It dispatches
	 * to the API methods after authenticating the user.
	 * 
	 * Copyright 2006-2011, Phork Labs. (http://phorklabs.com)
	 *
	 * Licensed under The MIT License
	 * Redistributions of files must retain the above copyright notice.
	 *
	 * @author Elenor Collings <elenor@phork.org>
	 * @license http://www.opensource.org/licenses/mit-license.php The MIT License
	 * @package phork
	 * @subpackage controllers
	 */
	class ApiController extends CoreControllerLite {
	
		protected $blnDebug = false;
		protected $strFormat;
		protected $blnSuccess;
		protected $arrResult;
		protected $intStatusCode;
		protected $objApi;
		
		
		/**
		 * Dispatches processing to the core API handler.
		 *
		 * @access public
		 */
		public function run() {
			$this->strFormat = AppRegistry::get('Url')->getExtension();
			try {
				AppLoader::includeClass('php/core/', 'CoreApi');
				$this->objApi = new CoreApi($this->authenticate(), false);
				list(
					$this->blnSuccess, 
					$this->arrResult, 
					$this->intStatusCode
				) = $this->objApi->run();
				
				$this->display();
			} catch (Exception $objException) {
				if (AppConfig::get('ErrorVerbose')) {
					trigger_error($objException->getBacktrace());
				} else {
					trigger_error(AppLanguage::translate('Fatal error at runtime'));
				}
				$this->error();
			}
		}
		
		
		/**
		 * Authenticates a user for the non-public API calls.
		 * using the server auth vars. If a session ID was
		 * passed in the query string that matches the user's
		 * current session that will also authenticate them.
		 * The session ID is required so that a malicious
		 * user can't send an already logged in user to the
		 * API to do something unauthorized.
		 *
		 * @access protected
		 * @return boolean True if the user was authenticated
		 */
		protected function authenticate() {
			if (!AppConfig::get('ApiInternal', false)) {
				if (!empty($_REQUEST['sid']) && $_REQUEST['sid'] == session_id()) {
					return true;
				} else if (!empty($_SERVER['PHP_AUTH_USER']) && !empty($_SERVER['PHP_AUTH_PW'])) {
					AppConfig::set('NoLoginCookie', true);
					$objUserLogin = AppRegistry::get('UserLogin');
					if ($objUserLogin->handleFormLogin($_SERVER['PHP_AUTH_USER'], $_SERVER['PHP_AUTH_PW'])) {
						return true;
					}
				}
			}
		}
		
		
		/**
		 * Encodes and displays the result returned from the
		 * API handler. Has special handling for jsonp calls
		 * to wrap the data with a callback function.
		 *
		 * @access protected
		 * @param boolean $blnSuccess True on success
		 * @param array $arrResult The results to encode and display
		 * @param integer $intStatusCode The result status code
		 */
		protected function display() {
			$strContent = $this->encode($this->blnSuccess, $this->arrResult);
			if ($this->strFormat == 'jsonp' && !empty($_GET['callback'])) {
				$strCallback = preg_replace('/[^a-z0-9_\.]/i', '', $_GET['callback']);
				$strContent = $strCallback . ' = function() { return "'
				            . str_replace('"', '\"', str_replace('\"', '\\\"', $strContent))
				            . '"; }'
				;
			}
			
			$objDisplay = AppDisplay::getInstance();
			$objDisplay->setStatusCode($this->intStatusCode ? $this->intStatusCode : 200);
			$objDisplay->appendString('content', $strContent);
		}
		
		
		/**
		 * Encodes and displays a fatal error.
		 *
		 * @access public
		 * @param integer $intErrorCode The HTTP status code
		 * @param string $strException The exception to throw
		 */
		public function error($intErrorCode = null, $strException = null) {
			if ($strException) {
				trigger_error($strException);
			}
		
			AppDisplay::getInstance()->setStatusCode($intErrorCode);
			AppDisplay::getInstance()->appendString('content', $this->encode(false, array(
				'errors' => AppRegistry::get('Error')->getErrors()
			)));
			exit;
		}
		
		
		/**
		 * Encodes the display result based on the extension.
		 * Currently supports JSON and XML. If the output is
		 * XML this checks for a method named getXmlNodeName
		 * in the API object to name generic child nodes that
		 * would otherwise be named generically.
		 *
		 * @access protected
		 * @param boolean $blnSuccess True if the output is a result of a successful operation
		 * @param array $arrResult The result array to encode
		 * @return string The JSON encoded string
		 */
		protected function encode($blnSuccess, array $arrResult = array()) {
			if ($this->blnDebug) {
				global $objTimer;
				$arrResult['timer'] = $objTimer->getTime();
			}
			
			$arrResult = array_merge(array(
				'status' => $blnSuccess ? 'success' : 'error'
			), $arrResult);
			
			if ($arrAlerts = CoreAlert::flushAlerts()) {
				$arrResult['alerts'] = $arrAlerts;
			}
		
			switch ($this->strFormat) {
				case 'xml':
					AppDisplay::getInstance()->appendHeader('Content-type: text/xml');
					AppLoader::includeUtility('XmlBuilder');
					$mxdNodeNameCallback = method_exists($objApi = $this->objApi->getDelegate(), 'getXmlNodeName') ? array($objApi, 'getXmlNodeName') : null;
					$objXmlBuilder = new XmlBuilder($arrResult, null, 'root', 'item', $mxdNodeNameCallback);
					$strEncoded = $objXmlBuilder->getXml();
					break;
					
				case 'jsonp':
					AppLoader::includeUtility('JsonHelper');
					$strEncoded = JsonHelper::encode($arrResult);
					break;
					
				case 'json':
				default:
					AppDisplay::getInstance()->appendHeader('Content-type: application/json');
					AppLoader::includeUtility('JsonHelper');
					$strEncoded = JsonHelper::encode($arrResult);
					break;
			}
			
			if (empty($_GET['raw'])) {
				$strEncoded = preg_replace('/\s{2,}/', ' ', $strEncoded);
			}
			
			return $strEncoded;
		}	
	}