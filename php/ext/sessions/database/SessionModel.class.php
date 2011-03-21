<?php
	require_once('php/core/CoreDatabaseModel.class.php');

	/**
	 * SessionModel.class.php
	 * 
	 * Used to add, edit, delete and load the session records
	 * from the database using the database model.
	 *
	 * Copyright 2006-2011, Phork Labs. (http://phorklabs.com)
	 *
	 * Licensed under The MIT License
	 * Redistributions of files must retain the above copyright notice.
	 *
	 * @author Elenor Collings <elenor@phork.org>
	 * @license http://www.opensource.org/licenses/mit-license.php The MIT License
	 * @package phork
	 * @subpackage models
	 */
	class SessionModel extends CoreDatabaseModel {
		
		protected $strTable = 'sessions';
		protected $strPrimaryKey = 'sessionid';
		
		protected $arrInsertCols = array('session', 'ipaddr', 'useragent', 'data', 'expires');
		protected $arrUpdateCols = array('ipaddr', 'useragent', 'data', 'expires');
		
		
		/**
		 * Includes the record class, sets up an iterator 
		 * object to hold the records, and sets up an event 
		 * key which is used to register and run events in
		 * the event object. This also sets up the relations
		 * helper to load relations and a validation helper.
		 *
		 * @access public
		 * @param array $arrConfig The config vars, including which helpers to use
		 */
		public function __construct($arrConfig = array()) {
			parent::__construct($arrConfig);
			$this->init($arrConfig);
		}
		
		
		/**
		 * Initializes any events and config actions. This 
		 * has been broken out from the constructor so cloned
		 * objects can use it. 
		 *
		 * @access public
		 * @param array $arrConfig The config vars, including which helpers to use
		 */
		public function init($arrConfig) {			
			AppEvent::register($this->strEventKey . '.pre-save', array($this, 'setDefaults'));
		
			if (!empty($arrConfig['Validate'])) {
				if (AppLoader::includeExtension('helpers/', 'ModelValidation')) {
					$this->appendHelper('validation', 'ModelValidation', array(
						'Id'			=> array(
							'Property'		=> 'sessionid',
							'Unique'		=> true,
							'Type'			=> 'integer',
							'Error'			=> 'Invalid ID'
						),
						
						'Ip'			=> array(
							'Property'		=> 'ip',
							'Required'		=> true,
							'Type'			=> 'string',
							'Error'			=> 'Invalid IP address'
						),
						
						'UserAgent'		=> array(
							'Property'		=> 'useragent',
							'Required'		=> true,
							'Type'			=> 'string',
							'Error'			=> 'Invalid user agent'
						),
				
						'Data'			=> array(
							'Property'		=> 'data',
							'Required'		=> true,
							'Type'			=> 'string',
							'Error'			=> 'Invalid password'
						),
					));
					
					$this->initHelper('validation', array('validateAll'));
				}
			}
		}
		
		
		/**
		 * Sets any default values before saving including the
		 * IP address and user agent.
		 *
		 * @access public
		 */
		public function setDefaults() {
			if (!$this->current()->get(self::ID_PROPERTY)) {
				$this->current()->set('ipaddr', $_SERVER['REMOTE_ADDR']);
				$this->current()->set('useragent', $_SERVER['HTTP_USER_AGENT']);
			}
		}
				
		
		/*****************************************/
		/**     LOAD METHODS                   **/
		/*****************************************/
		
		
		/**
		 * A shortcut function to load a record by session ID.
		 * This does not clear out any previously loaded data.
		 * That should be done explicitly.
		 *
		 * @access public
		 * @param string $strSession The session ID to load by
		 * @return boolean True if the query executed successfully
		 */
		public function loadBySession($strSession) {
			$arrFunctionArgs = func_get_args();
			$this->setLoading(__FUNCTION__, $arrFunctionArgs);
			
			$blnResult = $this->load(array(
				'Conditions' => array(
					array(
						'Column' 	=> 'session',
						'Value'  	=> $strSession
					)
				)		
			));
			
			$this->clearLoading();
			return $blnResult;
		}
		
		
		/**
		 * A shortcut function to load a record by session ID.
		 * This only loads unexpired sessions. This does not 
		 * clear out any previously loaded data. That should 
		 * be done explicitly.
		 *
		 * @access public
		 * @param string $strSession The session ID to load by
		 * @return boolean True if the query executed successfully
		 */
		public function loadCurrentBySession($strSession) {
			$arrFunctionArgs = func_get_args();
			$this->setLoading(__FUNCTION__, $arrFunctionArgs);
			
			$blnResult = $this->load(array(
				'Conditions' => array(
					array(
						'Column' 	=> 'session',
						'Value'  	=> $strSession
					),
					array(
						'Column' 	=> 'expires',
						'Operator'	=> '>=',
						'Value'  	=> date(AppRegistry::get('Database')->getDatetimeFormat())
					)
				)		
			));
			
			$this->clearLoading();
			return $blnResult;
		}
		
		
		/*****************************************/
		/**     DELETE METHODS                  **/
		/*****************************************/
		
		
		/**
		 * Deletes all the expired sessions. Currently does
		 * this with a single query but in the event that
		 * events need triggering for each record this can
		 * be changed to loadExpired, loop through and then
		 * destroy.
		 *
		 * @access public
		 * @return boolean True on success
		 */
		public function deleteExpired() {
			return $this->delete(array(
				'Conditions' => array(
					array(
						'Column' 	=> 'expires',
						'Operator'	=> '<',
						'Value'  	=> date(AppRegistry::get('Database')->getDatetimeFormat())
					)
				)			
			));
		}
	}