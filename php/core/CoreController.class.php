<?php
	require_once('php/core/CoreControllerLite.class.php');
	require_once('php/core/CoreAlert.class.php');
	
	/**
	 * CoreController.class.php
	 * 
	 * The controller class is used to pull together all
	 * the data and the templates to build the page. This
	 * works in conjunction with the bootstrap and the 
	 * display class.
	 *
	 * Unlike CoreControllerLite, this breaks up the parts
	 * of the page into cacheable nodes, rather than including
	 * a complete page. In order to use the node cache each
	 * display[Node]() method must call includeNodeCache() 
	 * first.
	 *
	 * The nodes to cache are defined by the URL. A regex
	 * match is performed against the current URL and the 
	 * list of URLs to cache. If a match is found the data
	 * associated with the match is used to define the cache
	 * params.
	 *
	 * <code>
	 * protected $arrCacheUrls = array(
	 *		'#(/manual/[^/]+/[^/]+/)#' 	=> array(
	 *			array(
	 *				'Node'		=> 'content',
	 *				'Namespace'	=> null,
	 *				'Expire'	=> 300
	 *			)
	 *		)
	 *	);
	 * </code>
	 *
	 * Nodes are also rearrangeable. See the display()
	 * method comment for more details.
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
	class CoreController extends CoreControllerLite {
	
		protected $arrNodeOrder = array('header', 'errors', 'alerts', 'content', 'footer');
		protected $arrNodeList;
		
		protected $arrCacheUrls;
		protected $arrCacheInfo;
		protected $blnNoCache;
		
		
		/**
		 * Sets up the template path and turns on compression
		 * and output buffering. Also determines which nodes,
		 * if any, should be cached. Can also be used to set
		 * up any default page data.
		 *
		 * @access public
		 */
		public function __construct() {
			parent::__construct();
			
			$objDisplay = AppDisplay::getInstance();
			$objDisplay->setCompress(true);
			$objDisplay->setBuffer(true);
						
			if (AppRegistry::get('Cache', false)) {
				$this->getCacheableNodes();
			}
		}
		
		
		/**
		 * Triggers an error and throws an exception. Has
		 * special handling to remove the alert and content
		 * nodes.
		 *
		 * @access public
		 * @param integer $intErrorCode The type of error
		 * @param string $strException The exception to throw
		 */
		public function error($intErrorCode = null, $strException = null) {
			if ($this->arrNodeList) {
				foreach ($this->arrNodeList as $intKey=>$strNode) {
					if (in_array($strNode, array('alert', 'content'))) {
						unset($this->arrNodeList[$intKey]);
					}
				}
			} else {
				$this->setNodeList(array('header', 'errors', 'footer'));
			}
			
			parent::error($intErrorCode, $strException);
		}
		
		
		/*****************************************/
		/**     DISPLAY METHODS                 **/
		/*****************************************/
		
		
		/**
		 * Pulls all the templates together and builds the
		 * page. In general this should usually be called from
		 * run().
		 *
		 * This sets the node order which automatically turns
		 * on output buffering so the page isn't actually output
		 * until the display object has been destroyed. This
		 * allows for errors triggered from the content to be
		 * displayed at the top of the page, and for page titles
		 * set in the content method to be used in the header.
		 *
		 * The page isn't output until the display object
		 * has been destroyed or its output() method has
		 * been explicitly called. To build a page means
		 * to merge all the node content into a single string.
		 *
		 * The error node should always be displayed after
		 * the saveNodeCache() call in case that produces
		 * errors.
		 *
		 * @access protected
		 */
		protected function display() {
			$objDisplay = AppDisplay::getInstance();
			
			if (is_array($this->arrNodeOrder)) {
				$objDisplay->setNodeOrder($this->arrNodeOrder);
			}
			
			if (empty($this->arrNodeList) || in_array('content', $this->arrNodeList)) {
				if (method_exists($this, $strMethod = $this->strMethodPrefix . ($this->strContent ? $this->strContent : 'Index'))) {
					$this->$strMethod();
				} else {
					$this->error(404);
				}
			}
			
			if (!empty($this->arrNodeOrder)) {
				foreach ($this->arrNodeOrder as $strNode) {
					if (empty($this->arrNodeList) || in_array($strNode, $this->arrNodeList)) {
						if (!in_array($strNode, array('content', 'errors'))) {
							$this->{$this->strMethodPrefix . ucfirst($strNode)}();
						}
					}
				}
			}
			
			$this->saveNodeCaches();
			
			if (empty($this->arrNodeList) || in_array('errors', $this->arrNodeList)) {
				$this->displayErrors();
			}
		}
		
		
		/**
		 * Displays the page header and passes the CSS and
		 * Javascript URLs to it.
		 * 
		 * @access protected
		 */
		protected function displayHeader() {
			$this->displayNode('header', $this->getTemplatePath('common/header'), array(
				'strCssUrl'		=> AppConfig::get('CssUrl'),
				'strJsUrl'		=> AppConfig::get('JsUrl')
			));
		}
		
		
		/**
		 * Displays the page footer and passes the Javascript
		 * URL to it.
		 *
		 * @access protected
		 */
		protected function displayFooter() {
			$this->displayNode('footer', $this->getTemplatePath('common/footer'), array(
				'strJsUrl'		=> AppConfig::get('JsUrl')
			));
		}
		
		
		/**
		 * Displays any errors that were triggered.
		 *
		 * @access protected
		 */
		protected function displayErrors() {
			if ($arrErrors = AppRegistry::get('Error')->getErrors()) {
				$this->displayNode('errors', $this->getTemplatePath('common/errors'), array(
					'arrErrors' => $arrErrors
				));
			}
		}
		
		
		/**
		 * Displays any alerts that were triggered.
		 *
		 * @access protected
		 */
		protected function displayAlerts() {
			if ($arrAlerts = CoreAlert::getAlerts()) {
				$this->displayNode('alerts', $this->getTemplatePath('common/alerts'), array(
					'arrAlerts' => $arrAlerts
				));
			}
		}
		
		
		/**
		 * Displays the index page.
		 *
		 * @access protected
		 */
		protected function displayIndex() {
			$this->displayNode('content', $this->getTemplatePath('index'));
		}
		
		
		/**
		 * Displays a generic node using the path passed.
		 * This handles caching if there are no errors in 
		 * the page. If a 'controller.displaynode' event
		 * has been registered then that is run if the
		 * cache fails. The results are added to the vars
		 * passed to the template.
		 *
		 * @access protected
		 * @param string $strNode The name of the node being displayed
		 * @param string $strFilepath The path to the custom template relative to the template directory
		 * @param array $arrLocalVars An associative array of variables that should be available to just this template
		 */
		protected function displayNode($strNode, $strFullPath, array $arrLocalVars = array()) {
			if (!$this->includeNodeCache($strNode)) {
				if ($this->validateFile($strFullPath)) {
					$arrResults = AppEvent::run('controller.displaynode');
					AppDisplay::getInstance()->appendTemplate($strNode, $strFullPath, array_merge($this->arrPageVars, $arrLocalVars, $arrResults));
				}
			}
			AppEvent::destroy('controller.displaynode');
		}
		
		
		/*****************************************/
		/**     CACHE METHODS                   **/
		/*****************************************/
		
		
		/**
		 * Includes a cached template if the node is cacheable and
		 * set a success or failure flag for the node.
		 *
		 * @access public
		 * @param string $strNode The node to retrieve the cache for
		 * @return boolean True if the cache was retrieved
		 */
		public function includeNodeCache($strNode) {
			if (!empty($this->arrCacheInfo['NodeList'][$strNode])) {
				$objCache = AppRegistry::get('Cache');
				$objCache->initPresentation();
			
				if ($strNamespace = $this->getCacheNamespace($strNode)) {
					$strContents = $objCache->loadNS($this->getCacheKey($strNode), $strNamespace);
				} else {
					$strContents = $objCache->load($this->getCacheKey($strNode));
				}
				
				if ($strContents) {
					$this->arrCacheInfo['NodeList'][$strNode]['Failed'] = 0;
					AppDisplay::getInstance()->appendString($strNode, $strContents);
				} else {
					$this->arrCacheInfo['NodeList'][$strNode]['Failed'] = 1;
				}
			}
			return !empty($strContents);
		}
		
		
		/**
		 * Saves all the failed template caches at once.
		 *
		 * @access public
		 */
		public function saveNodeCaches() {
			if (!AppRegistry::get('Error')->getErrorFlag()) {
				if (!empty($this->arrCacheInfo['NodeList'])) {
					$objCache = AppRegistry::get('Cache');
					$objCache->initPresentation();
					
					foreach ($this->arrCacheInfo['NodeList'] as $strNode=>$arrCacheParams) {
						if (!empty($arrCacheParams['Failed'])) {
							if ($strNamespace = $this->getCacheNamespace($strNode)) {
								$objCache->saveNS($this->getCacheKey($strNode), $strNamespace, AppDisplay::getInstance()->getNode($strNode), $arrCacheParams['Expire']);
							} else {
								$objCache->save($this->getCacheKey($strNode), AppDisplay::getInstance()->getNode($strNode), $arrCacheParams['Expire']);
							}
						}
					}
				}
			}
		}
		
		
		/**
		 * Check if caching is turned on and if the URL matches
		 * one of the cacheable URL patterns. If so it gets the
		 * array of nodes that should be cached. Also runs the
		 * namespace through a function in case any page-specific
		 * data needs adding to it.
		 *
		 * @access protected
		 * @return boolean True if there are cacheable nodes
		 */
		protected function getCacheableNodes() {
			$this->arrCacheInfo = array();
			
			if (!empty($this->arrCacheUrls) && !$this->blnNoCache) {
				$strMatchAgainst = AppRegistry::get('Url')->getUrl();
				foreach ($this->arrCacheUrls as $strUrl=>$arrNodeList) {
					if (preg_match($strUrl, $strMatchAgainst, $this->arrCacheInfo['Matches'])) {
						foreach ($arrNodeList as $arrNode) {
							$this->arrCacheInfo['NodeList'][$arrNode['Node']] = $this->assignCacheableNode($arrNode);
						}
						return true;
					}
				}
			}
		}
		
		
		/**
		 * Builds the cache key for the cacheable node.
		 *
		 * @access protected
		 * @param string $strNode The name of the node
		 * @return string The cache key
		 */
		protected function getCacheKey($strNode) {
			if (count($this->arrCacheInfo['Matches']) == 1) {
				$strCacheKey = $this->arrCacheInfo['Matches'][0];
			} else {
				$strCacheKey = implode(': ', array_slice($this->arrCacheInfo['Matches'], 1));
			}
			return sprintf('%s (%s)', $strCacheKey, $strNode);
		}
		
		
		/**
		 * Returns the cache namespace. This can be extended to
		 * format the namespace based on the URL or user.
		 *
		 * @access protected
		 * @return
		 */
		protected function getCacheNamespace($strNode) {
			if (!empty($this->arrCacheInfo['NodeList'][$strNode]['Namespace'])) {
				return $this->arrCacheInfo['NodeList'][$strNode]['Namespace'];
			}
		}
		
		
		/**
		 * Returns the cacheable node info. Must include the
		 * expiration time in seconds. This can be extended to
		 * format the namespace based on the URL or user.
		 *
		 * @access protected
		 * @param array $arrNode The node definition from $arrCacheUrls
		 * @return array The array of cacheable node info
		 */
		protected function assignCacheableNode(&$arrNode) {
			return array(
				'Failed'	=> null,
				'Namespace'	=> !empty($arrNode['Namespace']) ? $arrNode['Namespace'] : null,
				'Expire'	=> $arrNode['Expire']
			);
		}
		
		
		/*****************************************/
		/**     GET & SET METHODS               **/
		/*****************************************/
		
		
		/**
		 * Sets the name of the content template to display.
		 * The display[Content]() method will be called.
		 *
		 * @access protected
		 * @param string $strContent The content to display
		 */
		protected function setContent($strContent) {
			$this->strContent = $strContent;
		}
		
		
		/**
		 * Sets the list of nodes that should be displayed.
		 * If the node list is empty then all nodes will be
		 * displayed, otherwise it'll just display the specific
		 * nodes defined here. This can be used to turn off
		 * the header and footer nodes when displaying multiple
		 * pages at once.
		 *
		 * @access public
		 * @param array $arrNodeList The list of nodes to display
		 */
		public function setNodeList(array $arrNodeList) {
			$this->arrNodeList = $arrNodeList;
		}
		
		
		/**
		 * Turns off (or on) node caching.
		 *
		 * @access public
		 * @param boolean Whether to turn off caching
		 */
		public function setNoCache($blnNoCache) {
			$this->blnNoCache = $blnNoCache;
		}
	}