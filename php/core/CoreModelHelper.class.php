<?php
	require_once('php/core/CoreObject.class.php');
	require_once('interfaces/ModelHelper.interface.php');
	
	/**
	 * CoreModelHelper.class.php
	 * 
	 * An abstract model helper class to handle the
	 * registering, and destroying of events. The init()
	 * method should register the events to run based on
	 * the event names passed, and should store both the
	 * event name and the event actin key in the events
	 * array so they can be explicitly unregistered.
	 *
	 * <code>
	 * if (in_array('loadAll', $arrEvents)) {
	 *		$this->arrEvents[] = array(
	 *			'Event'	=> ($strEvent = $this->strModelKey . '.post-load'),
	 *			'Key'	=> AppEvent::register($strEvent, array($this, 'loadAll'), array(false))
	 *		);
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
	 * @subpackage core
	 */
	abstract class CoreModelHelper extends CoreObject implements ModelHelper {
		
		protected $strModelKey;
		protected $arrConfig;
		protected $arrEvents;
		
		
		/**
		 * Sets the the model's event key which identifies the
		 * model object within the event object, and the config 
		 * values.
		 *
		 * @access public
		 * @param string $strModelKey The model object's event key
		 * @param array $arrConfig The relation config
		 */
		public function __construct($strModelKey, $arrConfig) {
			$this->strModelKey = $strModelKey;
			$this->arrConfig = $arrConfig;
			$this->arrEvents = array();
		}
		
		
		/**
		 * Removes all the event actions registered by
		 * this object.
		 *
		 * @access public
		 */
		public function __destruct() {
			$this->destroy();
		}
		
		
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
			throw new CoreException(AppLanguage::translate('The %s method must be defined in the extension to the %s class', __METHOD__, __CLASS__));
		}
		
		
		/**
		 * Destroys the helper object by removing all
		 * the events it runs.
		 *
		 * @access public
		 * @param string $strModelKey The model object's event key
		 */
		public function destroy() {
			foreach ($this->arrEvents as $arrEvent) {
				AppEvent::remove($arrEvent['Event'], $arrEvent['Key']);
			}
		}
	}