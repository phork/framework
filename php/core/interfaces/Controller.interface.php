<?php
	/**
	 * Controller.interface.php
	 *
	 * The interface for the controllers to implement.
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
	interface Controller {
	
		public function run();
		public function error($intErrorCode = null, $strException = null);
	}