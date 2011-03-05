<?php
	/**
	 * Pagination.class.php
	 *
	 * A utility class for getting the various numbers
	 * used to set up pagination.
	 *
	 * Copyright 2006-2011, Phork Labs. (http://phorklabs.com)
	 *
	 * Licensed under The MIT License
	 * Redistributions of files must retain the above copyright notice.
	 *
	 * @author Elenor Collings <elenor@phork.org>
	 * @license http://www.opensource.org/licenses/mit-license.php The MIT License
	 * @package phork
	 * @subpackage utilities
	 */
	class Pagination {
		
		protected $intCurrentPage;
		protected $intStartPage;
		protected $intEndPage;
		protected $intTotalPages;
		
		protected $intStartItem;
		protected $intEndItem;
		protected $intTotalItems;
		protected $intItemsPerPage;
		
		protected $intNumLinks;
		
	
		/**
		 * Sets up the pagination numbers.
		 *
		 * @access public
		 * @param integer $intCurrentPage The current page to base the pagination from
		 * @param integer $intTotalItems The total number of items to paginate through
		 * @param integer $intItemsPerPage The total number of items to show on each page
		 * @param integer $intNumLinks The total number of links to show in the link list (ie. page 1 2 3 4 5)
		 */
		public function __construct($intCurrentPage, $intTotalItems, $intItemsPerPage, $intNumLinks = 5) {
			
			$this->intCurrentPage = $intCurrentPage;
			$this->intTotalItems = $intTotalItems;
			$this->intItemsPerPage = $intItemsPerPage;
			$this->intNumLinks = $intNumLinks;
			
			//figure out how many total pages there are
			$this->intTotalPages = ceil($intTotalItems / $intItemsPerPage);
			
			//figure out how many numbers in page loop before and after current page
			$intNumPrevious = floor($this->intNumLinks / 2);
			$intNumNext = $this->intNumLinks - $intNumPrevious;
			
			//figure out where to start and end the page loop
			$intStartPage = $this->intCurrentPage - $intNumPrevious;
			$intEndPage = $this->intCurrentPage + $intNumNext - 1;
			
			
			//if the page loop starts before page 1 add the extra pages on afterwards and start on the first page
			if ($intStartPage < 1) {
				$intEndPage += (1 - $intStartPage);
				$intStartPage = 1;
			}
			
			//if the page loop ends after the number of pages add the extra pages on before and end on the last page
			if ($intEndPage > $this->intTotalPages) {                                               
				$intStartPage -= ($intEndPage - $this->intTotalPages);
				$intEndPage = $this->intTotalPages;
				
				//make sure it still starts on page 1
				if ($intStartPage < 1) {
					$intStartPage = 1;
				}
			}
			
			$this->intStartPage = $intStartPage;
			$this->intEndPage = $intEndPage;
			
			//calculate the start and end items (ie. results 10 - 20)
			$this->intStartItem = ($this->intCurrentPage - 1) * $this->intItemsPerPage + 1;
			$this->intEndItem = min($this->intStartItem + $this->intItemsPerPage - 1, $this->intTotalItems);
		}
		
		
		/*****************************************/
		/**     MAGIC METHODS                   **/
		/*****************************************/
		
		
		/**
		 * Method called when an unknown method is called.
		 * Currently used for the get[Element] method.
		 *
		 * @access public
		 * @param string $strMethodName The method called
		 * @param array $arrParameters The parameters passed to the method
		 * @return string The element's value
		 */
		public function __call($strMethodName, $arrParameters) {
			
			//check for get[Element] method
			if (substr($strMethodName, 0, 3) == 'get') {
				$strElement = substr($strMethodName, 3);
				if (property_exists($this, ($strProperty = "int{$strElement}"))) {
					return $this->$strProperty;
				}
			}
			
			return parent::__call($strMethodName, $arrParameters);
		}
	}