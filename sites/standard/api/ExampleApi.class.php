<?php
	require_once('php/core/CoreApi.class.php');
	
	/**
	 * ExampleApi.class.php
	 * 
	 * This class is an example of the API calls. This can
	 * either be called internally or by URL using the 
	 * ApiController.
	 *
	 * /api/example/featured.json									(GET: featured result)
	 * /api/example/filter/by=id/[id].json							(GET: results by ID)
	 * /api/example/filter/by=userid/[user id].json					(GET: results by user ID)
	 * /api/example/filter/by=foo/[foo].json						(GET: results by another filter)
	 *
	 * Additional formatting can be added to determine
	 * what gets returned in the result.
	 *
	 * /include=foo/												(include foo)
	 * /include=bar/												(include bar)
	 * /include=foo,bar/											(include foo and bar)
	 *
	 * Internal calls to this can set an additional
	 * internal flag.
	 *
	 * /internal=unpublished,lipsum/								(set the interal unpublished and lipsum flags)
	 *
	 * The query string options are as follows.
	 * p=[page num]													(return a specific page of results)
	 * num=[num per page]										`	(return x results per page)
	 *
	 * The following calls require authentication.
	 *
	 * /api/example/favorite.json									(GET: favorite results of the user)
	 * /api/example/add.json										(POST: add a new record)
	 * /api/example/edit/[id].json									(PUT: edit a record)
	 * /api/example/delete/[id].json								(DELETE: delete a record by ID)
	 *
	 * Copyright 2006-2010, Phork Labs. (http://phorklabs.com)
	 *
	 * Licensed under The MIT License
	 * Redistributions of files must retain the above copyright notice.
	 *
	 * @author Elenor Collings <elenor@phork.org>
	 * @license http://www.opensource.org/licenses/mit-license.php The MIT License
	 * @package phork-standard
	 * @subpackage api
	 */
	class ExampleApi extends CoreApi {
	
		/**
		 * Maps the API method to a method within this class
		 * and returns the response. If no method is mapped
		 * then it attempts to use the core handler.
		 *
		 * @access protected
		 */
		protected function handle() {
			$arrHandlers = array(
				'favorite'		=> 'GetFavorite',
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
		 * @param boolean $blnFoo Whether to include the foo relations
		 * @param boolean $blnBar Whether to include the bar relations
		 * @return object The example model
		 */
		public function initModel($blnFoo, $blnBar) {
			AppLoader::includeModel('ExampleModel');
			
			$arrRelations = array();
			if ($blnFoo) {
				$arrRelations[] = 'HasMany.Foo';
			}
			if ($blnBar) {
				$arrRelations[] = 'HasMany.Bar';
			}
			
			$objExample = new ExampleModel(array(
				'Relations'	 => !empty($arrRelations),
			));
			
			if (count($arrRelations)) {
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
			$intNumResults = !empty($this->arrParams['num']) ? $this->arrParams['num'] : 10;
			$intPage = !empty($this->arrParams['p']) ? $this->arrParams['p'] : 1;
			$arrLimit = array('Limit' => $intNumResults, 'Offset' => ($intPage - 1) * $intNumResults);
			
			$arrInclude = explode(',', AppRegistry::get('Url')->getFilter('include'));
			$blnFoo = in_array('foo', $arrInclude);
			$blnBar = in_array('bar', $arrInclude) && $this->blnAuthenticated;
			
			return compact('arrLimit', 'blnFoo', 'blnBar');
		}
		
		
		/**
		 * Determines if the record is editable by the user.
		 *
		 * @access protected
		 * @param object $objExampleRecord The record to check
		 * @return boolean True if the Example is editable
		 */
		protected function isEditable($objExampleRecord = null) {
			if ($this->blnAuthenticated) {
				if ($objExampleRecord) {
					if ($objExampleRecord->get('userid') == AppRegistry::get('UserLogin')->getUserId()) {
						return true;
					} else {
						trigger_error(AppLanguage::translate('Records can only be edited by their owners'));
					}
				}
			}
		}
			
			
		/*****************************************/
		/**     HANDLER METHODS                 **/
		/*****************************************/
		
		
		/**
		 * Gets the featured examples. Defaults to 10 results but
		 * is configurable.
		 *
		 * @access protected
		 */
		protected function handleGetFeatured() {
			extract($this->getResultParams());
			
			$objExample = $this->initModel($blnFoo, $blnBar);
			if ($objExample->loadFeatured($arrLimit)) {
				$this->blnSuccess = true;
				if ($objExample->count()) {
					$this->arrResult = array(
						'records'	=> $this->formatExamples($objExample, $blnFoo, $blnBar),
						'total'		=> $objExample->getFoundRows()
					);
				} else {
					$this->arrResult = array(
						'records'	=> array(),
						'total'		=> 0
					);
				}
			} else {
				trigger_error(AppLanguage::translate('There was an error loading the example data'));
				$this->error();
			}
		}
		
		
		/**
		 * Gets the authenticated user's favorite examples.
		 * Defaults to 10 results but is configurable.
		 *
		 * @access protected
		 */
		protected function handleGetFavorites() {
			if ($this->blnAuthenticated) {
				extract($this->getResultParams());
				
				trigger_error(AppLanguage::translate('Favorite results are coming soon'));
				$this->error();
			} else {
				$this->error(401);
			}
		}	
		
		
		/**
		 * Gets the filtered examples. Defaults to 10 results
		 * but is configurable.
		 *
		 * @access protected
		 */
		protected function handleGetFiltered() {
			extract($this->getResultParams());
			
			$objUrl = AppRegistry::get('Url');
			$strFilterBy = $objUrl->getFilter('by');
			$mxdFilter = str_replace('.' . $this->strFormat, '', $objUrl->getSegment(3));
			
			$arrFilters = $arrLimit;
			if ($this->blnInternal) {
				$arrInternalFilters = explode(',', $objUrl->getFilter('internal'));
				if (in_array('unpublished', $arrInternalFilters)) {
					$arrFilters['AutoFilterOff'] = true;
				}		
			}
				
			$objExample = $this->initModel($blnFoo, $blnBar);
			switch ($strFilterBy) {
				case 'id':
					unset($arrFilters['Limit'], $arrFilters['Offset']);
					$blnResult = $objExample->loadById($mxdFilter, $arrFilters);
					break;
					
				case 'userid':
					$blnResult = $objExample->loadByUserId($mxdFilter, $arrFilters);
					break;
					
				case 'foo':
					$blnResult = $objExample->loadByFoo($mxdFilter, $arrFilters);
					break;
			}
			
			if ($blnResult) {
				$this->blnSuccess = true;
				if ($objExample->count()) {
					$this->arrResult = array(
						'records'	=> $this->formatExamples($objExample, $blnFoo, $blnBar),
						'total'		=> $objExample->getFoundRows()
					);
				} else {
					$this->arrResult = array(
						'records'	=> array(),
						'total' 	=> 0
					);
				}
			} else {
				trigger_error(AppLanguage::translate('There was an error loading the example data'));
				$this->error();
			}
		}
		
		
		/*****************************************/
		/**     ACTION METHODS                  **/
		/*****************************************/
		
		
		/**
		 * Adds an example for the authenticated user.
		 *
		 * @access protected
		 */
		protected function handleDoAdd() {
			if ($this->blnAuthenticated) {
				AppLoader::includeUtility('Sanitizer');
				if (!($arrUnsanitary = Sanitizer::sanitizeArray($this->arrParams))) {			
					AppLoader::includeModel('ExampleModel');
					$objExample = new ExampleModel(array('Validate' => true));
					$objExample->import(array(
						'userid'	=> AppRegistry::get('UserLogin')->getUserId(),
						'baz'		=> !empty($this->arrParams['baz']) ? $this->arrParams['baz'] : null,
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
				$this->error(401);
			}
		}
		
		
		/**
		 * Edits an example by the authenticated user.
		 *
		 * @access protected
		 */
		protected function handleDoEdit() {
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
								$objExampleRecord->set('baz', !empty($this->arrParams['baz']) ? $this->arrParams['baz'] : null);
								
								if ($objExample->save()) {
									$this->blnSuccess = true;
									$this->arrResult = array(
										'id' => $intId
									);
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
				$this->error(401);
			}
		}
		
		
		/**
		 * Deletes a Example and displays success or failure.
		 *
		 * @access protected
		 */
		protected function handleDoDelete() {
			if ($this->blnAuthenticated) {
				trigger_error(AppLanguage::translate('Delete is coming soon'));
				$this->error();
			} else {
				$this->error(401);
			}
		}
		
		
		/*****************************************/
		/**     FORMAT METHODS                  **/
		/*****************************************/
		
		
		/**
		 * Formats the records into an array to be encoded.
		 *
		 * @access protected
		 * @param object $objExample The list of records to format
		 * @param boolean $blnFoo Whether to include the foo data
		 * @param boolean $blnBar Whether to include the bar data
		 * @return array The results in array format
		 */
		protected function formatExample($objExample, $blnFoo, $blnBar) {
			$arrResults = array();
			
			if ($objExample instanceof CoreIterator) {
				while (list(, $objExampleRecord) = $objExample->each()) {
					$arrResult = array(
						'id'		=> $objExampleRecord->get('__id'),
						'userid'	=> $objExampleRecord->get('userid'),
						'baz'		=> $objExampleRecord->get('baz')
					);
					
					if ($blnFoo) {
						$arrResult['foos'] = $this->formatFoo($objExampleRecord->get('foo'));
					}
					if ($blnBar) {
						$arrResult['bars'] = $this->formatBar($objExampleRecord->get('bar'));
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
		 * Formats the bar records into an array to be encoded.
		 *
		 * @access protected
		 * @param object $objBar The list of bar records to format
		 * @return array The bars in array format
		 */
		protected function formatBar($objBar) {
			$arrResults = array();
			
			if ($objBar instanceof CoreIterator) {
				while (list(, $objBarRecord) = $objBar->each()) {
					$arrResults[] = array(
						'id'	=> $objBarRecord->get('__id'),
						'bar'	=> $objBarRecord->get('bar'),
						'car'	=> $objBarRecord->get('car')
					);
				}
				$objBar->rewind();
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
				case 'foos':
				case 'bars':
					$strNode = substr($strParentNode, 0, -1);
					break;
			}
			return $strNode;
		}
	}