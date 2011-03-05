<?php
	require_once('php/core/CoreIterator.class.php');
		
	/**
	 * AssociativeIterator.class.php
	 *
	 * The associative iterator class stores a collection
	 * of item that can be accessed in a standardized way.
	 * Unlike the core iterator this stores items with an
	 * associated key. 
	 *
	 * Copyright 2006-2010, Phork Labs. (http://phorklabs.com)
	 *
	 * Licensed under The MIT License
	 * Redistributions of files must retain the above copyright notice.
	 *
	 * @author Elenor Collings <elenor@phork.org>
	 * @license http://www.opensource.org/licenses/mit-license.php The MIT License
	 * @package phork
	 * @subpackage iterators
	 */
	class AssociativeIterator extends CoreIterator {
	
		protected $arrKeys;
			

		/**
		 * Initializes the properties and resets the cursor. 
		 * This is broken out from the constructor so that 
		 * it can be recalled from the __wakeup() method.
		 *
		 * @access protected
		 */
		protected function init() {
			if (!$this->arrKeys) {
				$this->arrKeys = array();
			}
			parent::init();
		}
		
		
		/**
		 * Generates a unique key for an item.
		 *
		 * @access protected
		 * @return string The unique key
		 */
		protected function generateKey() {
			do {
				$strKey = '__' . rand();
			} while (in_array($strKey, $this->arrKeys));
			
			return $strKey;
		}
		
		
		/*****************************************/
		/**     MODIFICATION METHODS            **/
		/*****************************************/
		
			
		/**
		 * Clears out the list and resets the cursor.
		 *
		 * @access public
		 */
		public function clear() {
			$this->arrKeys = array();
			parent::clear();
		}
		
		
		/**
		 * Moves the pointer to the position of the key 
		 * passed.
		 *
		 * @access public
		 * @param string $strKey The key to seek to
		 * @return boolean True if the position exists
		 */
		public function seekByKey($strKey) {
			if (($intPosition = $this->getPositionByKey($strKey)) !== false) {
				$this->intCursor = $intPosition;
				return true;
			}
		}
		
		
		/**
		 * Appends an item to the list and increments the
		 * count.
		 *
		 * @access public
		 * @param array $arrItem The key and item to append
		 * @return string The array key of the appended item
		 */
		public function append($arrItem) {
			list($strKey, $mxdItem) = $arrItem;
			if ($this->validate($mxdItem)) {
				$this->arrKeys[$this->intCount] = $strKey ? $strKey : $strKey = $this->generateKey();
				$this->arrItems[$this->intCount] = $mxdItem;
				$this->intCount++;
				
				return $strKey;
			}
		}
		
		
		/**
		 * Inserts an item at a specific position and shifts
		 * all the other items accordingly.
		 *
		 * @access public
		 * @param integer $intPosition The position to insert the item
		 * @param array $arrItem The key and item to append
		 * @return string The array key of the appended item
		 */
		public function insert($intPosition, $arrItem) {
			list($strKey, $mxdItem) = $arrItem;
			if ($this->validate($mxdItem)) {
				array_splice($this->arrKeys, $intPosition, 1, array_merge(array($strKey), array_slice($this->arrKeys, $intPosition, 1)));
				array_splice($this->arrItems, $intPosition, 1, array_merge(array($mxdItem), array_slice($this->arrItems, $intPosition, 1)));
				$this->intCount++;
				
				return $strKey;
			}
		}
		
		
		/**
		 * Removes the current item from the list by its key.
		 *
		 * @access public
		 * @param string $strKey The key to remove by
		 * @return boolean True on success
		 */
		public function removeByKey($strKey) {
			if (($intPosition = $this->getPositionByKey($strKey)) !== false) {
				return $this->removeByPosition($intPosition);
			}
		}
		
		
		/**
		 * Removes an item by its position, decrements the
		 * count and shifts the other items to fill the hole.
		 *
		 * @access public
		 * @param integer $intPosition The position of the item to remove.
		 * @return boolean True on success
		 */
		public function removeByPosition($intPosition) {
			if (parent::removeByPosition($intPosition)) {
				unset($this->arrKeys[$intPosition]);
				$this->arrKeys = array_values($this->arrKeys);
				return true;
			}
		}
		
		
		/**
		 * Modifies the current item in the list by its key.
		 *
		 * @access public
		 * @param string $strKey The key to modify by
		 * @param mixed $mxdItem The new item to put in its place
		 * @param string $strNewKey The new key of the item if it should be changed
		 * @return boolean True on success
		 */
		public function modifyByKey($strKey, $mxdItem, $strNewKey = null) {
			if (($intPosition = $this->getPositionByKey($strKey)) !== false) {
				if ($this->modifyByPosition($intPosition, $mxdItem)) {
					if (!is_null($strNewKey) && $strNewKey != $strKey) {
						$this->arrKeys[$intPosition] = $strNewKey;
					}
					return true;
				}
			}
		}
		
		
		/*****************************************/
		/**     RETRIEVAL METHODS               **/
		/*****************************************/
		
		
		/**
		 * Returns the key at the position of the cursor.
		 *
		 * @access public
		 * @return string The current key
		 */
		public function key() {
			if (isset($this->arrKeys[$this->intCursor])) {
				return $this->arrKeys[$this->intCursor];
			}
		}
		
		
		/**
		 * Returns the current key and item from the list
		 * and advances the cursor. This should not be used
		 * with the remove or modify methods here (or any
		 * other method that relies on the cursor) because
		 * the cursor will have been interated and will
		 * be on the next item.
		 *
		 * @access public
		 * @return array The array of key and item
		 */
		public function each() {
			if ($this->intCursor < $this->intCount) {
				$strKey = $this->arrKeys[$this->intCursor];
				$mxdItem = $this->arrItems[$this->intCursor];
				$this->next();
				return array($strKey, $mxdItem);
			}
		}
		
		
		/**
		 * Gets the position of the item by its key.
		 *
		 * @access public
		 * @param string $strKey The key to get the position of
		 * @return integer The position if it exists
		 */
		public function getPositionByKey($strKey) {
			return array_search($strKey, $this->arrKeys);
		}
		
		
		/*****************************************/
		/**     MAGIC METHODS                   **/
		/*****************************************/
		
		
		/**
		 * Returns the list of variables that should be stored
		 * when the object is serialized. This serializes the
		 * items and the keys.
		 *
		 * @access public
		 * @return array The array of properties to serialize
		 */
		public function __sleep() {
			return array('arrItems', 'arrKeys');
		}
	}