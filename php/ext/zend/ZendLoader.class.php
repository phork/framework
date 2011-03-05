<?php
	require_once('php/core/CoreStatic.class.php');
	
	/**
	 * ZendLoader.class.php
	 * 
	 * The Zend loader class is a small wrapper for Zend's
	 * loader class with additional functionality to set up
	 * the include path.
	 *
	 * <code>
	 * AppLoader::includeExtension('zend/', 'ZendLoader');
	 * ZendLoader::includeClass('Zend_Rest_Client');
	 * $objRest = new Zend_Rest_Client();
	 * </code>
	 *
	 * Copyright 2006-2010, Phork Labs. (http://phorklabs.com)
	 *
	 * Licensed under The MIT License
	 * Redistributions of files must retain the above copyright notice.
	 *
	 * @author Elenor Collings <elenor@phork.org>
	 * @license http://www.opensource.org/licenses/mit-license.php The MIT License
	 * @package phork
	 * @subpackage zend
	 */
	class ZendLoader extends CoreStatic {
		
		/**
		 * Includes a Zend class and sets up the include path if
		 * the Zend path hasn't been added.
		 *
		 * @access public
		 * @param string $strClass The class name
		 * @param mixed $mxdDirs A path or an array of paths to search
		 * @static
		 */
		static public function includeClass($strClass, $mxdDirs = null) {
			if (!class_exists('Zend_Loader')) {
				AppConfig::load('zend');
				set_include_path(get_include_path() . PATH_SEPARATOR . AppConfig::get('ZendBase'));
				require_once('Zend/Loader.php');
			}
			Zend_Loader::loadClass($strClass, $mxdDirs);
		}
	}