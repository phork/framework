<?php
	/**
	 * Url.interface.php
	 *
	 * The interface for the URL classes to implement.
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
	interface Url {
	
		public function init($strMethod = null, $strUrl = null, $arrVariables = null);
		public function getMethod();
		public function getUrl();
		public function getBaseUrl();
		public function getCurrentUrl($blnQueryString = true, $blnCleanUrl = true);
		public function getExtension();
		public function getSegment($intPosition);
		public function getSegments();
		public function getFilter($strFilter);
		public function getFilters();
		public function getVariable($strVariable);
		public function getVariables();
		public function setRoutes($arrRoutes);
	}