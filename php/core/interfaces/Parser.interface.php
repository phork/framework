<?php
	/**
	 * Parser.interface.php
	 *
	 * The interface for the parser classes to implement.
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
	interface Parser {
	
		public function loadConfigString($strConfig);
		public function loadConfigFile($strFilePath);
		public function getConfig();
		public function getConfigSection($strSection, $blnRequired = false);
	}