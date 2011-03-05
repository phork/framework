<?php
	/**
	 * CoreRecord.class.php
	 * 
	 * Stores a record from a data source such as a
	 * database. Usually used in conjunction with a 
	 * CoreModel[Datatype] object.
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
	class CoreRecord {
	
		/**
		 * Returns the value of the property passed. This can
		 * be extended to format the return value as needed
		 * or to map the object property to a data source
		 * property with a different name.
		 *
		 * @access public
		 * @param string $strProperty The name of the property to get
		 * @return mixed The value of the property
		 */
		public function get($strProperty) {
			if (property_exists($this, $strProperty)) {
				return $this->$strProperty;
			}
		}
		
		
		/**
		 * Sets the value of the property to the value passed.
		 * This can be extended to format the value as needed
		 * or to map the object property to a data source
		 * property with a different name.
		 *
		 * @access public
		 * @param string $strProperty The name of the property to set
		 * @param mixed $mxdValue The value to set the property to
		 * @return mixed The value the property was set to
		 */
		public function set($strProperty, $mxdValue) {
			return $this->$strProperty = $mxdValue;
		}
		
		
		/**
		 * Method called to set a property. Currently just
		 * dispatches to the main set method. The name of
		 * the property passed here will always be the same
		 * name as the data source property. If the get and
		 * set methods have been extended to map the property
		 * then this will need to be extended as well.
		 *
		 * @access public
		 * @param string $strProperty The name of the property to set
		 * @param mixed $mxdValue The value to set the property to
		 * @return mixed The value the property was set to
		 */
		public function __set($strProperty, $mxdValue) {
			return $this->set($strProperty, $mxdValue);
		}
		
		
		/**
		 * Returns a string representation of the object.
		 *
		 * @access public
		 * @return string
		 */
		public function __toString() {
			return get_class($this) . (($intId = $this->get('__id')) ? " #{$intId}" : '');
		}
	}