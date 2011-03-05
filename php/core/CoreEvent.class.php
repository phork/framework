<?php
	require_once('php/core/CoreObject.class.php');
	require_once('php/core/interfaces/Singleton.interface.php');
		
	/**
	 * CoreEvent.class.php
	 *
	 * The event class is used to register and run
	 * events throughout the application.
	 *
	 * This is a singleton class and therefore it must
	 * be instantiated using the getInstance() method.
	 *
	 * This must be extended by an AppEvent class.
	 *
	 * Copyright 2006-2010, Phork Labs. (http://phorklabs.com)
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
	abstract class CoreEvent extends CoreObject implements Singleton {
		
		static protected $objInstance;
		protected $arrEvents;
		
		
		/**
		 * The constructor can't be public for a singleton.
		 *
		 * @access protected
		 */
		protected function __construct() {
			AppLoader::includeExtension('iterators/', 'AssociativeIterator');
			$this->arrEvents = array();
		}
		
		
		/** 
		 * Returns the instance of the singleton object. If
		 * it doesn't exist it instantiates it.
		 *
		 * @access public
		 * @return object The instance of the event object
		 * @static
		 */
		static public function getInstance() {
			if (!self::$objInstance) {
				self::$objInstance = new AppEvent();
			}
			return self::$objInstance;
		}
		
		
		/**
		 * Checks if an event exists.
		 *
		 * @access public
		 * @param string $strEvent The name of the event to check
		 * @return boolean True if it exists
		 */
		static public function exists($strEvent) {
			return array_key_exists($strEvent, self::getInstance()->arrEvents);
		}
		
		
		/**
		 * Registers the event action by storing it in the
		 * events array. The action consists of an event name,
		 * a callback function, and an optional array of params
		 * to pass to the callback function.
		 *
		 * @access public
		 * @param string $strEvent The name of the event
		 * @param mixed $mxdCallback The name of the callback function, or an array for a class method
		 * @param array $arrParams The array of parameters to be passed to the callback
		 * @param integer $intPosition The position to insert the action in, otherwise it'll be last
		 * @return string The unique key of the event action
		 * @static
		 */
		static public function register($strEvent, $mxdCallback, array $arrParams = array(), $intPosition = null) {
			$objInstance = self::getInstance();
			
			if (!self::exists($strEvent)) {
				$objInstance->arrEvents[$strEvent] = new AssociativeIterator();
			}
			
			$arrCallback = array(null, array($mxdCallback, $arrParams));
			if ($intPosition !== null) {
				return $objInstance->arrEvents[$strEvent]->insert($intPosition, $arrCallback);
			} else {
				return $objInstance->arrEvents[$strEvent]->append($arrCallback);
			}
		}
		
		
		/**
		 * Runs the event actions registered with the event 
		 * name passed. Has the option to throw an exception
		 * if no event with that name exists. If any of the
		 * event actions return an array it's merged with all
		 * the other event action results and returned.
		 *
		 * @access public
		 * @param string $strEvent The name of the registered event
		 * @param array $arrParams Any additional params to send to the callbacks
		 * @param boolean $blnFatal Throws an exception if the event isn't found
		 * @param boolean $blnCleanup Whether to destroy the event after running it
		 * @return array The array of result data from the events
		 * @static
		 */
		static public function run($strEvent, $arrParams = array(), $blnFatal = false, $blnCleanup = false) {
			$objInstance = self::getInstance();	
			
			$arrResults = array();
			if (!self::exists($strEvent)) {
				if ($blnFatal) {
					throw new CoreException(AppLanguage::translate('No event named %s has been registered', $strEvent));
				}
			} else {
				$objInstance->arrEvents[$strEvent]->rewind(); 
				while (list($strKey, $arrEvent) = $objInstance->arrEvents[$strEvent]->each()) {
					if (is_array($mxdResult = call_user_func_array($arrEvent[0], array_merge($arrEvent[1], $arrParams)))) {
						$arrResults = array_merge($arrResults, $mxdResult);
					}
				}
				!$blnCleanup || self::destroy($strEvent);
			}
			return $arrResults;
		}
		
		
		/**
		 * Removes the event from the registry and returns
		 * the registered actions from the destroyed event.
		 *
		 * @access public
		 * @param string $strEvent The name of the event to remove
		 * @return object The iterator object containing the event actions
		 * @static
		 */
		static public function destroy($strEvent) {
			$objInstance = self::getInstance();
			
			if (self::exists($strEvent)) {
				$objIterator = $objInstance->arrEvents[$strEvent];
				unset($objInstance->arrEvents[$strEvent]);
				return $objIterator;
			}
		}
		
		
		/**
		 * Removes a single action from an event and
		 * returns the action.
		 *
		 * @access public
		 * @param string $strEvent The name of the event contain the action to remove
		 * @param string $strKey The key of the action to remove
		 */
		static public function remove($strEvent, $strKey) {
			$objInstance = self::getInstance();
			
			if (self::exists($strEvent) && $objInstance->arrEvents[$strEvent]->seekByKey($strKey)) {
				$arrEventAction = $objInstance->arrEvents[$strEvent]->current();
				$objInstance->arrEvents[$strEvent]->remove();
				return $arrEventAction;
			}
		}
	}