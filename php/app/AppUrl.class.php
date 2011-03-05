<?php
	require_once('php/core/CoreUrl.class.php');

	/**
	 * AppUrl.class.php
	 * 
	 * The URL class parses and routes the URL. The base
	 * URL is the application path relative to the document
	 * root, and including the filename when not using mod
	 * rewrite (eg. /admin or index.php).
	 *
	 * This is a singleton class and therefore it must
	 * be instantiated using the getInstance() method.
	 *
	 * Copyright 2006-2010, Phork Labs. (http://phorklabs.com)
	 *
	 * Licensed under The MIT License
	 * Redistributions of files must retain the above copyright notice.
	 *
	 * @author Elenor Collings <elenor@phork.org>
	 * @license http://www.opensource.org/licenses/mit-license.php The MIT License
	 * @package phork
	 * @subpackage app
	 */
	class AppUrl extends CoreUrl {
	
	}