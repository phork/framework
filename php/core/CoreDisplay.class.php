<?php
	require_once('php/core/CoreObject.class.php');
	require_once('interfaces/Singleton.interface.php');
	
	/**
	 * CoreDisplay.class.php
	 *
	 * The display class is used to buffer and output
	 * the content. It's also used to cache the content
	 * as necessary. If buffering is turned on then the 
	 * the content nodes won't be displayed right away.
	 * They will be stored in the object and can be
	 * rearranged and will be displayed when the object
	 * has been destroyed.
	 *
	 * This is a singleton class and therefore it must
	 * be instantiated using the getInstance() method.
	 *
	 * This must be extended by an AppDisplay class.
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
	abstract class CoreDisplay extends CoreObject implements Singleton {
		
		static protected $objInstance;
		
		protected $arrHeaders;
		protected $arrNodeList;
	 	protected $arrNodeOrder;
	 	protected $strOutput;
		
		protected $blnBuffer;
		protected $intStatusCode;
		
		
		/**
		 * The constructor can't be public for a singleton.
		 *
		 * @access protected
		 */
		protected function __construct() {
			$this->clear();
		}
		
		
		/**
		 * Outputs the page if it hasn't been output already.
		 * Singleton destructors must be public.
		 *
		 * @access public
		 */
		public function __destruct() {
			$this->output();
		}
		
		
		/** 
		 * Returns the instance of the singleton object. If
		 * it doesn't exist it instantiates it.
		 *
		 * @access public
		 * @return object The instance of the display object
		 * @static
		 */
		static public function getInstance() {
			if (!self::$objInstance) {
				self::$objInstance = new AppDisplay();
			}
			return self::$objInstance;
		}
		
		
		/**
		 * Clears out all the data added to the display object
		 * so far. Useful for fatal errors.
		 *
		 * @access public
		 */
		public function clear() {
			$this->arrHeaders = array();
			$this->arrNodeList = array();
			$this->arrNodeOrder = array();
			$this->strOutput = null;
		}
		
		
		/**
		 * If there's an output buffer this ends it, sends any
		 * necessary headers, builds the page, and displays the
		 * page contents. This can be called explicitly to force
		 * output the page but it's also called from the destructor.
		 *
		 * @access public
		 */
		public function output() {
			AppEvent::run('display.pre-headers');	
			$this->sendHeaders();
			
			if ($this->blnBuffer) {
				$this->setBuffer(false);
			}
			$this->build();
			
			AppEvent::run('display.pre-output');
			print $this->strOutput;
			$this->strOutput = null;
		}
		
		
		/**
		 * Adds a header to be sent before the page is output.
		 * If output buffering isn't turned on and the headers
		 * haven't been sent it sends the header right away.
		 *
		 * @access public
		 * @param string $strHeader The header to send
		 */
		public function appendHeader($strHeader) {
			$this->arrHeaders[] = $strHeader;
			if (!$this->blnBuffer) {
				$this->sendHeaders();
			}
		}
		
		
		/**
		 * Sends the headers if they haven't already been sent.
		 * After a header has been sent it's removed from the
		 * headers array.
		 *
		 * @access public
		 */
		public function sendHeaders() {
			if (!empty($this->arrHeaders)) {
				if (!headers_sent()) {
					foreach ($this->arrHeaders as $intKey=>$strHeader) {
						header($strHeader);
						unset($this->arrHeaders[$intKey]);
					}
				} else {
					trigger_error(AppLanguage::translate('The headers have already been sent'));
				}
			}
		}
		
		
		/**
		 * Includes a template file. If output buffering isn't
		 * turned on the template is displayed right away, otherwise
		 * it's added to the node list.
		 *
		 * @access public
		 * @param string $strNode The name of the node
		 * @param string $strFilePath The validated filepath of the file to include
		 * @param array $arrTemplateVars The variables that should be available to the template
		 */
		public function appendTemplate($strNode, $strFilePath, $arrTemplateVars = null) {
			if (is_array($arrTemplateVars)) {
				extract($arrTemplateVars);
			}
			
			if (file_exists($strFilePath) && include($strFilePath)) {
				if ($this->blnBuffer && ($strContents = ob_get_contents())) {
					if (!empty($this->arrNodeList[$strNode])) {
						$this->arrNodeList[$strNode] .= $strContents;
					} else {
						$this->arrNodeList[$strNode] = $strContents;
					}
					@ob_clean();
				}
			} else {
				trigger_error(AppLanguage::translate('Invalid template for the %s node', $strNode));
			}
		}
		
		
		/**
		 * Appends a string of content. If output buffering
		 * isn't turned on it's displayed right away, otherwise
		 * it's added to the node list.
		 *
		 * @access public
		 * @param string $strNode The name of the node
		 * @param string $strContents The content to buffer or print
		 */
		public function appendString($strNode, $strContents) {
			if ($this->blnBuffer) {
				if (!empty($this->arrNodeList[$strNode])) {
					$this->arrNodeList[$strNode] .= $strContents;
				} else {
					$this->arrNodeList[$strNode] = $strContents;
				}
			} else {
				print $strContents;
			}
		}
		
		
		/**
		 * Builds the page output. Clears the node list once
		 * the contents have been appended to the output. If 
		 * no node order has been defined it goes in the order
		 * the nodes were created.
		 *
		 * @access public
		 */
		public function build() {
			if (empty($this->arrNodeOrder)) {
				$this->arrNodeOrder = array_keys($this->arrNodeList);
			}
		
			foreach ($this->arrNodeOrder as $strNode) {
				if (!empty($this->arrNodeList[$strNode])) {
					$this->strOutput .= $this->arrNodeList[$strNode];
					unset($this->arrNodeList[$strNode]);
				}
			}
		}
		
		
		/**
		 * Replaces content in either the built page or a 
		 * single node.
		 *
		 * @access public
		 * @param string $strSearch The string to search for the replaceable text
		 * @param string $strReplace The replacement text
		 * @param string $strNode The node to replace the text in; if null it'll replace it in the built output
		 */
		public function replace($strSearch, $strReplace, $strNode = null) {
			if ($strNode) {
				if (!empty($this->arrNodeList[$strNode])) {
					$this->arrNodeList[$strNode] = str_replace($strSearch, $strReplace, $this->arrNodeList[$strNode]);
				}
			} else {
				if (!empty($this->strOutput)) {
					$this->strOutput = str_replace($strSearch, $strReplace, $this->strOutput);
				}
			}
		}
		
		
		/**
		 * Sets the order the nodes should be displayed in.
		 * This is useful when the error node needs to be 
		 * displayed at the top and errors could still be
		 * triggered afterwards. Automatically turns on the
		 * output buffer which is required for this feature.
		 *
		 * @access public
		 * @param array $arrNodeOrder The array of nodes to display in order 
		 */
		public function setNodeOrder(array $arrNodeOrder) {
			$this->setBuffer(true);
			$this->arrNodeOrder = $arrNodeOrder;
		}
		
		
		/**
		 * Turns the output buffer on or off and returns
		 * true if the buffer status has been changed.
		 *
		 * @access public
		 * @param boolean $blnBuffer Whether to turn on the output buffer
		 * @return boolean True if the buffer status was changed
		 */
		public function setBuffer($blnBuffer) {
			if ($blnBuffer != $this->blnBuffer) {
				if ($this->blnBuffer = $blnBuffer) {
					ob_start();
				} else {
					if (ob_list_handlers()) {
						@ob_end_flush();
					}
				}
				return true;
			}
		}
		
		
		/**
		 * Turns on the output compression if the zlib extension 
		 * is available. The output compression is handled by the
		 * zlib.output_compression rather than ob_start and the
		 * ob_gzhandler callback method because the latter won't 
		 * work if multiple page nodes are used.
		 *
		 * @access public
		 * @param boolean $blnCompress Whether to compress the output
		 */
		public function setCompress($blnCompress) {
			if ($blnCompress && extension_loaded('zlib') && !headers_sent()) {
				ini_set('zlib.output_compression', 1);
			}
		}
		
		
		/**
		 * Returns the headers that should be sent.
		 *
		 * @access public
		 * @return array The array of headers
		 */
		public function getHeaders() {
			return $this->arrHeaders;
		}
		
		
		/**
		 * Sets the headers that should be sent.
		 *
		 * @access public
		 * @param $arrHeaders array The array of headers
		 */
		public function setHeaders($arrHeaders) {
			$this->arrHeaders = $arrHeaders;
		}
		
		
		/**
		 * Returns the contents of a single node.
		 *
		 * @access public
		 * @param string $strNode The name of the node to return
		 * @return string The node contents
		 */
		public function getNode($strNode) {
			if ($this->nodeExists($strNode)) {
				return $this->arrNodeList[$strNode];
			}
		}
		
		
		/**
		 * Clears out a single node and returns the 
		 * contents.
		 *
		 * @access public
		 * @param string $strNode The name of the node to clear
		 * @return string The contents of the node
		 */
		public function flushNode($strNode) {
			if (isset($this->arrNodeList[$strNode])) {
				$strContents = $this->arrNodeList[$strNode];
				unset($this->arrNodeList[$strNode]);
				return $strContents;
			}
		}
		
		
		/**
		 * Returns true if a node exists.
		 *
		 * @access public
		 * @param string $strNode The name of the node to check for
		 * @return boolean True if it exists
		 */
		public function nodeExists($strNode) {
			return isset($this->arrNodeList[$strNode]);
		}
		
		
		/**
		 * Gets the built output.
		 *
		 * @access public
		 * @return string The output
		 */
		public function getOutput() {
			return $this->strOutput;
		}
		
		
		/**
		 * Sets the output to the value passed.
		 *
		 * @access public
		 * @param string $strOutput The initial output
		 */
		public function setOutput($strOutput) {
			$this->strOutput = $strOutput;
		}
		
		
		/**
		 * Gets the status code.
		 *
		 * @access public
		 * @return integer The status code
		 */
		public function getStatusCode() {
			return $this->intStatusCode;
		}
		
		
		/**
		 * Appends the header associated with the HTTP status
		 * code passed.
		 *
		 * @access public
		 * @param integer $intStatusCode The status code to send (eg. 404)
		 */
		public function setStatusCode($intStatusCode) {
			if (!($arrStatusCodes = AppConfig::get('StatusCodes', false))) {
				if ($arrHttpConfig = AppConfig::load('http')) {
					$arrStatusCodes = $arrHttpConfig['StatusCodes'];
				}
			}
			
			if (!empty($arrStatusCodes[$intStatusCode])) {
				$this->intStatusCode = $intStatusCode;
				$strHeader = sprintf('HTTP/1.0 %d %s', $intStatusCode, $arrStatusCodes[$intStatusCode]);
				$this->appendHeader($strHeader);
			}
		}
	}