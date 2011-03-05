<?php
	/**
	 * CacheTier.class.php
	 *
	 * The caching class can work with different caching
	 * tiers. The default tiers are Base which caches data,
	 * and Presentation which caches page output. This is
	 * used to define the tier settings.
	 *
	 * Copyright 2006-2010, Phork Labs. (http://phorklabs.com)
	 *
	 * Licensed under The MIT License
	 * Redistributions of files must retain the above copyright notice.
	 *
	 * @author Elenor Collings <elenor@phork.org>
	 * @license http://www.opensource.org/licenses/mit-license.php The MIT License
	 * @package phork
	 * @subpackage cache
	 */
	final class CacheTier {
	
		public $objCache;
		public $blnConnected;
		
		public $arrConfig;
		
		
		/**
		 * Sets up the cache servers and the default parameters.
		 *
		 * @access public
		 * @param array $arrConfig The array of servers or the filepath info
		 */
		public function __construct($arrConfig) {
			$this->arrConfig = $arrConfig;
		}
	}