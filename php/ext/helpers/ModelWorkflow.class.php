<?php
	require_once('php/core/CoreModelHelper.class.php');
	
	/**
	 * ModelWorkflow.class.php
	 * 
	 * This is a model helper class to handle workflow.
	 * This registers events that are run by the model
	 * object. Any data returned from the event methods
	 * is available in the function that runs the helper
	 * event. This should be applied at the controller
	 * level and not the model level.
	 *
	 * Copyright 2006-2010, Phork Software. (http://www.phork.org)
	 *
	 * Licensed under The MIT License
	 * Redistributions of files must retain the above copyright notice.
	 *
	 * @author Elenor Collings <elenor@phork.org>
	 * @license http://www.opensource.org/licenses/mit-license.php The MIT License
	 * @package phork
	 * @subpackage helpers
	 */
	class ModelWorkflow extends CoreModelHelper {
		
		/**
		 * Initializes the helper object by registering
		 * the events to run. This should be called from
		 * a model object.
		 *
		 * @access public
		 * @param array $arrEvents The names of the events to register
		 * @param array $arrConfig An array of config vars specific to this initialization
		 */
		public function init($arrEvents, $arrConfig = array()) {
			
			//validate the record during the model's pre-save event
			if (in_array('preSave', $arrEvents)) {
				$this->arrEvents[] = array(
					'Event'	=> ($strEvent = $this->strModelKey . '.pre-save'),
					'Key'	=> AppEvent::register($strEvent, array($this, 'queue'))
				);
			}
		}
		
		
		/**
		 * Saves a workflow record.
		 *
		 * @access public
		 * @param object $objModel The model object to use with the workflow
		 * @return array The array of vars to return to the save function
		 */
		public function queue($objModel) {
			if ($objModel instanceof CoreModel && ($objRecord = $objModel->current())) {
				AppLoader::includeModel('WorkflowModel');
				$objWorkflow = new WorkflowModel();
				$arrStepKeys = array_keys($objWorkflow->getStepOptions());
				$arrStatusKeys = array_keys($objWorkflow->getStatusOptions());
				$objWorkflow->import(array(
					'userid'		=> AppRegistry::get('UserLogin')->getUserId(),
					'itemtype'		=> get_class($objModel),
					'itemid'		=> $objModel->current()->get('__id'),
					'record'		=> $objModel->current(),
					'step'			=> $arrStepKeys[0],
					'status'		=> $arrStatusKeys[0]
				));
				
				if ($objWorkflow->save()) {	
					CoreAlert::alert(AppLanguage::translate('The changes have been submitted and are pending approval. Changes will not show up until they have been approved.'), true);
					return array(
						'blnSkipSave'	=> true,
						'blnResult' 	=> true
					);
				}
			}
		}
	}