<?php
	/**
	 * Conversion.class.php
	 * 
	 * A class for converting data and units of measurement
	 * from one format to another.
	 *
	 * Copyright 2006-2011, Phork Labs. (http://phorklabs.com)
	 *
	 * Licensed under The MIT License
	 * Redistributions of files must retain the above copyright notice.
	 *
	 * @author Elenor Collings <elenor@phork.org>
	 * @license http://www.opensource.org/licenses/mit-license.php The MIT License
	 * @package phork
	 * @subpackage utilities
	 */
	class Conversion {			
		
		/**
		 * Converts bytes into the most appropriate size.
		 *
		 * @access public
		 * @param integer $intBytes The number of bytes to convert
		 * @static
		 */
		static public function convertBytes($intBytes) {
			$arrUnits = array('B', 'kB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB');
			
			$intUnit = (int) $intBytes ? floor(log($intBytes) / log(1024)) : 0;
			return round($intBytes / pow(1024, $intUnit), 2) . $arrUnits[$intUnit];
		}
	}