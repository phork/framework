<?php
	/**
	 * Socket.class.php
	 * 
	 * A class for handling socket functions. This
	 * currently handles automatic form postings.
	 *
	 * Copyright 2006-2010, Phork Labs. (http://phorklabs.com)
	 *
	 * Licensed under The MIT License
	 * Redistributions of files must retain the above copyright notice.
	 *
	 * @author Elenor Collings <elenor@phork.org>
	 * @license http://www.opensource.org/licenses/mit-license.php The MIT License
	 * @package phork
	 * @subpackage utilities
	 */
	class Socket {
		
		/**
		 * Posts a form and returns the output.
		 *
		 * @access public
		 * @param string $strPostUrl The Url to post the form to
		 * @param array $arrData The array of data to post
		 * @param string $strHost The host to open the socket to
		 * @param integer $intPort The port to use
		 * @return string The content of the result page
		 * @static
		 */
		static public function postForm($strPostUrl, $arrData = array(), $strHost = null, $intPort = null) {
			
			//if no host submitted, use the current one
			if (!$strHost) {
				$strHost = $_SERVER['SERVER_NAME'];
			}
			
			//if no port submitted, use the current one
			if (!$intPort) {
				$intPort = $_SERVER['SERVER_PORT'];
			}
			
			//this must be set to the user's user agent for the session fingerprint to work
			$strUserAgent = $_SERVER['HTTP_USER_AGENT'];
			
			//open the socket
			if (!($rscSocket = fsockopen($strHost, $intPort, $intError, $strError, 30))) {
				trigger_error(AppLanguage::translate('There was an error posting the form (1)'));
				return false;
			}
			
			//format the post data
			$strData = http_build_query($arrData);
			
			//build the cookie data
			$strCookie = '';
			if ($strHost == $_SERVER['SERVER_NAME']) {
				foreach ($_COOKIE as $strKey=>$strValue) {
					$strCookie .= sprintf('%s=%s; ', $strKey, $strValue);
				}
				$strCookie = substr($strCookie, 0, -2);
			}
			
			$strHeader = "POST {$strPostUrl} HTTP/1.0\r\n";
			$strHeader.= "Host: {$strHost}\r\n";
			$strHeader.= "User-Agent: {$strUserAgent}\r\n";
			$strHeader.= "Content-Type: application/x-www-form-urlencoded\r\n";
			$strHeader.= "Cookie: {$strCookie}\r\n";
			$strHeader.= "Content-Length: ".strlen($strData)."\r\n";
			$strHeader.= "Connection: close\r\n\r\n";
			$strHeader.= $strData;
			
			//close the session or the script will time out
			if (session_id()) {
				session_write_close();
			}
			
			//do the actual post
			if (!fwrite($rscSocket, $strHeader)) {
				trigger_error(AppLanguage::translate('There was an error posting the form (2)'));
				return false;
			}
	
			//set the timeout
			stream_set_timeout($rscSocket, 4);
			
			//get the result page output; prevent feof infinite loop with first fread
			if ($strOutput = fread($rscSocket, 1024)) {
				while (!feof($rscSocket)) {
					$strOutput .= fread($rscSocket, 1024);
				}
			}
			
			//close the socket
			fclose($rscSocket);
			
			//split the body from the headers
			list(, $strOutput) = preg_split('/^\r?$/m', $strOutput, 2);
			
			//if the results came back empty it's an error
			if (empty($strOutput)) {
				trigger_error(AppLanguage::translate('There was an error retrieving the form results'));
				return false;
			}
			
			//return the results
			return ltrim($strOutput);
		}
	}