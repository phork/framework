<?php	
	/**
	 * Timer.class.php
	 * 
	 * A simple script execution timer.
	 *
	 * <code>
	 * $objTimer = new Timer();
	 * $objTimer->init();
	 * ...
	 * $numTime = $objTimer->getTime();
	 * </code>
	 *
	 * Copyright 2006-2010, Phork Labs. (http://phorklabs.com)
	 *
	 * Licensed under The MIT License
	 * Redistributions of files must retain the above copyright notice.
	 *
	 * @author Elenor Collings <elenor@phork.org>
	 * @license http://www.opensource.org/licenses/mit-license.php The MIT License
	 * @package phork
	 * @subpackage utilities
	 */
	class Timer {
		
		private $numStart;
		protected $arrIntervals = array();
		
		
		/**
		 * Gets the current server time to be used
		 * with the timer methods.
		 *
		 * @access private
		 * @return float The server time in seconds
		 */
		private function getCurrentTime() {
			return microtime(true);
		}
	
	
		/**
		 * Initialize a new timer.
		 * 
		 * @access public
		 */
		public function init() {
			$this->numStart = $this->getCurrentTime();
		}
		
		
		/**
		 * Gets the time elapsed since init() was called.
		 *
		 * @access public
		 * @return float The elapsed time in seconds
		 */
		public function getTime() {
			return $this->getCurrentTime() - $this->numStart;
		}
		
		
		/**
		 * Records the interval between starting and
		 * the time at which this is called.
		 *
		 * @access public
		 * @param string $strName The name of the interval for identification
		 * @param boolean $blnRelative Whether the interval should be relative to the one before
		 */
		public function addInterval($strName, $blnRelative = false) {
			$numTime = $this->getTime();
			
			//if the time is relative, subtract the previous interval
			if ($blnRelative == true && ($intCount = count($this->arrIntervals))) {
				$numTime -= $this->arrIntervals[$intCount - 1]['Time'];
			}
		
			$this->arrIntervals[$strName] = $numTime;
		}
		
		
		/**
		 * Returns all the intervals recorded.
		 *
		 * @access public
		 * @return array The array of intervals
		 */
		public function getIntervals() {
			return $this->arrIntervals;
		}
	}