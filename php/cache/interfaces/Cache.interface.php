<?php
	/**
	 * Cache.interface.php
	 *
	 * The interface for all tiered cache objects 
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
	 * @subpackage cache
	 */
	interface Cache {
		
		const CACHE_ADD_ONLY = 1;
		const CACHE_REPLACE_ONLY = 2;
		
		public function __construct(CacheTier $objBase, CacheTier $objPresentation);
		public function __destruct();
		static public function isAvailable();
		public function getTier($strTier);
		public function getTierTypes();
		public function addTier($strTier, CacheTier $objTier, $blnOverwrite = false);
		public function initTier($strTier, $blnAutoConnect = true);
		public function initBase($blnAutoConnect = true);
		public function initPresentation($blnAutoConnect = true);
		public function connect($strTier);
		public function close();
		public function getStats();
		public function save($strKey, $mxdValue, $intExpire = 0, $intSaveType = null);
		public function load($strKey);
		public function loadMulti($arrKeys);
		public function delete($strKey, $intTimeout = null);
		public function increment($strKey, $intValue = 1, $blnCreate = true);
		public function decrement($strKey, $intValue = 1);
		public function flush();
		public function saveNS($strKey, $strNamespace, $mxdValue, $intExpire = 0, $intSaveType = null);
		public function loadNS($strKey, $strNamespace);
		public function loadMultiNS($arrKeys, $strNamespace);
		public function deleteNS($strKey, $strNamespace, $intTimeout = null);
		public function flushNS($strNamespace);

	}