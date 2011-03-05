<?php
	/**
	 * DebugHandler.interface.php
	 *
	 * The interface for the debug handler classes 
	 * to implement.
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
	 */
	interface DebugHandler {
	
		public function handle($strDebug);
	}