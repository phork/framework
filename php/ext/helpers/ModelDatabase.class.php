<?php
	require_once('php/core/CoreModelHelper.class.php');
	
	/**
	 * ModelDatabase.class.php
	 *
	 * A model helper class to swap out the database
	 * connection before loading, saving and deleting.
	 *
	 * <code>
	 * AppLoader::includeExtension('helpers/', 'ModelDatabase')
	 * $objModel->appendHelper('database', 'ModelDatabase');
	 * $objModel->initHelper('database', array('preLoad', 'preSave', 'preDelete'), array(
	 * 		'Database' => 'foo'
	 * ));
	 * ...
	 * $objModel->destroyHelper('database');
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
	 */
	class ModelDatabase extends CoreModelHelper {
		
		/**
		 * Initializes the helper object by registering
		 * the events to run. This should be called from
		 * a model object. The config array should contain
		 * a Database element with the name of the database
		 * in the DatabaseManager class.
		 *
		 * @access public
		 * @param array $arrEvents The names of the events to register
		 * @param array $arrConfig An array of config vars specific to this initialization
		 */
		public function init($arrEvents, $arrConfig = array()) {
			if ($strDatabase = !empty($arrConfig['Database']) ? $arrConfig['Database'] : null) {
			
				//sets up the pre-load and post-load events to change the database and back again
				if (in_array('preLoad', $arrEvents)) {
					$this->arrEvents[] = array(
						'Event'	=> ($strEvent = $this->strModelKey . '.pre-load'),
						'Key'	=> AppEvent::register($strEvent, array('DatabaseManager', 'changeDatabase'), array($strDatabase))
					);
					$this->arrEvents[] = array(
						'Event'	=> ($strEvent = $this->strModelKey . '.post-load'),
						'Key'	=> AppEvent::register($strEvent, array('DatabaseManager', 'revertDatabase'))
					);
				}
				
				//sets up the pre-save and post-save events to change the database and back again
				if (in_array('preSave', $arrEvents)) {
					$this->arrEvents[] = array(
						'Event'	=> ($strEvent = $this->strModelKey . '.pre-save'),
						'Key'	=> AppEvent::register($strEvent, array('DatabaseManager', 'changeDatabase'), array($strDatabase))
					);
					$this->arrEvents[] = array(
						'Event'	=> ($strEvent = $this->strModelKey . '.post-save'),
						'Key'	=> AppEvent::register($strEvent, array('DatabaseManager', 'revertDatabase'))
					);
				}
				
				//sets up the pre-delete and post-delete events to change the database and back again
				if (in_array('preDelete', $arrEvents)) {
					$this->arrEvents[] = array(
						'Event'	=> ($strEvent = $this->strModelKey . '.pre-delete'),
						'Key'	=> AppEvent::register($strEvent, array('DatabaseManager', 'changeDatabase'), array($strDatabase))
					);
					$this->arrEvents[] = array(
						'Event'	=> ($strEvent = $this->strModelKey . '.post-delete'),
						'Key'	=> AppEvent::register($strEvent, array('DatabaseManager', 'revertDatabase'))
					);
				}
			}
		}
	}