<?php
	/**
	 * SessionHandler.interface.php
	 *
	 * The interface for the session handler classes 
	 * to implement.
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
	interface SessionHandler {
	
		public function open($strSavePath, $strSessionName);
		public function close();
		public function read($strId);
		public function write($strId, $strData);
		public function destroy($strId);
		public function cleanup($intLifetime);
	}