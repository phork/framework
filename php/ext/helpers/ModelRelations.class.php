<?php
	require_once('php/core/CoreModelHelper.class.php');
	
	/**
	 * ModelRelations.class.php
	 * 
	 * This is a model helper class to handle relations.
	 * This registers events that are run by the model
	 * object. Any data returned from the event methods
	 * is available in the function that runs the helper
	 * event.
	 *
	 * In the case of this helper it's a good idea to
	 * append the helper from the model object's constructor
	 * even if it's not initialized right away. This is
	 * so relation definitions aren't scattered all over
	 * the place.
	 *
	 * In order to use recursive relations the model
	 * constructor should check for a Relations config
	 * value and if it exists it should append and init
	 * the relations.
	 *
	 * <code>
	 * if (!empty($arrConfig['Relations'])) {
	 *		if (AppLoader::includeExtension('helpers/', 'ModelRelations')) {
	 * 			$this->appendHelper('relations', 'ModelRelations', array(
	 *				'BelongsToOne'	=> array(
	 *					'User'			=> array(
	 *						'LoadAs'		=> 'user',
	 *						'LoadTotalAs'	=> 'user_total',
	 *						'AutoLoad'		=> true,
	 *						'ClassName'		=> 'UserModel',
	 *						'ClassLoader'	=> array('AppLoader', 'includeFooModel'),
	 *						'Dependent'		=> true,
	 *						'Conditions'	=> array(
	 *							array(
	 *								'Column' 	=> 'userid',
	 *								'Property' 	=> 'userid',
	 *								'Operator'	=> '='
	 *							),
	 *							array(
	 *								'Column' 	=> 'foobar',
	 *								'Value'		=> '1',
	 *								'Operator'	=> '='
	 *							)
	 *						),
	 * 						'Order'			=> array(
	 * 							array(
	 * 								'Column'	=> 'created',
	 * 								'Sort'		=> 'desc'
	 * 							)
	 * 						),
	 * 						'Limit'			=> 20,
	 * 						'Offset'		=> 0,
	 * 						'Config'		=> array(
	 * 							'NoUserJoin'	=> true
	 * 						)
	 *					)
	 *				),
	 *				'HasOne'		=> array(
	 *					'Test'			=> array(
	 *						'LoadAs'		=> 'test',
	 *						'AutoLoad'		=> false,
	 *						'BatchLoader'	=> array($this, 'loadTestBatch')
	 *					)
	 *				)
	 * 			));
	 *
	 * 			$this->initHelper('relations', array('loadAutoLoad'), array(
	 * 				'Recursion' => isset($arrConfig['RelationsRecursion']) ? $arrConfig['RelationsRecursion'] : 0
	 * 			));
	 *		}
	 * }
	 * </code>
	 *
	 * Copyright 2006-2011, Phork Labs. (http://phorklabs.com)
	 *
	 * Licensed under The MIT License
	 * Redistributions of files must retain the above copyright notice.
	 *
	 * @author Elenor Collings <elenor@phork.org>
	 * @license http://www.opensource.org/licenses/mit-license.php The MIT License
	 * @package phork
	 * @subpackage helpers
	 * @todo Add methods to handle relation delete and save
	 */
	class ModelRelations extends CoreModelHelper {
		
		protected $intRecursion;
		
		
		/**
		 * Initializes the helper object by registering
		 * the events to run. This should be called from
		 * a model object. Sets up any recursion config
		 * parameters that specify the number of levels
		 * deep to load the releations.
		 *
		 * @access public
		 * @param array $arrEvents The names of the events to register
		 * @param array $arrConfig An array of config vars specific to this initialization
		 */
		public function init($arrEvents, $arrConfig = array()) {
			$this->intRecursion = (array_key_exists('Recursion', $arrConfig) ? $arrConfig['Recursion'] : 0);
			
			//loads all the relations during the model's post-load event
			if (in_array('loadAll', $arrEvents)) {
				$this->arrEvents[] = array(
					'Event'	=> ($strEvent = $this->strModelKey . '.post-load'),
					'Key'	=> AppEvent::register($strEvent, array($this, 'loadAll'), array(false))
				);
				
			//loads only the autoload relations during the model's post-load event
			} else if (in_array('loadAutoLoad', $arrEvents)) {
				$this->arrEvents[] = array(
					'Event'	=> ($strEvent = $this->strModelKey . '.post-load'),
					'Key'	=> AppEvent::register($strEvent, array($this, 'loadAll'), array(true))
				);
			
			//loads only the relations passed	
			} else if (in_array('loadSpecific', $arrEvents)) {
				$this->arrEvents[] = array(
					'Event'	=> ($strEvent = $this->strModelKey . '.post-load'),
					'Key'	=> AppEvent::register($strEvent, array($this, 'loadSpecific'), array($arrConfig['Relations']))
				);
			}
		}
		
		
		/**
		 * Loads all the auto-load relations for all the 
		 * records in the model's iterator object.
		 *
		 * @access public
		 * @param boolean $blnAutoLoad Whether to only load the autoload relations
		 * @param boolean $arrSpecific The array of specific relations to load
		 */
		public function loadAll($blnAutoLoadOnly, $objModel) {
			if ($objModel instanceof CoreModel) {
				foreach ($this->arrConfig as $strType=>$arrRelations) {
					foreach ($arrRelations as $strRelation=>$arrRelation) {
						if (!$blnAutoLoadOnly || !empty($arrRelation['AutoLoad'])) {
							if (!empty($arrRelation['BatchLoader'])) {
								call_user_func_array($arrRelation['BatchLoader'], array($arrRelation, $objModel));
							} else {
								while (list(, $objRecord) = $objModel->each()) {
									if (!$objRecord->get($arrRelation['LoadAs'])) {
										$this->load($objRecord, $arrRelation);
									}
								}
								$objModel->rewind();
							}
						}
					}
				}
			}
		}
		
		
		/**
		 * Loads the specific relations defined in the
		 * array passed. Should be in the format:
		 * BelongsTo.Foo, HasMany.Bar, etc.
		 *
		 * @access public
		 * @param boolean $arrSpecific The array of specific relations to load
		 * @param object $objModel The model object load the relations for
		 */
		public function loadSpecific($arrSpecific, $objModel) {
			if ($objModel instanceof CoreModel) {
				foreach ($this->arrConfig as $strType=>$arrRelations) {
					foreach ($arrRelations as $strRelation=>$arrRelation) {
						if (in_array("{$strType}.{$strRelation}", $arrSpecific)) {
							if (!empty($arrRelation['BatchLoader'])) {
								call_user_func_array($arrRelation['BatchLoader'], array($arrRelation, $objModel));
							} else {
								while (list(, $objRecord) = $objModel->each()) {
									if (!$objRecord->get($arrRelation['LoadAs'])) {
										$this->load($objRecord, $arrRelation);
									}
								}
								$objModel->rewind();
							}
						}
					}
				}
			}
		}
		
		
		/**
		 * Loads a single relation by the relation config
		 * passed. If the LoadTotalAs flag is set this loads
		 * the total number of matching records. If the
		 * recursion level is set this also sets up the
		 * relation to load relations.
		 *
		 * @access protected
		 * @param object $objRecord The object that the relation belongs to
		 * @param array $arrRelation The relation to load
		 */
		protected function load($objRecord, $arrRelation) {
			$arrConfig = array_merge(!empty($arrRelation['Config']) ? $arrRelation['Config'] : array(), array(
				'Relations'	=> $this->intRecursion > 0,
				'RelationsRecursion' => max(0, $this->intRecursion - 1),
				'RelationsAutoLoad' => true
			));
						
			if (!class_exists($arrRelation['ClassName'], false)) {
				if (!empty($arrRelation['ClassLoader'])) {
					call_user_func_array($arrRelation['ClassLoader'], array($arrRelation['ClassName']));
				} else {
					AppLoader::includeModel($arrRelation['ClassName']);
				}
			}
			$objRelationModel = new $arrRelation['ClassName']($arrConfig);
				
			$arrWhere = array();
			foreach ($arrRelation['Conditions'] as $arrFilter) {
				$arrWhere[] = array(
					'Column' 	=> (!empty($arrFilter['WithTable']) ? $objRelationModel->getTable() . '.' : '') . $arrFilter['Column'],
					'Value'  	=> array_key_exists('Value', $arrFilter) ? $arrFilter['Value'] : $objRecord->get($arrFilter['Property']),
					'Operator' 	=> $arrFilter['Operator'],
				);
			}
			
			$objRelationModel->load(array(
				'Conditions'	=> $arrWhere,
				'Order'			=> !empty($arrRelation['Order']) ? $arrRelation['Order'] : null,
				'Limit'			=> !empty($arrRelation['Limit']) ? $arrRelation['Limit'] : null,
				'Offset'		=> !empty($arrRelation['Offset']) ? $arrRelation['Offset'] : 0,
			), $blnCalcFoundRows = !empty($arrRelation['LoadTotalAs']));
			
			$objRecord->set($arrRelation['LoadAs'], $objRelationModel->getRecords());			
			if ($blnCalcFoundRows) {
				$objRecord->set($arrRelation['LoadTotalAs'], $objRelationModel->getFoundRows());
			}
		}
	}