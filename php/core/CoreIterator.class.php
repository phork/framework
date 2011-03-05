<?php
	require_once('php/core/CoreObject.class.php');
		
	/**
	 * CoreIterator.class.php
	 *
	 * The iterator class stores a collection of items.
	 * This implements PHP's built in iterator interface.
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
	class CoreIterator extends CoreObject implements Iterator {
	
		protected $arrItems;
		protected $intCursor;
		protected $intCount;
			

		/**
		 * The constructor initializes the item and count 
		 * data.
		 *
		 * @access public
		 */
		public function __construct() {
			$this->init();
		}
		
		
		/**
		 * Initializes the properties and resets the cursor. 
		 * This is broken out from the constructor so that 
		 * it can be recalled from the __wakeup() method.
		 *
		 * @access protected
		 */
		protected function init() {
			if (!$this->arrItems) {
				$this->arrItems = array();
			}
		
			$this->intCount = count($this->arrItems);
			$this->rewind();
		}
		
		
		/**
		 * Validates the item being added to the list. This
		 * allows any type of item to be added but can be
		 * extended to add certain restrictions.
		 *
		 * @access protected
		 * @param mixed $mxdItem The record to validate
		 */
		protected function validate($mxdItem) {
			return true;
		}
		
		
		/*****************************************/
		/**     MODIFICATION METHODS            **/
		/*****************************************/
		
			
		/**
		 * Clears out the list and resets the cursor.
		 *
		 * @acccess public
		 */
		public function clear() {
			$this->arrItems = array();
			$this->intCount = 0;
			$this->rewind();
		}
		
		
		/**
		 * Rewinds the cursor to the begining of the list.
		 *
		 * @access public
		 */
		public function rewind() {
			$this->intCursor = 0;
		}
		
		
		/**
		 * Moves the cursor to the end of the list.
		 *
		 * @access public
		 */
		public function end() {
			$this->intCursor = $this->intCount - 1;
		}
		
		
		/**
		 * Moves the pointer to the position passed.
		 *
		 * @access public
		 * @param integer $intPosition The position to seek to
		 * @return boolean True if the position exists
		 */
		public function seek($intPosition) {
			if (isset($this->arrItems[$intPosition])) {
				$this->intCursor = $intPosition;
				return true;
			}
		}
		
		
		/**
		 * Appends an item to the list and increments the
		 * count.
		 *
		 * @access public
		 * @param mixed $mxdItem The item to append
		 * @return integer The array key of the appended item
		 */
		public function append($mxdItem) {
			if ($this->validate($mxdItem)) {
				$this->arrItems[$this->intCount++] = $mxdItem;
				return $this->intCount - 1;
			}
		}
		
		
		/**
		 * Inserts an item at a specific position and shifts
		 * all the other items accordingly.
		 *
		 * @access public
		 * @param integer $intPosition The position to insert the item
		 * @param mixed $mxdItem The item to insert
		 * @return integer The array key of the appended item
		 */
		public function insert($intPosition, $mxdItem) {
			if ($this->validate($mxdItem)) {
				array_splice($this->arrItems, $intPosition, 1, array_merge(array($mxdItem), array_slice($this->arrItems, $intPosition, 1)));
				$this->intCount++;
				
				return $intPosition;
			}
		}
		
		
		/**
		 * Removes the current item from the list.
		 *
		 * @access public
		 * @return boolean True on success
		 */
		public function remove() {
			return $this->removeByPosition($this->intCursor);
		}
		
		
		/**
		 * Removes the previous item from the list. Useful
		 * when iterating through with each() and having to
		 * remove the current record but the iterator has
		 * been iterated to the next one.
		 *
		 * @access public
		 * @return boolean True on success
		 */
		public function removePrevious() {
			return $this->removeByPosition($this->intCursor - 1);
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
			if (isset($this->arrItems[$intPosition])) {
				unset($this->arrItems[$intPosition]);
				$this->arrItems = array_values($this->arrItems);
				
				if ($this->intCursor > $intPosition) {
					$this->intCursor--;
				}
				$this->intCount--;
				
				return true;
			} else {
				trigger_error(AppLanguage::translate("Cannot remove non-existant item '%d'", $intPosition));
			}
		}
		
		
		/**
		 * Modifies the current item in the list.
		 *
		 * @access public
		 * @param mixed $mxdItem The new item
		 * @return boolean True on success
		 */
		public function modify($mxdItem) {
			return $this->modifyByPosition($this->intCursor, $mxdItem);
		}
		
		
		/**
		 * Modifies an item in the list by its position.
		 *
		 * @access public
		 * @param integer $intPosition The position of the item to modify
		 * @param mixed $mxdItem The new item to put in its place
		 * @return boolean True on success
		 */
		public function modifyByPosition($intPosition, $mxdItem) {
			if ($this->validate($mxdItem)) {
				if (isset($this->arrItems[$intPosition])) {
					$this->arrItems[$intPosition] = $mxdItem;
					return true;
				} else {
					trigger_error(AppLanguage::translate("Cannot modify non-existant item '%d'", $intPosition));
				}
			}
		}
		
		
		/*****************************************/
		/**     RETRIEVAL METHODS               **/
		/*****************************************/
		
		
		/**
		 * Returns true if the cursor is on a valid item.
		 *
		 * @access public
		 * @return boolean True if valid
		 */
		public function valid() {
			return $this->intCursor >= 0 && $this->intCursor < $this->intCount;
		}
		
		
		/**
		 * Returns the count of the items in the list.
		 *
		 * @access public
		 * @return integer The item count
		 */
		public function count() {
			return $this->intCount;
		}
		
		
		/**
		 * Returns the position of the cursor.
		 *
		 * @access public
		 * @return integer The cursor position
		 */
		public function key() {
			return $this->intCursor;
		}
		
		
		/**
		 * Returns the current item from the list.
		 *
		 * @access public
		 * @return mixed The current item
		 */
		public function current() {
			if (isset($this->arrItems[$this->intCursor])) {
				return $this->arrItems[$this->intCursor];
			}
		}
		
		
		/**
		 * Returns the current item from the list and
		 * advances the cursor. This should not be used
		 * with the remove or modify methods here (or any
		 * other method that relies on the cursor) because
		 * the cursor will have been interated and will
		 * be on the next item.
		 *
		 * @access public
		 * @return array The array of position and item
		 */
		public function each() {
			if (isset($this->arrItems[$this->intCursor])) {
				$intPosition = $this->intCursor;
				$mxdItem = $this->arrItems[$this->intCursor];
				$this->next();
				return array($intPosition, $mxdItem);
			}
		}
		
		
		/**
		 * Advances the cursor and returns the next item
		 * from the list. The maximum cursor value is one
		 * past the last actual position.
		 *
		 * @access public
		 * @return mixed The next item
		 */
		public function next() {
			if ($this->intCursor < $this->intCount) {
				$this->intCursor++;
				return $this->current();
			} else {
				$this->intCursor = $this->intCount;
			}
		}
		
		
		/**
		 * Rewinds the cursor and returns the previous item
		 * from the list. The minimum cursor value is one
		 * before the first actual position.
		 *
		 * @return mixed The previous item
		 */
		public function prev() {
			if ($this->intCursor > 0) {
				$this->intCursor--;
				return $this->current();
			} else {
				$this->intCursor = -1;
			}
		}
		
		
		/**
		 * Returns the first item in the list.
		 *
		 * @access public
		 * @return mixed The first item
		 */
		public function first() {
			return (isset($this->arrItems[0]) ? $this->arrItems[0] : null);
		}
		
		
		/**
		 * Returns the last item in the list.
		 *
		 * @access public
		 * @return mixed The last item
		 */
		public function last() {
			return (isset($this->arrItems[$this->intCount - 1]) ? $this->arrItems[$this->intCount - 1] : null);
		}
		
		
		/**
		 * Returns all the items in the list.
		 *
		 * @access public
		 * @return array The array of items
		 */
		public function items() {
			return $this->arrItems;
		}
		
		
		/*****************************************/
		/**     MAGIC METHODS                   **/
		/*****************************************/
		
		
		/**
		 * Returns the list of variables that should be stored
		 * when the object is serialized. By default it only
		 * stores the array of items.
		 *
		 * @access public
		 * @return array The array of properties to serialize
		 */
		public function __sleep() {
			return array('arrItems');
		}
		
		
		/**
		 * Re-initializes the object properties.
		 *
		 * @access public
		 */
		public function __wakeup() {
			$this->init();
		}
	}