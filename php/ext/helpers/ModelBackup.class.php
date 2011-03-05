<?php
	require_once('php/core/CoreModelHelper.class.php');
	
	/**
	 * ModelBackup.class.php
	 * 
	 * This is a model helper class to handle backing up
	 * records into a separate table before deletion. For 
	 * example if a record should be deleted from the primary
	 * table but the deleted record still needs storing.
	 *
	 * <code>
	 * if (AppLoader::includeExtension('helpers/', 'ModelBackup')) {
	 * 		$this->appendHelper('backup', 'ModelBackup', array(
	 * 			'BackupTable'	=> 'foo-deleted'
	 * 		));
	 * 		$this->initHelper('backup', array('backupSave'), array(
	 * 			'Batch'			=> true,
	 * 			'Fatal'			=> false
	 * 		));
	 * }
	 * 
	 * if (AppLoader::includeExtension('helpers/', 'ModelBackup')) {
	 *		$this->appendHelper('backup', 'ModelBackup', array(
	 *			'BackupTable'	=> 'foo-deleted'
	 *		));
	 *		$this->initHelper('backup', array('backupDelete'), array(
	 *			'Batch'			=> true,
	 *			'Fatal'			=> false
	 *		));
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
	class ModelBackup extends CoreModelHelper {
		
		protected $arrBackedUpIds;
		protected $blnHelpers = false;
		
		
		/**
		 * Initializes the helper object by registering
		 * the events to run. This should be called from
		 * a model object. If the fatal config flag is set
		 * and the results aren't backed up successfuly
		 * the skip action flag is returned as true.
		 *
		 * @access public
		 * @param array $arrEvents The names of the events to register
		 * @param array $arrConfig An array of config vars specific to this initialization
		 */
		public function init($arrEvents, $arrConfig = array()) {
			$this->blnHelpers = !empty($arrConfig['Helpers']);
			
			//backup records before saving new ones
			if (in_array('backupSave', $arrEvents)) {
				$this->arrEvents[] = array(
					'Event'	=> ($strEvent = $this->strModelKey . '.pre-save'),
					'Key'	=> AppEvent::register($strEvent, array($this, 'backupSave'), array(
						!empty($arrConfig['Batch']),
						!empty($arrConfig['Fatal'])
					))
				);
			}
			
			//backup records before deleting them
			if (in_array('backupDelete', $arrEvents)) {
				$this->arrEvents[] = array(
					'Event'	=> ($strEvent = $this->strModelKey . '.pre-delete'),
					'Key'	=> AppEvent::register($strEvent, array($this, 'backupDelete'), array(
						!empty($arrConfig['Batch']),
						!empty($arrConfig['Fatal'])
					))
				);
			}
		}
		
		
		/**
		 * Clones the model and removes the backup method to
		 * prevent recursion. Doesn't use clone because that
		 * won't clone any helpers.
		 *
		 * @access protected
		 * @param object $objModel The model to clone
		 * @return object The model clone
		 */
		protected function cloneModel($objModel) {
			if ($objModel instanceof CoreModel) {
				$objClone = clone $objModel;
				if (!$this->blnHelpers) {
					$objClone->setConfigOption('NoSaveHelpers', true);
					$objClone->clearHelpers();
				} else {
					$objClone->destroyHelper('backup');
				}
				return $objClone;
			} else {
				throw new CoreException(AppLanguage::translate('Invalid model'));
			}
		}
		
		
		/**
		 * Backs up a single record about to be saved over into a 
		 * separate table. Only works when updating existing records, 
		 * not adding new ones.
		 *
		 * @access public
		 * @param boolean $blnBatch Whether to back up the records in batch
		 * @param boolean $blnFatal Whether a backup failure means don't save
		 * @param object $objModel The model object to use with the backup
		 * @param string $strFunction The function being called
		 * @param boolean $blnNewRecord Whether a new record is being inserted (vs. update)
		 * @param boolean $blnForceInsert Whether to force insert a record even though it has an ID
		 * @return array The result in extractable array format
		 */
		public function backupSave($blnBatch, $blnFatal, $objModel, $strFunction, $blnNewRecord, $blnForceInsert) {
			if (!$blnNewRecord) {
				if ($objClone = $this->cloneModel($objModel)) {
					if ($objClone->loadById($objModel->current()->get('__id'), array('AutoFilterOff' => true)) && $objClone->count()) {		
						$blnResult = $this->backup($objClone, $blnBatch, $blnFatal);
					} else {
						$blnResult = false;
					}
				} else {
					$blnResult = false;
				}
			} else {
				$blnResult = true;
			}
				
			return array(
				'blnResult' 	=> $blnResult,
				'blnSkipSave'	=> !$blnResult && $blnFatal,
				'arrBackedUp'	=> $this->arrBackedUpIds
			);
		}
		
		
		/**
		 * Backs up multiple records about to be deleted into
		 * a separate table. This also deletes the records in 
		 * case something else updates the database between
		 * this being called and the actual delete. Rather
		 * than relying on a delete by filter query this
		 * explicitly deletes all backed up records by ID.
		 *
		 * @access public
		 * @param boolean $blnBatch Whether to back up the records in batch
		 * @param boolean $blnFatal Whether a backup failure means don't delete
		 * @param object $objModel The model object to use with the backup
		 * @param string $strFunction The function being called
		 * @param array $arrFilters The array of filters passed to the function
		 * @return array The array of vars to return to the delete function
		 */
		public function backupDelete($blnBatch, $blnFatal, $objModel, $strFunction, $arrFilters) {
			if ($objClone = $this->cloneModel($objModel)) {
				if ($objClone->load($arrFilters)) {
					if ($objClone->count()) {
						$blnResult = $this->backup($objClone, $blnBatch, $blnFatal);
					} else {
						$blnResult = true;
					}
				}
			}
			
			if ($blnResult || !$blnFatal) {
				$blnDeleted = $objClone->deleteById($this->arrBackedUpIds);
			}
			
			return array(
				'blnResult' 	=> $blnResult,
				'blnSkipDelete'	=> ($blnResult && $blnDeleted) || (!$blnResult && $blnFatal),
				'arrBackedUp'	=> $this->arrBackedUpIds
			);
		}
				
		
		/**
		 * Backs up the records into a separate table. This
		 * must get the array of backed up IDs before saving
		 * the clone or else the ID will be the inserted ID
		 * from the backup table.
		 *
		 * @access protected
		 * @param object $objClone A clone of the model with the records to back up loaded in
		 * @param boolean $blnBatch Whether to back up the records in batch
		 * @param boolean $blnFatal Whether a backup failure means don't complete the action
		 * @return boolean True if all backups were successful
		 */
		protected function backup($objClone, $blnBatch, $blnFatal) {
			$this->arrBackedUpIds = array();
			
			$strTableBackup = $objClone->getTable();
			$objClone->setTable($this->arrConfig['BackupTable']);
			if ($blnBatch && method_exists($objClone, 'insertAll')) {
				while (list(, $objRecord) = $objClone->each()) {
					$this->arrBackedUpIds[] = $objRecord->get('__id');
				}
				$objClone->rewind();
				
				if (!$objClone->insertAll(true)) {
					$this->arrBackedUpIds = array();
				}
			} else {
				while ($objRecord = $objClone->current()) {
					$intId = $objRecord->get('__id');
					if ($objClone->save(true)) {
						$this->arrBackedUpIds[] = $intId;
					} else {
						if ($blnFatal) {
							break;
						}
					}				
					$objClone->next();
				}
				$objClone->rewind();
			}
			$objClone->setTable($strTableBackup);
			
			return count($this->arrBackedUpIds) == $objClone->count();
		}
	}