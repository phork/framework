<?php	
	/**
	 * XmlBuilder.class.php
	 * 
	 * Converts an object or array into XML.
	 *
	 * <code>
	 * function formatNodeName($strNode, $strParent) {
	 *  	switch ($strParent) {
	 * 			case 'dogs':
	 * 			case 'cats':
	 * 				return substr($strParent, 0, -1);
	 *
	 * 			case 'cactii':
	 * 				return 'cactus';
	 *
	 * 			default:
	 * 				return $strNode;
	 *     }
	 * }
	 * 
	 * AppLoader::includeUtility('XmlBuilder');
	 * $objXmlBuilder = new XmlBuilder($arrResult, null, 'root', 'item', 'formatNodeName');
	 * $strEncoded = $objXmlBuilder->getXml();
	 * </code>
	 *
	 * Copyright 2006-2010, Phork Labs. (http://phorklabs.com)
	 *
	 * Licensed under The MIT License
	 * Redistributions of files must retain the above copyright notice.
	 *
	 * @author Elenor Collings <elenor@phork.org>
	 * @license http://www.opensource.org/licenses/mit-license.php The MIT License
	 * @package phork
	 * @subpackage utilities
	 */
	class XmlBuilder {
	
		protected $arrNumericReplacements;
		protected $strNumericPrefix;
		protected $mxdFormatNodeName;
		protected $blnIncludeKeys;
		
		protected $objDom;
		protected $strXml;
		
		
		/**
		 * Sets up the DOM Document object, creates the first
		 * node, and calls the build method to create the rest.
		 *
		 * @access public
		 * @param mixed $mxdData The array or object to turn into XML
		 * @param array $arrNumericReplacements The numeric node replacement definitions
		 * @param string $strRootNode The name to use for the root node
		 * @param string $strNumericPrefix The prefix to add to any numeric node not in the replacements array
		 * @param mixed $mxdFormatNodeName The callback function to format the node name
		 * @param boolean $blnIncludeKeys Whether to include the array key as an attribute for non-associative arrays
		 */
		public function __construct($mxdData, $arrNumericReplacements = array(), $strRootNode = 'root', $strNumericPrefix = 'node', $mxdFormatNodeName = null, $blnIncludeKeys = true) {
			$this->arrNumericReplacements = $arrNumericReplacements;
			$this->strNumericPrefix = $strNumericPrefix;
			$this->mxdFormatNodeName = $mxdFormatNodeName;
			$this->blnIncludeKeys = $blnIncludeKeys;
		
			$this->objDom = new DOMDocument();
			$this->objDom->formatOutput = true;
			$this->objDom->appendChild($objRoot = $this->objDom->createElement($strRootNode));
			
			$this->build($mxdData, $objRoot);
		}
		
		
		/**
		 * Builds the XML from an array or object and appends it to
		 * the parent node passed. If the node name is numeric and
		 * it has been replaced with a string, and if $blnIncludeKeys
		 * is true then this adds the original numeric value as a key
		 * attribute.
		 *
		 * @access public
		 * @param mixed $mxdData The array or object of data to turn into XML
		 * @param object $objParent The object to attach the node(s) to
		 */
		public function build($mxdData, $objParent) {
			foreach ($mxdData as $mxdKey=>$mxdItem) {
				$strKey = $this->node($mxdKey, $objParent);
				
				if ($blnRecurse = (is_array($mxdItem) || is_object($mxdItem))) {
					$objParent->appendChild($objChild = $this->objDom->createElement($strKey));
				} else {
					$objParent->appendChild($objChild = $this->objDom->createElement($strKey))->appendChild($this->objDom->createTextNode($mxdItem));
				}
				
				if ($this->blnIncludeKeys && ($strKey != "$mxdKey")) {
					$objChild->appendChild($objIdNode = $this->objDom->createAttribute('key'));
					$objIdNode->appendChild($this->objDom->createTextNode($mxdKey));
				}
				
				if ($blnRecurse) {
					$this->build($mxdItem, $objChild);
				}
			}
		}
		
		
		/**
		 * Returns the node name to use. If the node is numeric
		 * this checks the name of the parent node and looks in
		 * the numeric replacement array to see if there's a 
		 * default node name to use.
		 *
		 * @access protected
		 * @param mixed $mxdKey The key to turn into a node name
		 * @param object $objParent The parent node
		 */
		protected function node($mxdKey, $objParent) {
			if (!is_numeric($mxdKey)) {
				$strKey = $mxdKey;
			} else if (!empty($this->arrNumericReplacements[$strParentNode = $objParent->nodeName])) {
				$strKey = $this->arrNumericReplacements[$strParentNode];
			} else {
				$strKey = $this->strNumericPrefix;
			}
			
			if ($this->mxdFormatNodeName) {
				$strKey = call_user_func_array($this->mxdFormatNodeName, array($strKey, $objParent->nodeName));
			}
			
			return $strKey;
		}
		
		
		/*****************************************/
		/**     GET & SET METHODS               **/
		/*****************************************/
		
		
		/**
		 * Returns the DOM Document object.
		 *
		 * @access public
		 * @return object The DOM Document object
		 */
		public function getDom() {
			return $this->objDom;
		}
		
		
		/**
		 * Returns the XML string.
		 *
		 * @access public
		 * @return string The XML string
		 */
		public function getXml() {
			return $this->objDom->saveXML();
		}
	}