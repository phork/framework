<?php
	require_once('SiteApi.class.php');
	
	/**
	 * ExampleApi.class.php
	 * 
	 * BECAUSE THIS IS AN EXAMPLE IT USES CLASSES THAT
	 * DON'T EXIST. INCLUDING THE UserLogin CLASS TO GET
	 * A USER ID.
	 *
	 * /api/example/featured.json									(GET: featured results)
	 * /api/example/filter/by=id/[id].json							(GET: results by ID)
	 * /api/example/filter/by=userid/[user id].json					(GET: results by user ID)
	 *
	 * Additional formatting can be added to determine
	 * what gets returned in the result.
	 *
	 * /include=foo/												(include foo)
	 * /include=bar/												(include bar)
	 * /include=foo,bar/											(include foo and bar)
	 * /sort=alphabetical/											(sort the results alphabetically)
	 * /sort=latest/												(sort with the latest results first)
	 *
	 * Internal calls to this can set an additional
	 * internal flag.
	 *
	 * /internal=nocache/											(set the interal no cache flag)
	 *
	 * The query string options are as follows.
	 * p=[page num]													(return a specific page of results)
	 * num=[num per page]										`	(return x results per page)
	 *
	 * The following calls require authentication.
	 *
	 * /api/example/add.json										(POST: add a new record)
	 * /api/example/edit/[id].json									(POST: edit a record)
	 * /api/example/delete/[id].json								(DELETE: delete a record by ID)
	 *
	 * Copyright 2006-2011, Phork Labs. (http://phorklabs.com)
	 *
	 * Licensed under The MIT License
	 * Redistributions of files must retain the above copyright notice.
	 *
	 * @author Elenor Collings <elenor@phork.org>
	 * @license http://www.opensource.org/licenses/mit-license.php The MIT License
	 * @package phork
	 * @subpackage api
	 */
	class ExampleApi extends SiteApi {
	
		protected $blnFoo;
		protected $blnBar;
		
	
		/**
		 * Maps the API method to a method within this class
		 * and returns the response. If no method is mapped
		 * then it attempts to use the core handler.
		 *
		 * @access protected
		 */
		protected function handle() {
			$arrHandlers = array(
				'featured'		=> 'GetFeatured',
				'filter'		=> 'GetFiltered',
				
				'add'			=> 'DoAdd',
				'edit'			=> 'DoEdit',
				'delete'		=> 'DoDelete'
			);
			
			$strSegment = str_replace('.' . $this->strFormat, '', AppRegistry::get('Url')->getSegment(2));
			if (!empty($arrHandlers[$strSegment])) {
				$strMethod = $this->strMethodPrefix . $arrHandlers[$strSegment];
				$this->$strMethod();
			} else {
				parent::handle();
			}
		}
		
		
		/**
		 * Includes and instantiates an example model class
		 * and sets up the relations helper if necessary.
		 *
		 * @access public
		 * @return object The example model
		 */
		public function initModel() {
			AppLoader::includeModel('ExampleModel');
			
			$arrRelations = array();
			if ($this->blnFoo) {
				$arrRelations[] = 'HasMany.Foos';
			}
			if ($this->blnBar) {
				$arrRelations[] = 'HasOne.Bar';
			}
			
			$objExample = new ExampleModel(array(
				'Relations' => $blnRelations = !empty($arrRelations),
			));
			
			if ($blnRelations) {
				$objExample->initHelper('relations', array('loadSpecific'), array(
					'Relations' => $arrRelations,
					'Recursion' => 1
				));
			}
			
			return $objExample;
		}
		
		
		/**
		 * Gets the result parameters from the URL and returns
		 * the data to be extracted by the display method.
		 *
		 * @access protected
		 * @return array The compacted data
		 */
		protected function getResultParams() {
			$objUrl = AppRegistry::get('Url');
			
			$intNumResults = (int) !empty($this->arrParams['num']) ? $this->arrParams['num'] : 10;
			$intPage = (int) !empty($this->arrParams['p']) ? $this->arrParams['p'] : 1;
			$arrFilters = array(
				'Conditions' => array(),
				'Limit' => $intNumResults, 
				'Offset' => ($intPage - 1) * $intNumResults
			);
			
			if ($strSortBy = $objUrl->getFilter('sort')) {
				switch ($strSortBy) {
					case 'alphabetical':
						$arrFilters['Order'][] = array(
							'Column'	=> 'name',
							'Sort'		=> 'ASC'
						);
						break;
						
					case 'latest':
						$arrFilters['Order'][] = array(
							'Column'	=> 'created',
							'Sort'		=> 'DESC'
						);
						break;
				}
			}
			
			if ($this->blnInternal) {
				$arrInternal = explode(',', $objUrl->getFilter('internal'));
				if (in_array('nocache', $arrInternal)) {
					$this->blnNoCache = true;
				}
			} else {
				$arrInternal = array();
			}
			
			if ($arrInclude = explode(',', $objUrl->getFilter('include'))) {
				$this->blnFoo = in_array('foo', $arrInclude);
				$this->blnBar = in_array('bar', $arrInclude);
			}
			
			return compact('arrFilters', 'arrInternal');
		}
		
		
		/**
		 * Verifies the parameters from the URL, including the
		 * maximum number of results allowed.
		 *
		 * @access protected
		 * @return boolean True if valid
		 */
		protected function verifyParams() {
			$blnResult = true;
			
			if (!empty($this->arrParams['num']) && $this->arrParams['num'] > ($intMaxResults = 50)) {
				$blnResult = false;
				trigger_error(AppLanguage::translate('The maximum number of results allowed is %d', $intMaxResults));
			}
			
			return $blnResult;
		}
		
		
		/**
		 * Determines if the record is editable by the user.
		 *
		 * @access protected
		 * @param object $objExampleRecord The record to check
		 * @return boolean True if the Example is editable
		 */
		protected function isEditable($objExampleRecord) {
			if ($this->blnAuthenticated) {
				if ($objExampleRecord->get('userid') == AppRegistry::get('UserLogin')->getUserId()) {
					return true;
				} else {
					trigger_error(AppLanguage::translate('Records can only be edited by their owners'));
				}
			}
		}
			
			
		/*****************************************/
		/**     HANDLER METHODS                 **/
		/*****************************************/
		
		
		/**
		 * Gets the featured example records. Defaults to a
		 * maximum of 10 results but is configurable. Cached.
		 *
		 * @access protected
		 */
		protected function handleGetFeatured() {
			if ($this->verifyRequest('GET') && $this->verifyParams()) {
				extract($this->getResultParams());
				
				if (!$this->loadFromCache()) {
					$objExample = $this->initModel();
					if ($objExample->loadFeatured($arrFilters)) {
						$this->blnSuccess = true;
						if ($objExample->count()) {
							$this->arrResult = array(
								'examples'	=> $this->formatExamples($objExample),
								'total'		=> $objExample->getFoundRows()
							);
						} else {
							$this->arrResult = array(
								'examples'	=> array(),
								'total'		=> 0
							);
						}
						$this->saveToCache(300);
					} else {
						trigger_error(AppLanguage::translate('There was an error loading the example data'));
						$this->error();
					}
				}
			} else {
				$this->error(400);
			}
		}
		
		
		/**
		 * Gets the filtered example records. Defaults to a
		 * maximum of 10 results but is configurable. Cached.
		 *
		 * @access protected
		 */
		protected function handleGetFiltered() {
			if ($this->verifyRequest('GET') && $this->verifyParams()) {
				extract($this->getResultParams());
				
				$objUrl = AppRegistry::get('Url');
				$strFilterBy = $objUrl->getFilter('by');
				$mxdFilter = str_replace('.' . $this->strFormat, '', $objUrl->getSegment(3));
				
				$objExample = $this->initModel();
				switch ($strFilterBy) {
					case 'id':
						unset($arrFilters['Limit'], $arrFilters['Offset']);
						$blnResult = $objExample->loadById($mxdFilter, $arrFilters);
						break;
						
					case 'userid':
						$blnResult = $objExample->loadByUserId($mxdFilter, $arrFilters);
						break;
				}
				
				if ($blnResult) {
					$this->blnSuccess = true;
					if ($objExample->count()) {
						$this->arrResult = array(
							'examples'	=> $this->formatExamples($objExample),
							'total'		=> $objExample->getFoundRows()
						);
					} else {
						$this->arrResult = array(
							'examples'	=> array(),
							'total' 	=> 0
						);
					}
				} else {
					trigger_error(AppLanguage::translate('There was an error loading the example data'));
					$this->error();
				}
			} else {
				$this->error(400);
			}
		}
		
		
		/*****************************************/
		/**     ACTION METHODS                  **/
		/*****************************************/
		
		
		/**
		 * Adds an example record for the authenticated user.
		 *
		 * @access protected
		 */
		protected function handleDoAdd() {
			if ($this->verifyRequest('POST') && $this->verifyParams()) {
				if ($this->blnAuthenticated) {
					AppLoader::includeUtility('Sanitizer');
					if (!($arrUnsanitary = Sanitizer::sanitizeArray($this->arrParams))) {			
						AppLoader::includeModel('ExampleModel');
						$objExample = new ExampleModel(array('Validate' => true));
						$objExample->import(array(
							'userid'	=> AppRegistry::get('UserLogin')->getUserId(),
							'name'		=> !empty($this->arrParams['name']) ? $this->arrParams['name'] : null,
						));
						
						if ($objExample->save() && $intId = $objExample->current()->get('__id')) {
							$this->blnSuccess = true;
							$this->intStatusCode = 201;
							$this->arrResult = array(
								'id' => $intId
							);
						} else {
							trigger_error(AppLanguage::translate('There was an error adding the example'));
							$this->error();
						}
					} else {
						trigger_error(AppLanguage::translate('The following value(s) contain illegal data: %s', implode(', ', array_map('htmlentities', $arrUnsanitary))));
						$this->error(400);
					}
				} else {
					trigger_error(AppLanguage::translate('Missing or invalid authentication'));
					$this->error(401);
				}
			} else {
				$this->error(400);
			}
		}
		
		
		/**
		 * Edits an example record owned by the authenticated
		 * user.
		 *
		 * @access protected
		 */
		protected function handleDoEdit() {
			if ($this->verifyRequest('POST') && $this->verifyParams()) {
				if ($this->blnAuthenticated) {
					AppLoader::includeUtility('Sanitizer');
					if (!($arrUnsanitary = Sanitizer::sanitizeArray($this->arrParams))) {	
						$objUrl = AppRegistry::get('Url');		
						if ($strIdSegment = $objUrl->getSegment(3)) {
							$intId = str_replace('.' . $this->strFormat, '', $strIdSegment);
							
							AppLoader::includeModel('ExampleModel');
							$objExample = new ExampleModel(array('Validate' => true));
							if ($objExample->loadById($intId, array('AutoFilterOff' => true)) && $objExampleRecord = $objExample->current()) {
								if ($this->isEditable($objExampleRecord)) {
									$objExampleRecord->set('name', !empty($this->arrParams['name']) ? $this->arrParams['name'] : null);
									
									if ($objExample->save()) {
										$this->blnSuccess = true;
										$this->intStatusCode = 201;
									} else {
										trigger_error(AppLanguage::translate('There was an error editing the example'));
										$this->error();
									}
								}
							} else {
								trigger_error(AppLanguage::translate('Invalid record ID'));
								$this->error(400);
							}
						} else {
							trigger_error(AppLanguage::translate('Missing record ID'));
							$this->error(400);
						}
					} else {
						trigger_error(AppLanguage::translate('The following value(s) contain illegal data: %s', implode(', ', array_map('htmlentities', $arrUnsanitary))));
						$this->error(400);
					}
				} else {
					trigger_error(AppLanguage::translate('Missing or invalid authentication'));
					$this->error(401);
				}
			} else {
				$this->error(400);
			}
		}
		
		
		/**
		 * Deletes an example record owned by the authenticated
		 * user.
		 *
		 * @access protected
		 */
		protected function handleDoDelete() {
			if ($this->verifyRequest('DELETE') && $this->verifyParams()) {
				if ($this->blnAuthenticated) {
					if ($intExampleId = str_replace('.' . $this->strFormat, '', AppRegistry::get('Url')->getSegment(3))) {
						$objExample = $this->initModel();
						if ($objExample->loadById($intExampleId) && $objExample->count() == 1) {
							if ($this->isEditable($objExample->current())) {
								if ($objExample->destroy()) {
									CoreAlert::alert('The example was deleted successfully.');
									$this->blnSuccess = true;
									$this->intStatusCode = 200;
								} else {
									trigger_error(AppLanguage::translate('There was an error deleting the example'));
									$this->error(400);
								}
							} else {
								trigger_error(AppLanguage::translate('Invalid example permissions'));
								$this->error(401);
							}
						} else {
							trigger_error(AppLanguage::translate('There was an error loading the example data'));
							$this->error(400);
						}
					} else {
						trigger_error(AppLanguage::translate('Missing example ID'));
						$this->error(401);
					}
				} else {
					trigger_error(AppLanguage::translate('Missing or invalid authentication'));
					$this->error(401);
				}
			} else {
				$this->error(400);
			}
		}
		
		
		/*****************************************/
		/**     FORMAT METHODS                  **/
		/*****************************************/
		
		
		/**
		 * Formats the example records into an array to be encoded.
		 *
		 * @access protected
		 * @param object $objExample The list of records to format
		 * @return array The results in array format
		 */
		protected function formatExample($objExample) {
			$arrResults = array();
			
			if ($objExample instanceof CoreIterator) {
				while (list(, $objExampleRecord) = $objExample->each()) {
					$arrResult = array(
						'id'		=> $objExampleRecord->get('__id'),
						'userid'	=> $objExampleRecord->get('userid'),
						'name'		=> $objExampleRecord->get('name')
					);
					
					if ($this->blnFoo) {
						$arrResult['foos'] = $this->formatFoo($objExampleRecord->get('foos'));
					}
					if ($this->blnBar) {
						$arrResult['bar'] = $this->formatBar($objExampleRecord->get('bar'));
					}
					
					$arrResults[] = $arrResult;
				}
				$objExample->rewind();
			}
			
			return $arrResults;
		}
		
		
		/**
		 * Formats the foo records into an array to be encoded.
		 *
		 * @access protected
		 * @param object $objFoo The list of foo records to format
		 * @return array The foos in array format
		 */
		protected function formatFoo($objFoo) {
			$arrResults = array();
			
			if ($objFoo instanceof CoreIterator) {
				while (list(, $objFooRecord) = $objFoo->each()) {
					$arrResults[] = array(
						'id'	=> $objFooRecord->get('__id'),
						'foo'	=> $objFooRecord->get('foo'),
						'boo'	=> $objFooRecord->get('boo')
					);
				}
				$objFoo->rewind();
			}
			
			return $arrResults;
		}
		
		
		/**
		 * Formats a bar record into an array to be encoded.
		 *
		 * @access protected
		 * @param object $objBar The list of bar records to format
		 * @return array The bar in array format
		 */
		protected function formatBar($objBar) {
			$arrResults = array();
			
			if ($objBar instanceof CoreIterator) {
				if ($objBarRecord = $objBar->first()) {
					$arrResults = array(
						'id'	=> $objBarRecord->get('__id'),
						'bar'	=> $objBarRecord->get('bar'),
						'car'	=> $objBarRecord->get('car')
					);
				}
			}
			
			return $arrResults;
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
				case 'examples':
				case 'foos':
					$strNode = substr($strParentNode, 0, -1);
					break;
			}
			return $strNode;
		}
	}