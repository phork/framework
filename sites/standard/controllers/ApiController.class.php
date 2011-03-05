<?php
	require_once('php/core/CoreControllerLite.class.php');
	
	/**
	 * ApiController.class.php
	 * 
	 * This controller handles all the API calls. It dispatches
	 * to the API methods after authenticating the user.
	 * 
	 * Copyright 2006-2010, Phork Labs. (http://phorklabs.com)
	 *
	 * @author Elenor Collings <elenor@phork.org>
	 * @package phork-standard
	 * @subpackage controllers
	 */
	class ApiController extends CoreControllerLite {
	
		protected $blnDebug = false;
		protected $strFormat;
		protected $blnSuccess;
		protected $arrResult;
		protected $intStatusCode;
		protected $objApi;
		
		protected $blnEncryptInput = false;
		protected $blnEncryptOutput = false;
		
		
		/**
		 * Dispatches processing to the core API handler.
		 *
		 * @access public
		 */
		public function run() {
			$this->strFormat = AppRegistry::get('Url')->getExtension();
			try {
				if ($this->blnEncryptInput || $this->blnEncryptOutput) {
					AppLoader::includeExtension('phpseclib/Crypt/', 'AES', true);
					
					if ($this->blnEncryptInput && !empty($_SERVER['QUERY_STRING'])) {
						$objAes = new Crypt_AES();
						$objAes->setKey(AppConfig::get('AesKey'));
						parse_str($objAes->decrypt(base64_decode($_SERVER['QUERY_STRING'])), $_GET);
					}
				}
				
				AppLoader::includeClass('php/core/', 'CoreApi');
				$this->objApi = new CoreApi($this->authenticate(), false);
				list(
					$this->blnSuccess, 
					$this->arrResult, 
					$this->intStatusCode
				) = $this->objApi->run();
				
				$this->display();
			} catch (Exception $objException) {
				trigger_error(AppLanguage::translate('Fatal error at runtime'));
				$this->error();
			}
		}
		
		
		/**
		 * Authenticates a user for the non-public API calls.
		 * using the server auth vars.
		 *
		 * @access protected
		 * @return boolean True if the user was authenticated
		 */
		protected function authenticate() {
			if (!AppConfig::get('ApiInternal', false)) {
				if (!empty($_SERVER['PHP_AUTH_USER']) && !empty($_SERVER['PHP_AUTH_PW'])) {
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
			AppDisplay::getInstance()->setStatusCode($this->intStatusCode ? $this->intStatusCode : 200);
			AppDisplay::getInstance()->appendString('content', $strContent);
		}
		
		
		/**
		 * Encodes and displays a fatal error.
		 *
		 * @access public
		 * @param integer $intErrorCode The HTTP status code
		 */
		public function error($intErrorCode = 500) {
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
				'status'	=> $blnSuccess ? 'success' : 'error'
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
					$strEncoded = json_encode($arrResult);
					break;
					
				case 'json':
				default:
					AppDisplay::getInstance()->appendHeader('Content-type: application/json');
					$strEncoded = json_encode($arrResult);
					break;
			}
			
			if (empty($_GET['raw'])) {
				$strEncoded = preg_replace('/\s{2,}/', ' ', $strEncoded);
			}
			
			if ($this->blnEncryptOutput && class_exists('Crypt_AES', false)) {
				$objAes = new Crypt_AES();
				$objAes->setKey(AppConfig::get('AesKey'));
				$strEncoded = base64_encode($objAes->encrypt($strEncoded));
			}
			
			CoreDebug::debug($strEncoded);
			return $strEncoded;
		}	
	}