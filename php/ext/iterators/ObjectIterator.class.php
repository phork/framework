<?php
	require_once('php/core/CoreIterator.class.php');
		
	/**
	 * ObjectIterator.class.php
	 *
	 * The object iterator class stores a collection of
	 * objects that can be accessed in a standardized way.
	 *
	 * Copyright 2006-2011, Phork Labs. (http://phorklabs.com)
	 *
	 * Licensed under The MIT License
	 * Redistributions of files must retain the above copyright notice.
	 *
	 * @author Elenor Collings <elenor@phork.org>
	 * @license http://www.opensource.org/licenses/mit-license.php The MIT License
	 * @package phork
	 * @subpackage iterators
	 */
	class ObjectIterator extends CoreIterator {
	
		/**
		 * Validates the object being added to the list.
		 * Makes sure that it's the correct type of object.
		 *
		 * @access protected
		 * @param object $objRecord The record to validate
		 */
		protected function validate($objRecord) {
			if (!is_object($objRecord)) {
				throw new CoreException(AppLanguage::translate("'%s' is not an object", $objRecord));
			}
			
			return true;
		}
		
		
		/*****************************************/
		/**     RETRIEVAL METHODS               **/
		/*****************************************/
		
		
		/**
		 * Returns an associative array of the objects keyed
		 * by the property passed. If the aggregate flag is
		 * set to true and more than one object has the same 
		 * property value it will create a multi-dimensional 
		 * array of objects.
		 *
		 * @access public
		 * @param string $strProperty The property to key the results by
		 * @param boolean $blnAggregate Whether to create a multi-dimensional array (true) or to overwrite (false)
		 * @param boolean $blnForceAggregate Whether to force the result to be an array even if a single record was returned
		 * @return array The associative array
		 */
		public function getAssociativeList($strProperty, $blnAggregate = true, $blnForceAggregate = false) {
			$arrItems = array();
			
			foreach ($this->arrItems as $mxdItem) {
				if ($mxdValue = ($mxdItem->get($strProperty))) {
					if ($blnAggregate && !empty($arrItems[$mxdValue])) {
						if (is_array($arrItems[$mxdValue])) {
							$arrItems[$mxdValue][] = $mxdItem;
						} else {
							$arrItems[$mxdValue] = array($arrItems[$mxdValue], $mxdItem);
						}
					} else {
						if ($blnForceAggregate) {
							$arrItems[$mxdValue] = array($mxdItem);
						} else {
							$arrItems[$mxdValue] = $mxdItem;
						}
					}
				}
			}
			
			return $arrItems;
		}
	}