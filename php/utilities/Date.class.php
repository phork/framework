<?php
	require_once('php/core/CoreObject.class.php');
	require_once('php/core/interfaces/Singleton.interface.php');
	
	/**
	 * Date.class.php
	 * 
	 * A class for handling date and time data. This also
	 * accounts for a user's timezone.
	 *
	 * Copyright 2006-2011, Phork Labs. (http://phorklabs.com)
	 *
	 * @author Elenor Collings <elenor@phork.org>
	 * @package phork
	 * @subpackage utilities
	 */
	class Date extends CoreObject implements Singleton {
	
		static protected $objInstance;
		protected $intSystemOffset = 0;
		protected $intClientOffset = 0;
		
		
		/**
		 * The constructor can't be public for a singleton.
		 * This sets the timezone object for the system timezone.
		 *
		 * @access protected
		 */
		protected function __construct() {
			$objSystemTimezone = new DateTimeZone(date_default_timezone_get());
			$this->intSystemOffset = $objSystemTimezone->getOffset(new DateTime('now', new DateTimeZone('Europe/London'))) - (date('I') * 3600);
		}
		
		
		/** 
		 * Returns the instance of the singleton object. If
		 * it doesn't exist it instantiates it.
		 *
		 * @access public
		 * @return object The instance of the alert object
		 * @static
		 */
		static public function getInstance() {
			if (!self::$objInstance) {
				self::$objInstance = new Date();
			}
			return self::$objInstance;
		}
		
		
		/**
		 * Returns "N hours/minutes/seconds ago" if the date is 
		 * recent, otherwise it returns "on" and the formatted
		 * date.
		 *
		 * @access public
		 * @param mixed $mxdTimestamp The timestamp to format preferably as a unix timestamp
		 * @param integer $intHourLimit The number of hours until the formatter returns the date, as usual
		 * @param boolean $blnAbbreviate Whether to abbreviate hours, minutes and secods
		 * @param string $strAltFormat The date format to use if no within the hour limit
		 * @param boolean $blnFormatFuture Include future dates in the formatting
		 * @return string The date in the "time ago" format
		 * @static
		 */
		static public function getTimeAgo($mxdTimestamp, $intHourLimit = 24, $strAltFormat = 'M d, Y @ g:ia', $blnAbbreviate = false, $blnFormatFuture = true) {
			if (!is_numeric($intTimestamp = $mxdTimestamp)) {
				if (($intTimestamp = strtotime($mxdTimestamp)) < 1) {
					return;
				}
			}
			
			if (($intSeconds = time() - $intTimestamp) < 0 && $blnFormatFuture) {
				$intSeconds = abs($intSeconds);
				$blnInFuture = true;
			}
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
				
				if (!empty($blnInFuture)) {
					return sprintf('in %d %s%s', $intAmount, $strUnit, ($intAmount != 1 ? 's' : ''));
				} else {
					return sprintf('%d %s%s ago', $intAmount, $strUnit, ($intAmount != 1 ? 's' : ''));
				}
			} else {
				$objInstance = self::getInstance();
				return 'on ' . date($strAltFormat, $intTimestamp + ($objInstance->intClientOffset - $objInstance->intSystemOffset));
			}
		}
		
		
		/**
		 * Sets the client's timezone offset relative to GMT
		 * excluding daylight savings.
		 *
		 * @access public
		 * @param numeric $numClientOffset The timezone offset in hours (eg. -8)
		 * @static
		 */
		static public function setClientOffset($numClientOffset) {
			self::getInstance()->intClientOffset = $numClientOffset * 3600;
		}
		
		
		/**
		 * Returns the system timezone offset relative to GMT
		 * excluding daylight savings.
		 *
		 * @access public
		 * @return integer The system offset in seconds
		 * @static
		 */
		static public function getSystemOffset() {
			return self::getInstance()->intSystemOffset;
		}
	}