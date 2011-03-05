<?php
	require_once('php/core/CoreRecord.class.php');
	
	/**
	 * CacheRecord.class.php
	 * 
	 * Stores a cache record from a data source with
	 * additional functionality to store the serialized
	 * cache data.
	 *
	 * Copyright 2006-2011, Phork Labs. (http://phorklabs.com)
	 *
	 * @author Elenor Collings <elenor@phork.org>
	 * @package phork
	 * @subpackage cache
	 */
	class CacheRecord extends CoreRecord {
	
		/**
		 * Sets the value of the property to the value passed.
		 * This has been extended to store the serialized data.
		 *
		 * @access public
		 * @param string $strProperty The name of the property to set
		 * @param mixed $mxdValue The value to set the property to
		 * @return mixed The value the property was set to
		 */
		public function set($strProperty, $mxdValue) {
			$this->$strProperty = $mxdValue;
			switch ($strProperty) {
				case 'data_raw':
					$this->data_raw = $mxdValue;
					if (is_array($mxdValue) || is_object($mxdValue)) {
						$this->format = 'serialized';
						$this->data = base64_encode(serialize($mxdValue));
					} else {
						$this->format = 'raw';
						$this->data = $mxdValue;
					}
					break;
			
				case 'data':
					$this->data = $mxdValue;
					if ($this->format == 'serialized') {
						$this->data_raw = unserialize(base64_decode($mxdValue));
					} else {
						$this->data_raw = $mxdValue;
					}				
					break;
			}
			return $this->$strProperty;
		}
	}