<?php
	require_once('php/core/CoreObject.class.php');
	require_once('php/core/interfaces/Singleton.interface.php');
	
	/**
	 * DatabaseManager.class.php
	 * 
	 * The database manager class is used to manage
	 * multiple database objects and to swap out the
	 * registered database object with a different
	 * one. The calls to change and revert databases
	 * can be stacked.
	 *
	 * Copyright 2006-2010, Phork Labs. (http://phorklabs.com)
	 *
	 * Licensed under The MIT License
	 * Redistributions of files must retain the above copyright notice.
	 *
	 * @author Elenor Collings <elenor@phork.org>
	 * @license http://www.opensource.org/licenses/mit-license.php The MIT License
	 * @package phork
	 * @subpackage database
	 */
	class DatabaseManager extends CoreObject implements Singleton {
		
		static protected $objInstance;
		
		protected $strCurrent;
		protected $arrStack;
		protected $arrObjects;
		
		
		/**
		 * The constructor can't be public for a singleton.
		 *
		 * @access protected
		 */
		protected function __construct() {
			$this->strCurrent = 'Default';
			$this->arrStack = array();
			$this->arrObjects = array();
		}
		
		
		/** 
		 * Returns the instance of the singleton object. If
		 * it doesn't exist it instantiates it.
		 *
		 * @access public
		 * @return object The instance of the debug object
		 * @static
		 */
		static public function getInstance() {
			if (!self::$objInstance) {
				self::$objInstance = new DatabaseManager();
			}
			return self::$objInstance;
		}
		
		
		/**
		 * Adds a database object to the database library.
		 *
		 * @access public
		 * @param string $strName The name of the database
		 * @param object $objDatabase The database object
		 * @static
		 */
		static public function appendDatabase($strName, $objDatabase) {
			if (!($objDatabase instanceof Sql)) {
				throw new CoreException(AppLanguage::translate('Only objects that implement the Sql interface can be added to the database manager'));
			}
			
			$objInstance = self::getInstance();
			$objInstance->arrObjects[$strName] = $objDatabase;
		}
		
		
		/**
		 * Changes the registered database object to 
		 * a different object and adds the new object
		 * to the end of the stack.
		 *
		 * @access public
		 * @param string $strName The name of the database object to use
		 * @static
		 */
		static public function changeDatabase($strName) {
			$objInstance = self::getInstance();		
			if (empty($objInstance->arrObjects[$strName])) {
				throw new CoreException(AppLanguage::translate('Invalid database object'));
			}
			
			if ($objInstance->strCurrent != $strName) {
				if (empty($objInstance->arrObjects[$objInstance->strCurrent])) {
					if ($objDatabase = AppRegistry::get('Database')) {
						$objInstance->arrStack[] = $objInstance->strCurrent;
						$objInstance->arrObjects[$objInstance->strCurrent] = $objDatabase;
					}
				}
				AppRegistry::destroy('Database');
				AppRegistry::register('Database', $objInstance->arrObjects[$strName]);
				$objInstance->arrObjects[$strName]->selectDatabase();
			}
			$objInstance->arrStack[] = $objInstance->strCurrent = $strName;
		}
		
		
		/**
		 * Reverts to the previously used database object
		 * and removes the current object from the stack.
		 * This actually re-registers the previous database
		 * so it must also be removed from the stack before
		 * being re-added.
		 *
		 * @access public
		 * @static
		 */
		static public function revertDatabase() {
			$objInstance = self::getInstance();
			if (count($objInstance->arrStack) < 2) {
				throw new CoreException(AppLanguage::translate('No database to revert to'));
			}
			
			array_pop($objInstance->arrStack);
			$objInstance->changeDatabase(array_pop($objInstance->arrStack));
		}
		
		
		/**
		 * Removes the database object from the list.
		 *
		 * @access public
		 * @param string $strName The name of the database object to remove
		 * @static
		 */
		static public function removeDatabase($strName) {
			$objInstance = self::getInstance();		
			if (!empty($objInstance->arrObjects[$strName])) {
				unset($objInstance->arrObjects[$strName]);
			}
		}
		
		
		/**
		 * Checks if the database has already been appended
		 * to this instance.
		 *
		 * @access public
		 * @return boolean True if it exists
		 * @static
		 */
		static public function exists($strName) {
			return array_key_exists($strName, self::getInstance()->arrObjects);
		}
	}