<?php
	require_once('php/core/CoreObject.class.php');
	require_once('php/core/interfaces/Singleton.interface.php');
	
	/**
	 * CoreSession.class.php
	 * 
	 * The session class dispatches to a session handler
	 * The most common use of this is to store the session
	 * data in the database.
	 *
	 * This is a singleton class and therefore it must
	 * be instantiated using the getInstance() method.
	 *
	 * <code>
	 * CoreSession::addHandler(new SessionDatabase());
	 * session_start();
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
	 * @subpackage core
	 * @final
	 */
	final class CoreSession extends CoreObject implements Singleton {
		
		static protected $objInstance;
		protected $objHandler;
		protected $blnClosed;
		
		
		/**
		 * The constructor can't be public for a singleton.
		 * This sets up itself as the session handler.
		 *
		 * @access protected
		 */
		protected function __construct() {
			$strSelf = get_class();
					
			AppEvent::register('display.pre-headers', array($strSelf, 'writeClose'));
			register_shutdown_function(array($strSelf, 'writeClose'));
			
			ini_set('session.gc_divisor', 100);
			ini_set('session.gc_probability', AppConfig::get('SessionGcProbability'));
			ini_set('session.gc_maxlifetime', AppConfig::get('SessionGcLifetime'));
			
			session_set_save_handler(
				array($strSelf, 'handleOpen'),
				array($strSelf, 'handleClose'),
				array($strSelf, 'handleRead'),
				array($strSelf, 'handleWrite'),
				array($strSelf, 'handleDestroy'),
				array($strSelf, 'handleCleanup')
			);
		}
		
		
		/** 
		 * Returns the instance of the singleton object. If
		 * it doesn't exist it instantiates it.
		 *
		 * @access public
		 * @return object The instance of the session object
		 * @static
		 */
		static public function getInstance() {
			if (!self::$objInstance) {
				self::$objInstance = new CoreSession();
			}
			return self::$objInstance;
		}
		
		
		/**
		 * Writes the session data and ends the session.
		 *
		 * @access public
		 * @static
		 */
		static public function writeClose() {
			$objInstance = self::getInstance();
			if (!$objInstance->blnClosed) {
				$objInstance->blnClosed = true;
				session_write_close();
			}
		}
		
		
		/**
		 * Sets the session handler. The handlers do all the
		 * saving and retrieving of the session data.
		 *
		 * @access public
		 * @param object $objHandler The handler object
		 */
		static public function setHandler(SessionHandler $objHandler) {
			$objInstance = self::getInstance();
			$objInstance->objHandler = $objHandler;
		}
		
		
		/*****************************************/
		/**     HANDLER METHODS                 **/
		/*****************************************/
		
		
		/**
		 * The open function works like a contructor and is
		 * executed when the session is open.
		 * 
		 * @access public
		 * @param string $strSavePath The save path for file-based sessions
		 * @param string $strSessionName The name of the session
		 * @return boolen True
		 * @static
		 */
		static public function handleOpen($strSavePath, $strSessionName) {
			$objInstance = self::getInstance();
			if ($objHandler = $objInstance->objHandler) {
				$objHandler->open($strSavePath, $strSessionName);
				return true;
			}
		}
		
		
		/**
		 * The close function works like a destructor and is
		 * executed when the session operation is done.
		 * 
		 * @access public
		 * @return boolen True
		 * @static
		 */
		static public function handleClose() {
			$objInstance = self::getInstance();
			if ($objHandler = $objInstance->objHandler) {
				$objHandler->close();
				return true;
			}
		}
		
		
		/**
		 * The read function retrieves and returns the session
		 * data. It must return a string.
		 *
		 * @access public
		 * @param string $strId The session ID to read
		 * @return string The session data
		 * @static
		 */
		static public function handleRead($strId) {
			$objInstance = self::getInstance();
			if ($objHandler = $objInstance->objHandler) {
				return (string) $objHandler->read($strId);
			}
		}
		
		
		/**
		 * The write function writes the session data. It's not
		 * executed until after the output stream is closed. Output
		 * will never be seen in the browser.
		 *
		 * @access public
		 * @param string $strId The session ID to write
		 * @param string $strData The session data to write
		 * @return boolean True on success
		 * @static
		 */
		static public function handleWrite($strId, $strData) {
			$objInstance = self::getInstance();
			if ($objHandler = $objInstance->objHandler) {
				return $objHandler->write($strId, $strData);
			}
		}
		
		
		/**
		 * The destroy function is executed when the session 
		 * is destroyed with session_destroy().
		 *
		 * @access public
		 * @param string $strId The session ID to destroy
		 * @return boolean True on success
		 * @static
		 */
		static public function handleDestroy($strId) {
			$objInstance = self::getInstance();
			if ($objHandler = $objInstance->objHandler) {
				return $objHandler->destroy($strId);
			}
		}
		
		
		/**
		 * The clean up function is a garbage collector for
		 * removing old session data.
		 *
		 * @access public
		 * @return boolen True
		 * @param integer $intLifetime The session's maximum lifetime
		 * @static
		 */
		static public function handleCleanup($intLifetime) {
			$objInstance = self::getInstance();
			if ($objHandler = $objInstance->objHandler) {
				$objHandler->cleanup($intLifetime);
				return true;
			}
		}
	}