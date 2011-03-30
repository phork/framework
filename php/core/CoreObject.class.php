<?php
	/**
	 * CoreObject.class.php
	 *
	 * All non-static classes in the core package should
	 * extend this class.
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
	 * @abstract
	 */
	abstract class CoreObject {
		
		/**
		 * Returns a string representation of the object.
		 *
		 * @access public
		 * @return string The class name of the object
		 */
		public function __toString() {
			return get_class($this);
		}
		
		
		/**
		 * Throws an exception when an invalid method is called.
		 *
		 * @access public
		 * @param string $strMethodName The method called
		 * @param array $arrParameters The parameters passed to the method
		 */
		public function __call($strMethodName, $arrParameters) { 
			throw new CoreException(AppLanguage::translate('Method %s does not exist', $strMethodName));
		}
	}