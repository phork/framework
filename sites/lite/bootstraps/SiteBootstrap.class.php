<?php
	require_once('php/core/CoreBootstrap.class.php');
	
	/**
	 * SiteBootstrap.class.php
	 * 
	 * The bootstrap sets up the site-wide libs and
	 * configs and delegates processing to a controller.
	 *
	 * This should also require the default controller
	 * at the top of the file. 
	 *
	 * Copyright 2006-2010, Phork Labs. (http://phorklabs.com)
	 *
	 * Licensed under The MIT License
	 * Redistributions of files must retain the above copyright notice.
	 *
	 * @author Elenor Collings <elenor@phork.org>
	 * @license http://www.opensource.org/licenses/mit-license.php The MIT License
	 * @package phork-lite
	 * @subpackage bootstraps
	 */
	class SiteBootstrap extends CoreBootstrap {
	
		protected $strDefaultController = 'SiteController';
	}