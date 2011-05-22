<?php
	require_once('php/core/CoreModelHelper.class.php');
	
	/**
	 * ModelValidation.class.php
	 * 
	 * This is a model helper class to handle validation.
	 * This registers events that are run by the model
	 * object. Any data returned from the event methods
	 * is available in the function that runs the helper
	 * event.
	 *
	 * In the case of this helper it's a good idea to
	 * append the helper from the model object's init
	 * method even if it's not initialized right away. 
	 * This is so validation isn't scattered all over
	 * the place.
	 *
	 * <code>
	 * if (!empty($arrConfig['Relations'])) {
	 * 		if (AppLoader::includeExtension('helpers/', 'ModelValidation')) {
	 *			$this->appendHelper('validation', 'ModelValidation', array(
	 *				'UserId'		=> array(
	 *					'Id'			=> array(
	 *					'Unique'		=> true,
	 *					'Type'			=> 'integer',
	 *					'Error'			=> 'Invalid ID'
	 *				),
	 *				
	 *				'Username'		=> array(
	 *					'Unique'		=> true,
	 *					'Required'		=> true,
	 *					'Type'			=> 'string',
	 *					'RegEx'			=> '/^[0-9a-z]{3,20}$/i',
	 *					'Error'			=> 'Invalid username. It must be between 3 and 20 characters in length, containing only a-z and 0-9.',
	 *				)
	 *			));
	 *
	 * 			$this->initHelper('validation', array('loadAutoLoad'), array(
	 * 				'Recursion' => isset($arrConfig['ValidationRecursion']) ? $arrConfig['ValidationRecursion'] : 0
	 * 			));
	 *		}
	 * }
	 * </code>
	 *
	 * Copyright 2006-2011, Phork Labs. (http://phorklabs.com)
	 *
	 * Licensed under The MIT License
	 * Redistributions of files must retain the above copyright notice.
	 *
	 * @author Elenor Collings <elenor@phork.org>
	 * @license http://www.opensource.org/licenses/mit-license.php The MIT License
	 * @package phork
	 * @subpackage helpers
	 */
	class ModelValidation extends CoreModelHelper {
		
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
			
			//validate the record during the model's pre-save event
			if (in_array('validateAll', $arrEvents)) {
				$this->arrEvents[] = array(
					'Event'	=> ($strEvent = $this->strModelKey . '.pre-save'),
					'Key'	=> AppEvent::register($strEvent, array($this, 'validate'))
				);
			}
		}
		
		
		/**
		 * Disables a field to prevent it from being
		 * validated. The field name is the key in the
		 * config array.
		 *
		 * @access public
		 * @param string $strField The name of the field to disable
		 * @return boolean True on success
		 */
		public function disableField($strField) {
			if (!empty($this->arrConfig[$strField])) {
				$this->arrConfig[$strField]['Disabled'] = true;
				return true;
			}
		}
		
		
		/**
		 * Enables a field to allow it to be validated.
		 * The field name is the key in the config array.
		 *
		 * @access public
		 * @param string $strField The name of the field to disable
		 * @return boolean True on success
		 */
		public function enableField($strField) {
			if (!empty($this->arrConfig[$strField])) {
				$this->arrConfig[$strField]['Disabled'] = false;
				return true;
			}
		}
		
		
		/**
		 * Validates the current object in the model. This is
		 * generally called from the save function and returns
		 * a flag to not save if invalid.
		 *
		 * @access public
		 * @param object $objModel The model object to validate
		 * @return array The array of vars to return to the save function
		 */
		public function validate($objModel) {
			$objError = AppRegistry::get('Error');
			$objError->startGroup($strErrorGroup = 'err' . rand());
		
			if (isset($objModel)) {
				if ($objModel instanceof CoreModel && ($objRecord = $objModel->current())) {
					foreach ($this->arrConfig as $strValidate=>$arrValidator) {
						if (empty($arrValidator['Disabled'])) {
							if (empty($arrValidator['Property'])) {
								
								//if there's a custom validation method, use it now (either a model method or a callback)
								if (!empty($arrValidator['Function'])) {
									if (!$this->validateFunction($objModel, $arrValidator)) {
										if (!empty($arrValidator['Error'])) {
											trigger_error($arrValidator['Error']);
										}
									}
								} else {
									throw new CoreException(AppLanguage::translate('Invalid validation method: %s', $arrValidator['Function']));
								}
							} else {
								$mxdValue = $objRecord->get($strProperty = $arrValidator['Property']);
								
								//make sure the value isn't empty if it's required
								if (!empty($arrValidator['Required'])) {
									if (is_null($mxdValue) || $mxdValue === '') {
										if (empty($arrValidator['Error'])) {
											trigger_error(AppLanguage::translate('Missing a value for %s', $strProperty));
										} else {
											trigger_error($arrValidator['Error']);
										}
										continue;
									}
								}
								
								//if there's a custom validation method, use it now (either a model method or a callback)
								if (!empty($arrValidator['Function'])) {
									if (!$this->validateFunction($objModel, $arrValidator, $strProperty, $objRecord)) {
										if (!empty($arrValidator['Error'])) {
											trigger_error($arrValidator['Error']);
										}
									}
								}
								
								//don't continue to validate empty values
								if (is_null($mxdValue) || $mxdValue == '') {
									continue;
								}
								
								//validate the property's value
								if (!empty($arrValidator['Type'])) {
									switch ($arrValidator['Type']) {
										case 'string':
											$mxdResult = $this->validateString($strProperty, $mxdValue, $arrValidator);
											break;
											
										case 'integer':
											$mxdResult = $this->validateInteger($strProperty, $mxdValue, $arrValidator);
											break;
											
										case 'float':
											$mxdResult = $this->validateFloat($strProperty, $mxdValue, $arrValidator);
											break;
											
										case 'array':
											$mxdResult = $this->validateArray($strProperty, $mxdValue, $arrValidator);
											break;
										
										case 'object':
											$mxdResult = $this->validateObject($strProperty, $mxdValue, $arrValidator);
											break;
											
										case 'email':
											$mxdResult = $this->validateEmail($strProperty, $mxdValue, $arrValidator);
											break;
											
										case 'datetime':
											$mxdResult = $this->validateDatetime($strProperty, $mxdValue, $arrValidator);
											break;
									}
									
									//make sure the value validation was successful
									if ($mxdResult !== true) {
										trigger_error($mxdResult);
										continue;
									}
								}
								
								//make sure the field is unique if it should be
								if (!empty($arrValidator['Unique'])) {
									 $mxdResult = $this->validateUnique($strProperty, $mxdValue, $objModel);
									 if ($mxdResult !== true) {
										trigger_error($mxdResult);
									 }
								}
							}
						}
					}
				}
			}
			
			if ($objError->endGroup($strErrorGroup)) {
				return array(
					'blnSkipSave' => true
				);
			}
		}
		
		
		/**
		 * Uses an external callback to validate the value.
		 * This can be used for both a property validator and
		 * a generic validator.
		 *
		 * @access protected
		 * @param object $objModel The model containing the record to validate
		 * @param array $arrValidator The array of validation parameters
		 * @param string $strProperty The name of the property to validate
		 * @param object $objRecord The record containing the property to validate
		 * @return mixed True on success, or the error on failure
		 */
		protected function validateFunction($objModel, &$arrValidator, $strProperty = null, $objRecord = null) {
			if (is_array($arrValidator['Function'])) {
				return call_user_func_array($arrValidator['Function'], array($strProperty, $objRecord));
			} else {
				if (method_exists($objModel, $arrValidator['Function'])) {
					 return $objModel->{$arrValidator['Function']}($strProperty);
				} else {
					throw new CoreException(AppLanguage::translate('Invalid validation method: %s', $arrValidator['Function']));
				}
			}
		}
		
		
		/**
		 * Validates a string and, if necessary, its format.
		 *
		 * @access public
		 * @param string $strProperty The name of the property to validate
		 * @param string $strValue The value to validate
		 * @param array $arrValidator The array of validation parameters
		 * @return mixed True on success, or the error on failure
		 */
		public function validateString($strProperty, $strValue, &$arrValidator) {
			
			//make sure the value is a string
			if (!is_string($strValue)) {
				if (empty($arrValidator['Error'])) {
					return AppLanguage::translate('%s must be a string', $strProperty);
				}
				return $arrValidator['Error'];
			}
			
			//if there's a regular expression, validate against it
			if (!empty($arrValidator['RegEx']) && !preg_match($arrValidator['RegEx'], $strValue)) {
				if (empty($arrValidator['Error'])) {
					return AppLanguage::translate('%s must be in the format %s', $strProperty, $strValue);
				}
				return $arrValidator['Error'];
			}
			
			//validate the minimum length if a value is set
			if ($strValue && array_key_exists('MinLength', $arrValidator)) {
				if (strlen($strValue) < $arrValidator['MinLength']) {
					if (empty($arrValidator['Error'])) {
						return AppLanguage::translate('%s must be more than %d characters long', $strProperty, $arrValidator['MinLength']);
					}
					return $arrValidator['Error'];
				}
			}
			
			//validate the maximum length if a value is set
			if ($strValue && array_key_exists('MaxLength', $arrValidator)) {
				if (strlen($strValue) > $arrValidator['MaxLength']) {
					if (empty($arrValidator['Error'])) {
						return AppLanguage::translate('%s must be less than %d characters long', $strProperty, $arrValidator['MaxLength']);
					}
					return $arrValidator['Error'];
				}
			}
			
			return true;
		}
		
		
		/**
		 * Validates an integer.
		 *
		 * @access public
		 * @param string $strProperty The name of the property to validate
		 * @param integer $mxdValue The value to validate
		 * @param array $arrValidator The array of validation parameters
		 * @return mixed True on success, or the error on failure
		 */
		public function validateInteger($strProperty, $mxdValue, &$arrValidator) {
			
			//compare the values as strings, because comparing int and strings doesn't work (ctype_digit() won't work on empty value)
			if ((string) intval($mxdValue) != (string) $mxdValue) {
				if (empty($arrValidator['Error'])) {
					return AppLanguage::translate('%s must be an integer', $strProperty);
				}
				return $arrValidator['Error'];	
			}
			
			//validate the minimum value
			if (array_key_exists('MinValue', $arrValidator)) {
				if ($mxdValue < $arrValidator['MinValue']) {
					if (empty($arrValidator['Error'])) {
						return AppLanguage::translate('%s must be greater than %d', $strProperty, $arrValidator['MinValue']);
					}
					return $arrValidator['Error'];
				}
			}
			
			//validate the maximum value
			if (array_key_exists('MaxValue', $arrValidator)) {
				if ($mxdValue > $arrValidator['MaxValue']) {
					if (empty($arrValidator['Error'])) {
						return AppLanguage::translate('%s must be less than than %d', $strProperty, $arrValidator['MaxValue']);
					}
					return $arrValidator['Error'];
				}
			}
			
			return true;
		}
		
		
		/**
		 * Validates a float. Compares the values as string
		 * because comparing float and strings doesn't work.
		 *
		 * @access public
		 * @param string $strProperty The name of the property to validate
		 * @param integer $mxdValue The value to validate
		 * @param array $arrValidator The array of validation parameters
		 * @return mixed True on success, or the error on failure
		 */
		public function validateFloat($strProperty, $mxdValue, &$arrValidator) {
			
			//compares the values as strings because comparing float and strings doesn't work.
			if (!$this->validateInteger($strProperty, $mxdValue, $arrValidator)) {
				if ((string) floatval($mxdValue) != (string) $mxdValue) {
					if (empty($arrValidator['Error'])) {
						return AppLanguage::translate('%s must be a float', $strProperty);
					}
					return $arrValidator['Error'];	
				}
			}
			
			//validate the minimum value
			if (array_key_exists('MinValue', $arrValidator)) {
				if ($mxdValue < $arrValidator['MinValue']) {
					if (empty($arrValidator['Error'])) {
						return AppLanguage::translate('%s must be greater than %d', $strProperty, $arrValidator['MinValue']);
					}
					return $arrValidator['Error'];
				}
			}
			
			//validate the maximum value
			if (array_key_exists('MaxValue', $arrValidator)) {
				if ($mxdValue > $arrValidator['MaxValue']) {
					if (empty($arrValidator['Error'])) {
						return AppLanguage::translate('%s must be less than than %d', $strProperty, $arrValidator['MaxValue']);
					}
					return $arrValidator['Error'];
				}
			}
			
			return true;
		}
		
		
		/**
		 * Validates an array and optionally the number of
		 * of items in it.
		 *
		 * @access public
		 * @param string $strProperty The name of the property to validate
		 * @param integer $mxdValue The value to validate
		 * @param array $arrValidator The array of validation parameters
		 * @return mixed True on success, or the error on failure
		 */
		public function validateArray($strProperty, $mxdValue, &$arrValidator) {
			
			//make sure that the value is an array if it's not empty
			if (!is_array($mxdValue) && !empty($mxdValue)) {
				if (empty($arrValidator['Error'])) {
					return AppLanguage::translate('%s must be an array', $strProperty);
				}
				return $arrValidator['Error'];
			}
			
			//validate the minimum number of required items
			if (array_key_exists('MinCount', $arrValidator)) {
				if (count($mxdValue) < $arrValidator['MinCount']) {
					if (empty($arrValidator['Error'])) {
						return AppLanguage::translate('%s must contain at least %d items', $strProperty, $arrValidator['MinCount']);
					}
					return $arrValidator['Error'];
				}
			}
			
			//validate the maximum number of required items
			if (array_key_exists('MaxCount', $arrValidator)) {
				if (count($mxdValue) > $arrValidator['MaxCount']) {
					if (empty($arrValidator['Error'])) {
						return AppLanguage::translate('%s must contain at most %d items', $strProperty, $arrValidator['MaxCount']);
					}
					return $arrValidator['Error'];
				}
			}
			
			return true;
		}
		
		
		/**
		 * Validates an object.
		 *
		 * @access public
		 * @param string $strProperty The name of the property to validate
		 * @param integer $mxdValue The value to validate
		 * @param array $arrValidator The array of validation parameters
		 * @return mixed True on success, or the error on failure
		 */
		public function validateObject($strProperty, $mxdValue, &$arrValidator) {
			if (!is_object($mxdValue) && !empty($mxdValue)) {
				if (empty($arrValidator['Error'])) {
					return AppLanguage::translate('%s must be an object', $strProperty);
				}
				return $arrValidator['Error'];	
			}
			
			//validate the type of object
			if (array_key_exists('InstanceOf', $arrValidator)) {
				if (!($mxdValue instanceof $arrValidator['InstanceOf'])) {
					if (empty($arrValidator['Error'])) {
						return AppLanguage::translate('%s must be an instance of %s', $strProperty, $arrValidator['InstanceOf']);
					}
					return $arrValidator['Error'];
				}
			}
			
			return true;
		}
		
		
		/**
		 * Validates an email address and checks its MX record.
		 * The preferred validation uses filter_var() but that
		 * requires PHP >= 5.2.0. The fallback is a regular
		 * expression which comes with this copyright:
		 * 
		 * Copyright Michael Rushton 2009-10
		 * http://squiloople.com/
		 *
		 * @access public
		 * @param string $strProperty The name of the property to validate
		 * @param string $strValue The value to validate
		 * @param array $arrValidator The array of validation parameters
		 * @return mixed True on success, or the error on failure
		 */
		public function validateEmail($strProperty, $strValue, &$arrValidator) {
			if (function_exists('filter_var')) {
				$blnValid = (filter_var($strValue, FILTER_VALIDATE_EMAIL) !== false);
			} else {
				$blnValid = preg_match('/^(?!(?:(?:\\x22?\\x5C[\\x00-\\x7E]\\x22?)|(?:\\x22?[^\\x5C\\x22]\\x22?)){255,})(?!(?:(?:\\x22?\\x5C[\\x00-\\x7E]\\x22?)|(?:\\x22?[^\\x5C\\x22]\\x22?)){65,}@)(?:(?:[\\x21\\x23-\\x27\\x2A\\x2B\\x2D\\x2F-\\x39\\x3D\\x3F\\x5E-\\x7E]+)|(?:\\x22(?:[\\x01-\\x08\\x0B\\x0C\\x0E-\\x1F\\x21\\x23-\\x5B\\x5D-\\x7F]|(?:\\x5C[\\x00-\\x7F]))*\\x22))(?:\\.(?:(?:[\\x21\\x23-\\x27\\x2A\\x2B\\x2D\\x2F-\\x39\\x3D\\x3F\\x5E-\\x7E]+)|(?:\\x22(?:[\\x01-\\x08\\x0B\\x0C\\x0E-\\x1F\\x21\\x23-\\x5B\\x5D-\\x7F]|(?:\\x5C[\\x00-\\x7F]))*\\x22)))*@(?:(?:(?!.*[^.]{64,})(?:(?:(?:xn--)?[a-z0-9]+(?:-[a-z0-9]+)*\\.){1,126}){1,}(?:(?:[a-z][a-z0-9]*)|(?:(?:xn--)[a-z0-9]+))(?:-[a-z0-9]+)*)|(?:\\[(?:(?:IPv6:(?:(?:[a-f0-9]{1,4}(?::[a-f0-9]{1,4}){7})|(?:(?!(?:.*[a-f0-9][:\\]]){7,})(?:[a-f0-9]{1,4}(?::[a-f0-9]{1,4}){0,5})?::(?:[a-f0-9]{1,4}(?::[a-f0-9]{1,4}){0,5})?)))|(?:(?:IPv6:(?:(?:[a-f0-9]{1,4}(?::[a-f0-9]{1,4}){5}:)|(?:(?!(?:.*[a-f0-9]:){5,})(?:[a-f0-9]{1,4}(?::[a-f0-9]{1,4}){0,3})?::(?:[a-f0-9]{1,4}(?::[a-f0-9]{1,4}){0,3}:)?)))?(?:(?:25[0-5])|(?:2[0-4][0-9])|(?:1[0-9]{2})|(?:[1-9]?[0-9]))(?:\\.(?:(?:25[0-5])|(?:2[0-4][0-9])|(?:1[0-9]{2})|(?:[1-9]?[0-9]))){3}))\\]))$/iD', $strValue);
			}
			
			if (!$blnValid) {
				if (empty($arrValidator['Error'])) {
					return AppLanguage::translate('%s must be a valid email address', $strProperty, $strValue);
				}
				return $arrValidator['Error'];
			}
			
			if (!empty($arrValidator['CheckMx']) && function_exists('checkdnsrr')) {
				$arrEmailSegments = explode('@', $strValue);
				if (!checkdnsrr($strEmailDomain = array_pop($arrEmailSegments), 'MX')) {
					return AppLanguage::translate('%s is not a valid email domain', $strEmailDomain);
				}
			}
			
			return true;
		}
		
		
		/**
		 * Validates that a datetime value is in the format
		 * Y-m-d H:i:s.
		 *
		 * @access public
		 * @param string $strProperty The name of the property to validate
		 * @param string $strValue The value to validate
		 * @param array $arrValidator The array of validation parameters
		 * @return mixed True on success, or the error on failure
		 */
		public function validateDatetime($strProperty, $strValue, &$arrValidator) {
			if (!preg_match('/^[0-9]{4}-[0-9]{2}-[0-9]{2} [0-9]{2}:[0-9]{2}:[0-9]{2}$/i', $strValue)) {
				if (empty($arrValidator['Error'])) {
					return AppLanguage::translate('%s must be a valid datetime (YYYY-MM-DD hh:mm:ss)', $strProperty, $strValue);
				}
				return $arrValidator['Error'];
			}
			
			return true;
		}
		
		
		/**
		 * Makes sure that a property's value is unique.
		 *
		 * @access public
		 * @param string $strProperty The name of the property to validate
		 * @param string $strValue The value to validate
		 * @param object $objModel The model with the record to validate
		 * @return mixed True on success, or the error on failure
		 */
		public function validateUnique($strProperty, $mxdValue, $objModel) {
			$arrFilters = array(
				'Conditions' => array(
					array(
						'Column'	=> $objModel->getTable() . '.' . $strProperty,
						'Value' 	=> $mxdValue,
						'Operator'	=> '='
					)
				)
			);
			
			//load the data and make sure the property is unique
			$objModelClone = clone $objModel;
			if ($objModelClone->load($arrFilters) && $objModelClone->count()) {
				if ($objModelClone->first()->get('__id') && $objModelClone->first()->get('__id') != $objModel->current()->get('__id')) {
					if (empty($arrValidator['Error'])) {
						return AppLanguage::translate('%s (%s) must be unique', $strProperty, $mxdValue);
					}
					return $arrValidator['Error'];
				}
			}
			unset($objModelClone);
			
			return true;
		}
	}