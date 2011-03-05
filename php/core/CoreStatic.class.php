<?php
	/**
	 * CoreStatic.class.php
	 * 
	 * The only purpose of this is to throw exceptions. The
	 * real work should be handled by the extensions.
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
	abstract class CoreStatic {
		
		/**
		 * The static object's constructor. This should
		 * never be called.
 		 *
		 * @access public
		 */
		public function __construct() {
			throw new CoreException(AppLanguage::translate('You cannot construct a static object'));
		}
	}