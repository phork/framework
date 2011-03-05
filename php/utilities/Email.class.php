<?php	
	/**
	 * Email.class.php
	 *
	 * Sends emails with the correct headers.
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
	class Email {
		
		/**
		 * Sends a plain text email.
		 *
		 * @access public
		 * @param string $strToEmail The address to send the email to
		 * @param string $strToName The name to address the email to
		 * @param string $strFromEmail The address to send the email from
		 * @param string $strFromName The name to send the email from
		 * @param string $strSubject The email subject
		 * @param string $strBody The email body
		 */
		static public function sendTextEmail($strToEmail, $strToName, $strFromEmail, $strFromName, $strSubject, $strBody) {
			$strTo = "{$strToName} <{$strToEmail}>";
			$strFrom = "{$strFromName} <{$strFromEmail}>";
			$strHeaders = "From: {$strFromName} <{$strFromEmail}>\r\n";
			
			return mail($strTo, $strSubject, $strBody, $strHeaders);
		}
	}