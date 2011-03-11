<?php
	require_once('php/core/CoreObject.class.php');
	
	/**
	 * CoreApi.class.php
	 * 
	 * This is the base class for all API calls. It works nearly
	 * the same as a controller however it returns the results
	 * in an array instead of outputting them. This allows for
	 * the results to then be displayed by the ApiController or
	 * for this to be called internally.
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
	 */
	class CoreApi extends CoreObject {
		
		protected $strMethodPrefix = 'handle';
		
		protected $blnAuthenticated;
		protected $blnInternal;
		protected $arrParams;
		protected $strFormat;
		
		protected $intStatusCode = 200;
		protected $blnSuccess;
		protected $arrResult;
		
		protected $objDelegate;
		
		
		/**
		 * Sets up the flags that determine whether this is an
		 * internal API which will grant additional permissions 
		 * and whether the user is authenticated.
		 *
		 * @access public
		 * @param boolean $blnAuthenticated Whether the user is authenticated
		 * @param boolean $blnInternal Whether this is an internal API call
		 */
		public function __construct($blnAuthenticated, $blnInternal = false) {
			$this->blnAuthenticated = $blnAuthenticated;
			$this->blnInternal = $blnInternal;
		}
		
		
		/**
		 * Determines the params, the page format based on the
		 * URL extension, and whether the user is authenticated.
		 * Then hands off processing to the handler function.
		 *
		 * @access public
		 * @return array The result data either to be encoded or handled as is
		 */
		public function run() {
			if (get_class($this) == __CLASS__ && count($arrSegments = AppRegistry::get('Url')->getSegments()) > 2) {
				AppLoader::includeApi($strApi = ucfirst($arrSegments[1]) . 'Api');
				$this->objDelegate = new $strApi($this->blnAuthenticated, $this->blnInternal);
				return $this->objDelegate->run();
			} else {
				$arrCurrentAlerts = CoreAlert::flushAlerts();
				
				$this->arrResult = array();
				$this->strFormat = AppRegistry::get('Url')->getExtension();
				switch (strtolower($_SERVER['REQUEST_METHOD'])) {
					case 'get':
						$this->arrParams = $_GET;
						break;
						
					case 'post':
						$this->arrParams = $_POST;
						break;
					
					case 'put':
						parse_str(file_get_contents('php://input'), $this->arrParams);
						break;
						
					case 'delete':
						$this->arrParams = array();
						break;
						
					case 'head':
						$_SERVER['REQUEST_METHOD'] = 'GET';
						$this->arrParams = $_GET;
						break;
				}
				$this->handle();
				
				if ($arrApiAlerts = CoreAlert::flushAlerts()) {
					$this->arrResult['alerts'] = $arrApiAlerts;
				}
				CoreAlert::setAlerts($arrCurrentAlerts, true);
				
				return array(
					$this->blnSuccess, 
					$this->arrResult, 
					$this->intStatusCode
				);
			}
		}
		
		
		/**
		 * Verifies that the actual request type matches the
		 * request type passed.
		 *
		 * @access protected
		 * @param string $strRequestType The required request type (GET, PUT, POST, DELETE)
		 * @param boolean $blnInternalLeniency If this is true the request always verifies
		 * @return boolean True on success
		 */
		protected function verifyRequest($strRequestType, $blnInternalLeniency = false) {
			if (!($blnResult = (strtolower($_SERVER['REQUEST_METHOD']) == strtolower($strRequestType)) || ($blnInternalLeniency && $this->blnInternal))) {
				trigger_error(AppLanguage::translate('Invalid request method - %s required', $strRequestType));
			}
			return $blnResult;
		}
		
		
		/**
		 * Maps the API method to a method within this
		 * controller and returns the response.
		 *
		 * @access protected
		 */
		protected function handle() {
			$arrHandlers = array(
				'batch'	=> 'GetBatch'
			);
			
			$strSegment = str_replace('.' . $this->strFormat, '', AppRegistry::get('Url')->getSegment(1));
			if (!empty($arrHandlers[$strSegment])) {
				$strMethod = $this->strMethodPrefix . $arrHandlers[$strSegment];
				$this->$strMethod();
			} else {
				trigger_error(AppLanguage::translate('Invalid API method'));
				$this->error(404);
			}
		}
		
		
		/**
		 * Returns an error response.
		 *
		 * @access public
		 * @param integer $intErrorCode The HTTP status code
		 */
		public function error($intErrorCode = 500) {
			$this->blnSuccess = false;
			$this->intStatusCode = $intErrorCode;
			$this->arrResult = array(
				'errors' => AppRegistry::get('Error')->getErrors()
			);
		}
		
		
		/*****************************************/
		/**     HANDLER METHODS                 **/
		/*****************************************/
		
		
		/**
		 * Handles batch processing. Multiple GET API calls can be
		 * called at once. The request data must be in JSON format.
		 * Batch API calls can't take advantage of the internal
		 * flag.
		 *
		 * @access protected
		 */
		protected function handleGetBatch() {
			if (!empty($this->arrParams['calls'])) {
				if ($arrCalls = json_decode($this->arrParams['calls'], true)) {
					AppLoader::includeUtility('ApiHelper');
					foreach ($arrCalls as $mxdKey=>$arrCall) {
						$strKey = isset($arrCall['key']) ? $arrCall['key'] : $mxdKey;
						if (!empty($arrCall['request']) && !empty($arrCall['url'])) {
							switch (strtolower($arrCall['request'])) {
								case 'get':
									list(
										$blnResult, 
										$arrResult[$strKey]['data'], 
										$arrResult[$strKey]['status']
									) = ApiHelper::get($arrCall['url'], false);
									break;
									
								case 'post':
									list(
										$blnResult, 
										$arrResult[$strKey]['data'], 
										$arrResult[$strKey]['status']
									) = ApiHelper::post($arrCall['url'], $arrCall['args'], false);
									break;
									
								case 'put':
									list(
										$blnResult, 
										$arrResult[$strKey]['data'], 
										$arrResult[$strKey]['status']
									) = ApiHelper::put($arrCall['url'], false);
									break;
									
								case 'delete':
									list(
										$blnResult, 
										$arrResult[$strKey]['data'], 
										$arrResult[$strKey]['status']
									) = ApiHelper::delete($arrCall['url'], false);
									break;
							}
						} else {
							trigger_error(AppLanguage::translate('Missing request type and/or URL'));
							$this->error();
						}
					}
					
					$this->blnSuccess = true;
					$this->arrResult = array(
						'batched' => isset($arrResult) ? $arrResult : array()
					);
				} else {
					trigger_error(AppLanguage::translate('Invalid API batch definitions'));
					$this->error(400);
				}
			} else {
				trigger_error(AppLanguage::translate('Missing API batch definitions'));
				$this->error(400);
			}
		}	
		
		
		/*****************************************/
		/**     GET & SET METHODS               **/
		/*****************************************/
		
		
		/**
		 * Returns the delegate API object or self if nothing
		 * has been delegated.
		 *
		 * @access public
		 * @return object The API object
		 */
		public function getDelegate() {
			return $this->objDelegate ? $this->objDelegate : $this;
		}
		
		
		/**
		 * Formats an XML node name. This is to prevent child
		 * nodes being named with a generic name.
		 *
		 * @access public
		 * @param string $strNode The name of the node to potentially format
		 * @param string $strParentNode The name of the parent node
		 * @return string The formatted node name
		 */
		public function getXmlNodeName($strNode, $strParentNode) {
			switch ($strParentNode) {
				case 'batched':
					$strNode = 'result';
					break;
			}
			return $strNode;
		}	
	}