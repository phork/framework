<?php
	/**
	 * CoreException.class.php
	 *
	 * The core system throws a CoreException for any fatal
	 * application error. This also handles uncaught exceptions
	 * by printing out a backtrace.
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
	class CoreException extends Exception {
	
		/**
		 * The core exception object's constructor.
		 *
		 * It is highly recommended that this call parent::__construct() 
		 * to ensure all available data has been properly assigned.
		 *
		 * @access public
		 * @param string $strMessage The exception error
		 * @param integer $intCode The exception code
		 */
		public function __construct($strMessage = null, $intCode = 0) {
			parent::__construct($strMessage, $intCode);
			set_exception_handler(array($this, 'handleException'));
		}
		
		
		/**
		 * The default exception handler. This prints out the 
		 * exception backtrace.
		 *
		 * @access public
		 */
		public function handleException() {
			$this->flushBuffer();
				
			print '<pre>' . $this->getBacktrace() . '</pre>';
			exit;
		}
		
		
		/**
		 * Flushes any output buffers so data can be displayed.
		 *
		 * @access public
		 */
		public function flushBuffer() {
			if ($arrHandlers = ob_list_handlers()) {
				for ($i = 0, $ix = count($arrHandlers); $i < $ix; $i++) {
					@ob_end_clean();
				}
			}
		}
		
		
		/**
		 * Returns a backtrace of the exception.
		 *
		 * @access public
		 * @return string The backtrace string
		 */
		public function getBacktrace() {	 
			$strError = "{$this->getMessage()} in {$this->getFile()} on line {$this->getLine()}\n\n";
			$strError .= "Backtrace: \n";
			
			$i = 0;
			foreach ($this->getTrace() as $arrTrace) {
				$strError .= ++$i . ') ';
				foreach ($arrTrace as $strKey=>$mxdTrace) {
					$strError .= "\t{$strKey}: " . $this->formatBacktrace($mxdTrace) . "\n";
				}
				$strError .= "\n\n";
			}
			
			return $strError;
		}
		
		
		/**
		 * Formats the backtrace data to handle objects
		 * and arrays. Only prints out the first level of
		 * array data otherwise the memory limit could be
		 * exceeded.
		 *
		 * @access protected
		 * @param mixed $mxdTrace The trace data to format
		 * @param integer $intLevel The number of level deep the formatting is
		 * @return string The formatted backtrace
		 */
		protected function formatBacktrace($mxdTrace, $intLevel = 0) {
			$strError = '';
			if (is_array($mxdTrace)) {
				if ($intLevel < 1) {
					$arrError = array();
					foreach ($mxdTrace as $mxdTraceElement) {
						$arrError[] = $this->formatBacktrace($mxdTraceElement, ++$intLevel);
					}
					$strError .= implode(', ', $arrError);
				} else {
					$strError .= '[Array]';
				}
			} else {
				$strError .= "$mxdTrace";
			}
			return $strError;
		}
	}