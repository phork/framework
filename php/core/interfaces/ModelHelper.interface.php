<?php
	/**
	 * ModelHelper.interface.php
	 *
	 * The interface for the model helper classes to
	 * implement.
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
	interface ModelHelper {
	
		public function __construct($strEventKey, $arrConfig);
		public function init($arrEvents, $arrConfig = array());
		public function destroy();
	}