<?php
	require_once('php/core/CoreModelHelper.class.php');
	
	/**
	 * ModelCounter.class.php
	 * 
	 * This is a model helper class to handle updating
	 * a record count in a different model. For example
	 * if you have a blog model and a comment model and
	 * anytime a comment has been added to a blog, the
	 * blog model should have a counter updated. This
	 * registers events that are run by the model object.
	 * Any data returned from the event methods is available
	 * in the function that runs the helper event.
	 *
	 * This has an additional feature allowing the count
	 * update to be written to the cache and only made
	 * permanent in the database after every x updates
	 * where x is defined by the UpdateFrequency config var.
	 * The system does this by checking the modulus of the
	 * updated count and the UpdateFrequency config var. 
	 * There is also a SyncFrequency config var that does
	 * a full count and update. This checks if rand(1, 100)
	 * is less than or equal to the sync frequency and if 
	 * so the count is synced. A sync frequency of 0 means
	 * never sync, and 100 means always sync.
	 *
	 * In the case of this helper it's a good idea to
	 * append the helper from the model object's init
	 * method.
	 *
	 * The following code block should go in the Comment
	 * model's init method. The IdProperty is the comment
	 * property that is tied to the primary key of the
	 * blogs. The CountProperty is the blog property that
	 * holds the comment count.
	 *
	 * <code>
	 * if (AppLoader::includeExtension('helpers/', 'ModelCounter')) {
	 *		$this->appendHelper('counter', 'ModelCounter', array(
	 *			'IdProperty'		=> 'blogid',
	 *			'CountProperty'		=> 'comments',
	 *			'ClassName'			=> 'BlogModel',
	 *			'UpdateMethod'		=> 'updateCommentCount',
	 *			'SyncMethod'		=> 'syncCommentCount',
	 *			'UseCache'			=> true,
	 *			'CacheKey'			=> 'comments:count:%d',
	 *			'UpdateFrequency' 	=> 10,
	 *			'SyncFrequency'		=> 1
	 *		));
	 *		$this->initHelper('counter', array('incrementCount', 'decrementCount'));
	 * }
	 * </code>
	 *
	 * This code block would go in the BlogModel to update the
	 * database by incrementing the existing comment count by 
	 * the $intAlterBy, preferably using a query with the syntax
	 * set comment = comment + 1.
	 *
	 * <code>
	 * public function updateCommentCount($intAlterBy) {
	 *		...
	 *		return $blnSuccess;
	 * }
	 * </code>
	 *
	 * This code block would go in the BlogModel to sync the count.
	 * If $blnUpdateRecord is set then it would load the synced
	 * count back into the record object.
	 *
	 * <code>
	 * public function syncCommentCount($blnUpdateRecord = false) {
	 * 		...
	 *		return $blnSuccess
	 * }
	 * </code>
	 *
	 * Copyright 2006-2010, Phork Software. (http://www.phork.org)
	 *
	 * Licensed under The MIT License
	 * Redistributions of files must retain the above copyright notice.
	 *
	 * @author Elenor Collings <elenor@phork.org>
	 * @license http://www.opensource.org/licenses/mit-license.php The MIT License
	 * @package phork
	 * @subpackage helpers
	 */
	class ModelCounter extends CoreModelHelper {
		
		protected $objModelBackup;
		
		
		/**
		 * Initializes the helper object by registering
		 * the events to run. This should be called from
		 * a model object.
		 *
		 * @access public
		 * @param array $arrEvents The names of the events to register
		 * @param array $arrConfig An array of config vars specific to this initialization
		 */
		public function init($arrEvents, $arrConfig = array()) {
			
			//increment the counter on add
			if (in_array('incrementCount', $arrEvents)) {
				$this->arrEvents[] = array(
					'Event'	=> ($strEvent = $this->strModelKey . '.post-save'),
					'Key'	=> AppEvent::register($strEvent, array($this, 'incrementCount'))
				);
			}
			
			//decrement the counter on delete
			if (in_array('decrementCount', $arrEvents)) {
				$this->arrEvents[] = array(
					'Event'	=> ($strEvent = $this->strModelKey . '.pre-delete'),
					'Key'	=> AppEvent::register($strEvent, array($this, 'backupRecords'))
				);
				$this->arrEvents[] = array(
					'Event'	=> ($strEvent = $this->strModelKey . '.post-delete'),
					'Key'	=> AppEvent::register($strEvent, array($this, 'decrementCount'))
				);
			}
		}
		
		
		/**
		 * Backs up the records before the delete method is
		 * called so the necessary data is available for the
		 * decrementing.
		 *
		 * @access public
		 * @param object $objModel The model containing the countable records
		 * @param string $strFunction The delete function called
		 * @param array $arrFilters The array of filters used with the delete query
		 * @return array The result in extractable array format
		 */
		public function backupRecords($objModel, $strFunction, $arrFilters) {
			$this->objModelBackup = clone $objModel;
			if (!($blnResult = $this->objModelBackup->load($arrFilters))) {
				$blnResult = false;
			}
			
			return array(
				'blnResult' => $blnResult
			);
		}
		
		
		/**
		 * Increments the count when a record has been added.
		 * This only updates the count for the current record.
		 *
		 * @access public
		 * @param object $objModel The model containing the countable records
		 * @param string $strFunction The save function called
		 * @param boolean $blnNewRecord Whether a new record was inserted
		 * @param boolean $blnForceInsert Whether a record was forced to be inserted
		 * @param boolean $blnSuccess Whether the insert was successful
		 * @return array The result in extractable array format
		 */
		public function incrementCount($objModel, $strFunction, $blnNewRecord, $blnForceInsert, $blnSuccess) {
			if (($blnNewRecord || $blnForceInsert) && $blnSuccess) {
				$blnResult = $this->changeCount($objModel->current(), 1);
			}
			
			return array(
				'blnResult'	=> !empty($blnResult)
			);
		}
		
		
		/**
		 * Decrements the count when a record has been deleted.
		 * This allows all deleted records to be decremented.
		 *
		 * @access public
		 * @param object $objModel The model containing the countable records
		 * @param string $strFunction The delete function called
		 * @param array $arrFilters The array of filters used with the delete query
		 * @param boolean $blnSuccess Whether the insert was successful
		 * @return array The result in extractable array format
		 */
		public function decrementCount($objModel, $strFunction, $arrFilters, $blnSuccess) {
			if ($blnSuccess) {
				while (list(, $objRecord) = $this->objModelBackup->each()) {
					if (!$this->changeCount($objRecord, -1)) {
						$blnFailed = true;
					}
				}
				$this->objModelBackup->rewind();
			}
			
			return array(
				'blnResult'	=> $blnSuccess && empty($blnFailed)
			);
		}
		
		
		/**
		 * Handles the actual changing of the counter. The
		 * cached counter only caches the difference between
		 * the database and the real count, not the total
		 * count. Decrementing can't go below 0 so if the
		 * decremented amount is 0 this will force a sync in
		 * case it should actually have been -1.
		 *
		 * @access protected
		 * @param integer $intValue The amount to increment by (1 or -1)
		 * @return boolean True on success
		 */
		protected function changeCount($objRecord, $intValue) {
			$blnSync = rand(1, 100) <= $this->arrConfig['SyncFrequency'];
			
			if (!empty($this->arrConfig['UseCache']) && $objCache = AppRegistry::get('Cache', false)) {
				$objCache->initBase();
				$strCacheKey = sprintf($this->arrConfig['CacheKey'], $objRecord->get($this->arrConfig['IdProperty']));
				
				if (!$blnSync) {	
					$strFunction = $intValue < 0 ? 'decrement' : 'increment';
					if (($intCount = $objCache->$strFunction($strCacheKey, abs($intValue), false)) === false) {
						if ($objCache->save($strCacheRaceKey = "saving-{$strCacheKey}", 1, 10, Cache::CACHE_ADD_ONLY)) {
							$blnSync = true;
						}
					} else {
						if ($intCount === 0) {
							$blnSync = true;
						}
						$blnResult = true;
					}
				}
				
				if (!$blnSync) {
					if ($blnUpdate = !($intCount % $this->arrConfig['UpdateFrequency'])) {
						$intUpdateAmount = $intCount;
					}
				}
			} else {
				if ($blnUpdate = !$blnSync) {
					$intUpdateAmount = $intValue;
				}
			}
			
			if ($blnSync || $blnUpdate) {
				AppLoader::includeModel($strModelClass = $this->arrConfig['ClassName']);
				$objRelation = new $strModelClass(array('NoCachedCounts' => true));
				$objRelation->import(array(
					'__id' => $objRecord->get($this->arrConfig['IdProperty'])
				));
				
				if ($blnSync) {
					if ($blnResult = $objRelation->{$this->arrConfig['SyncMethod']}($this->arrConfig['CountProperty'])) {
						if (!empty($objCache)) {
							if (isset($strCacheRaceKey)) {
								$objCache->delete($strCacheRaceKey);
							}
							$objCache->save($strCacheKey, 0, 0);
						}
					}
				} else {
					if ($blnResult = $objRelation->{$this->arrConfig['UpdateMethod']}($this->arrConfig['CountProperty'], $intUpdateAmount)) {
						if (!empty($objCache)) {
							$intResult = $objCache->decrement($strCacheKey, $intUpdateAmount, false);
						}
					}
				}
			}
			
			return !empty($blnResult);
		}
	}