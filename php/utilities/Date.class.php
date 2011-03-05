<?php
	/**
	 * Date.class.php
	 * 
	 * A class for handling date and time data.
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
	class Date {
	
		/**
		 * Returns "N hours/minutes/seconds ago" if the date is 
		 * recent, otherwise it returns "on" and the formatted
		 * date.
		 *
		 * @access public
		 * @param mixed $mxdTimestamp The timestamp to format preferably as a unix timestamp
		 * @param integer $intHourLimit The number of hours until the formatter returns the date, as usual
		 * @param boolean $blnAbbreviate Whether to abbreviate hours, minutes and secods
		 * @return string The date in the "time ago" format
		 * @static
		 */
		static public function getTimeAgo($mxdTimestamp, $intHourLimit = 24, $strAltFormat = 'M d, Y @ g:ia', $blnAbbreviate = false) {
			if (!is_numeric($mxdTimestamp)) {
				if (($intTimestamp = strtotime($mxdTimestamp)) < 1) {
					return;
				}
			} else {
				$intTimestamp = $mxdTimestamp;
			}
			
			$intSeconds = time() - $intTimestamp;
			$intHours = floor($intSeconds / 3600);
			
			if ($intHours >= 0 && $intHours < $intHourLimit) {
				if (!$intHours) {
					$intMinutes = floor($intSeconds / 60);
					
					if (!$intMinutes) {
						if ($intSeconds == 0) {
							return 'just now';
						}
						$intAmount = $intSeconds;
						$strUnit = $blnAbbreviate ? 'sec' : 'second';
					} else {
						$intAmount = $intMinutes;
						$strUnit = $blnAbbreviate ? 'min' : 'minute';
					}
				} else {
					$intAmount = $intHours;
					$strUnit = $blnAbbreviate ? 'hr' : 'hour';
				}
				
				return sprintf('%d %s%s ago', $intAmount, $strUnit, ($intAmount != 1 ? 's' : ''));
			} else {
				return 'on ' . date($strAltFormat, $intTimestamp);
			}
		}
	}