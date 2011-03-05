<?php
	require_once('php/core/CoreDisplay.class.php');
	
	/**
	 * AppDisplay.class.php
	 *
	 * The display class is used to buffer and output
	 * the content. It's also used to cache the content
	 * as necessary. If buffering is turned on then the 
	 * the content nodes won't be displayed right away.
	 * They will be stored in the object and can be
	 * rearranged and will be displayed when the object
	 * has been destroyed.
	 *
	 * This is a singleton class and therefore it must
	 * be instantiated using the getInstance() method.
	 *
	 * Copyright 2006-2011, Phork Labs. (http://phorklabs.com)
	 *
	 * Licensed under The MIT License
	 * Redistributions of files must retain the above copyright notice.
	 *
	 * @author Elenor Collings <elenor@phork.org>
	 * @license http://www.opensource.org/licenses/mit-license.php The MIT License
	 * @package phork
	 * @subpackage app
	 */
	class AppDisplay extends CoreDisplay {
	
	}