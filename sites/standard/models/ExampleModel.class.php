<?php
	require_once('php/core/CoreDatabaseModel.class.php');

	/**
	 * ExampleModel.class.php
	 * 
	 * Used to add, edit, delete and load the example records
	 * from the database using the database model.
	 *
	 * @author Elenor Collings <elenor@phork.org>
	 * @package wemo-ocean
	 * @subpackage models
	 */
	class ExampleModel extends CoreDatabaseModel {
		
		protected $strTable = 'examples';
		protected $strPrimaryKey = 'exampleid';
		
		protected $arrInsertCols = array('userid', 'name', 'created', 'updated');
		protected $arrUpdateCols = array('name', 'updated');
		
		
		/**
		 * Includes the record class, sets up an iterator 
		 * object to hold the records, and sets up an event 
		 * key which is used to register and run events in
		 * the event object.
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
							'Property'		=> $this->strPrimaryKey,
							'Unique'		=> true,
							'Type'			=> 'integer',
							'Error'			=> 'Invalid ID'
						),
						
						'UserId'		=> array(
							'Property'		=> 'userid',
							'Required'		=> true,
							'Type'			=> 'integer',
							'Error'			=> 'Invalid user ID'
						),
						
						'Name'			=> array(
							'Property'		=> 'name',
							'Required'		=> true,
							'Type'			=> 'string',
							'Error'			=> 'Invalid name'
						)
					));
					
					$this->initHelper('validation', array('validateAll'));
				}
			}
			
			if (!empty($arrConfig['Relations'])) {
				if (AppLoader::includeExtension('helpers/', 'ModelRelations')) {
					$this->appendHelper('relations', 'ModelRelations', array(
						'BelongsToOne'	=> array(
							'User'			=> array(
								'LoadAs'		=> 'user',
								'AutoLoad'		=> false,
								'ClassName'		=> 'UserModel',
								'Dependent'		=> true,
								'Conditions'	=> array(
									array(
										'Column' 	=> 'userid',
										'Property' 	=> 'userid',
										'Operator'	=> '='
									)
								)
							)
						),
						
						'HasOne'		=> array(
							'Bar'			=> array(
								'LoadAs'		=> 'bar',
								'AutoLoad'		=> false,
								'ClassName'		=> 'BarModel',
								'Dependent'		=> false,
								'Conditions'	=> array(
									array(
										'Column' 	=> 'barid',
										'Property' 	=> 'barid',
										'Operator'	=> '='
									)
								)
							)
						),
						
						'HasMany'		=> array(
							'Foos'			=> array(
								'LoadAs'		=> 'foos',
								'AutoLoad'		=> false,
								'ClassName'		=> 'FooModel',
								'Dependent'		=> false,
								'Conditions'	=> array(
									array(
										'Column' 	=> 'fooid',
										'Property' 	=> $this->strPrimaryKey,
										'Operator'	=> '='
									)
								)
							)
						)
					));
					
					if (!empty($arrConfig['RelationsAutoLoad'])) {
						$this->initHelper('relations', array('loadAutoLoad'), array(
							'Recursion' => isset($arrConfig['RelationsRecursion']) ? $arrConfig['RelationsRecursion'] : 0
						));
					}
				}
			}
		}
		
		
		/**
		 * Adds the save helpers. This has been broken out
		 * because save helpers don't need to be added all
		 * the time.
		 *
		 * @access protected
		 */
		protected function addSaveHelpers() {
			if (empty($this->arrConfig['NoSaveHelpers'])) {
				if (!array_key_exists('cache-bust-save', $this->arrHelpers)) {
					if (AppLoader::includeExtension('helpers/', 'ModelCache')) {
						$this->appendHelper('cache-bust-save', 'ModelCache');
						$this->initHelper('cache-bust-save', array('postSave'));
					}
				}
			}
		}
		
		
		/*****************************************/
		/**     EVENT CALLBACKS                 **/
		/*****************************************/
		
		
		/**
		 * Sets any default values before saving including the
		 * created and updated dates.
		 *
		 * @access public
		 */
		public function setDefaults() {
			$objDb = AppRegistry::get('Database');
			if (!$this->current()->get(self::ID_PROPERTY)) {
				$this->current()->set('created', date($objDb->getDatetimeFormat()));
			}
			$this->current()->set('updated', date($objDb->getDatetimeFormat()));
		}
		
		
		/*****************************************/
		/**     LOAD METHODS                    **/
		/*****************************************/
		
		
		/**
		 * A shortcut function to load the records by the user ID
		 * passed. This does not clear out any previously loaded
		 * data. That should be done explicitly.
		 *
		 * @access public
		 * @param mixed $intUserId The user ID to load by
		 * @param array $arrFilters Any additional filters as well as the limits
		 * @param boolean $blnCalcFoundRows Whether to calculate the total number of matching rows
		 * @return boolean True if the query executed successfully
		 */
		public function loadByUserId($intUserId, $arrFilters = array(), $blnCalcFoundRows = false) {
			$arrFunctionArgs = func_get_args();
			$this->setLoading(__FUNCTION__, $arrFunctionArgs);
			
			if (!array_key_exists('Conditions', $arrFilters)) {
				$arrFilters['Conditions'] = array();
			}
			$arrFilters['Conditions'][] = array(
				'Column'	=> $this->strTable . '.userid',
				'Value' 	=> $intUserId,
				'Operator'	=> '='
			);
			
			$this->addDefaultOrder($arrFilters);
			$blnResult = $this->load($arrFilters, $blnCalcFoundRows);
			
			$this->clearLoading();
			return $blnResult;
		}
		
		
		/**
		 * Returns the query to load a record from the database.
		 * Has additional handling to join on the user table.
		 *
		 * @access protected
		 * @param array $arrFilters The filters to load by
		 * @param boolean $blnCalcFoundRows Whether to calculate the total number of matching rows
		 * @return array The load query
		 */
		protected function getLoadQuery($arrFilters, $blnCalcFoundRows) {
			$objQuery = AppRegistry::get('Database')->getQuery()->select($blnCalcFoundRows)->from($this->strTable);			
			$objQuery->addColumn($this->strTable . '.*');
			
			if (!empty($this->arrConfig['WithUserJoin']) || !empty($arrFilters['WithUserJoin'])) {
				$objQuery->addColumn('u.username');
				$objQuery->addTableJoin('users', 'u', array(array($this->strTable . '.userid', 'u.userid')));
			}
			
			if ($this->addQueryFilters($objQuery, $arrFilters)) {
				return $objQuery->buildQuery();
			}
		}
		
		
		/**
		 * Adds the default order to the filters array.
		 *
		 * @access protected
		 * @param array $arrFilters The array of existing filters
		 */
		protected function addDefaultOrder(&$arrFilters) {
			if (!array_key_exists('Order', $arrFilters)) {
				$arrFilters['Order'] = array();
			}
			$arrFilters['Order'][] = array(
				'Column'	=> $this->strTable . '.updated',
				'Sort'		=> 'DESC'
			);
		}
		
		
		/*****************************************/
		/**     SAVE METHODS                    **/
		/*****************************************/
		
		
		/**
		 * Saves a record to the database. Has additional
		 * handling to initialize any save helpers.
		 *
		 * @access public
		 * @param boolean $blnForceInsert Whether to force insert a record even though it has an ID
		 * @return boolean True on success
		 */
		public function save($blnForceInsert = false) {
			$this->addSaveHelpers();
			return parent::save($blnForceInsert);
		}
	}