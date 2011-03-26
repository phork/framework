<?php
	require_once('php/database/interfaces/SqlQuery.interface.php');
	
	/**
	 * MySqlQuery.class.php
	 *
	 * Used to generate queries for MySQL databases.
	 *
	 * <code>
	 * $objQuery = new MySqlQuery();
	 * $objQuery->initSelectQuery();
	 * $objQuery->addTable('file');
	 * $objQuery->addTableJoin('folder', null, 'folderid', 'LEFT JOIN');
	 * $objQuery->addColumn('file.*');
	 * $objQuery->addColumn('folder.fullpath');
	 * $objQuery->addColumn('folder.hashlevel');
	 * $objQuery->addColumn($objQuery->buildFunction('COUNT', '*'), 'tally');
	 * $objQuery->addWhere('image', 1);
	 * $objQuery->addWhere('width', 400, '>');
	 * $objQuery->addOrderBy('created', 'DESC');
	 * $objQuery->addGroupBy('type');
	 * $objQuery->addHaving('tally', 3, '>');
	 * $objQuery->addLimit(3);
	 * $strQuery = $objQuery->buildQuery();
	 *
	 * $strQuery = $objQuery->select()->from($strTable)->where('id', $intId)->buildQuery();
	 * </code>
	 *
	 * <code>
	 * $objQuery = new MySqlQuery();
	 * $objQuery->initInsertQuery();
	 * $objQuery->addTable('demo');
	 * $objQuery->addColumn('foo', 'fooval');
	 * $objQuery->addColumn('bar', 'barval');
	 * $strQuery = $objQuery->buildQuery();
	 * </code>
	 *
	 * <code>
	 * $objQuery = new MySqlQuery();
	 * $objQuery->initInsertQuery();
	 * $objQuery->addColumn('foo', null);
	 *
	 * $objClone = clone $objQuery();
	 * $objClone->initInsertQuery();
	 * $objClone->addColumn('foo', 'fooval1');
	 * $arrQuery[] = $objClone;
	 *
	 * $objClone = clone $objQuery();
	 * $objClone->initInsertQuery();
	 * $objClone->addColumn('foo', 'fooval2');
	 * $arrQuery[] = $objClone;
	 *
	 * $strQuery = $objQuery->buildInsertMultiQuery($arrQuery);
	 * </code>
	 *
	 * <code>
	 * $objQuery = new MySqlQuery();
	 * $objQuery->initInsertQuery();
	 * $objQuery->addTable('demo');
	 * $objQuery->addColumn('foo', 'fooval');
	 * $objQuery->addColumn('bar', 'barval');
	 * 
	 * $objUpdate = clone $objQuery;
	 * $objUpdate->initUpdateQuery();
	 * $objUpdate->addColumn('baz', 'bazval'); 
	 *
	 * $strQuery = $objQuery->buildInsertOrUpdateQuery($objUpdate);
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
	 * @subpackage database
	 */
	class MySqlQuery implements SqlQuery {
	
		const SELECT_QUERY = 'Select';
		const INSERT_QUERY = 'Insert';
		const UPDATE_QUERY = 'Update';
		const DELETE_QUERY = 'Delete';
	
		protected $objDb;
		protected $strQueryType;
		protected $arrTable;
		protected $arrTableJoin;
		protected $arrColumn;
		protected $blnDistinct;
		protected $arrWhere;
		protected $blnWhereOr;
		protected $arrGroupBy;
		protected $arrHaving;
		protected $blnHavingOr;
		protected $arrOrder;
		protected $arrLimit;
		
		protected $blnPrepareCount;
		protected $blnIgnore;
		
		
		/**
		 * The query constructor used to set up the
		 * database object.
		 *
		 * @access public
		 * @param object $objDb The database object
		 */
		public function __construct(Sql $objDb) {
			$this->objDb = $objDb;
		}
		
		
		/** 
		 * Cleans the filter data before adding it to the
		 * query.
		 * 
		 * @access protected
		 * @param mixed $mxdElementValue The data to clean
		 * @return mixed The cleaned data
		 */
		protected function cleanFilterData($mxdElementValue) {
			if (is_array($mxdElementValue)) {
				foreach ($mxdElementValue as &$mxdValue) {
					$mxdValue = $this->objDb->escapeString($mxdValue);
				}
			} else {
				$mxdElementValue = $this->objDb->escapeString($mxdElementValue);
			}
			
			return $mxdElementValue;
		}
		
		
		/**
		 * This sets the flag to use OR instead of AND for
		 * all the WHERE filters.
		 *
		 * @access public
		 */
		public function useWhereOr() {
			$this->blnWhereOr = true;
		}
		
		
		/**
		 * This sets the flag to use OR instead of AND for
		 * all the HAVING filters.
		 *
		 * @access public
		 */
		public function useHavingOr() {
			$this->blnHavingOr = true;
		}
		
		
		/*****************************************/
		/**     INIT METHODS                    **/
		/*****************************************/
		
		
		/**
		 * Initializes the select query.
		 *
		 * @access public
		 * @param boolean $blnPrepareCount Whether to prepare the query to be used for a count (SQL_CALC_FOUND_ROWS)
		 */
		public function initSelectQuery($blnPrepareCount = false) {
			$this->blnPrepareCount = $blnPrepareCount;
			$this->strQueryType = self::SELECT_QUERY;
			$this->initQuery();
		}
		
		
		/**
		 * Initializes the insert query.
		 *
		 * @access public
		 * @param boolean $blnIgnore Whether to ignore any errors
		 */
		public function initInsertQuery($blnIgnore = false) {
			$this->blnIgnore = $blnIgnore;
			$this->strQueryType = self::INSERT_QUERY;
			$this->initQuery();
		}
		
		
		/**
		 * Initializes the update query.
		 *
		 * @access public
		 * @param boolean $blnIgnore Whether to ignore any errors
		 */
		public function initUpdateQuery($blnIgnore = false) {
			$this->blnIgnore = $blnIgnore;
			$this->strQueryType = self::UPDATE_QUERY;
			$this->initQuery();
		}
		
		
		/**
		 * Initializes the delete query.
		 *
		 * @access public
		 */
		public function initDeleteQuery() {
			$this->strQueryType = self::DELETE_QUERY;
			$this->initQuery();
		}
		
		
		/**
		 * Initializes the query parts.
		 *
		 * @access protected
		 */
		protected function initQuery() {
			$this->arrTable = array();
			$this->arrTableJoin = array();
			$this->arrColumn = array();
			$this->blnDistinct = false;
			$this->arrWhere = array();
			$this->blnWhereOr = false;
			$this->arrGroupBy = array();
			$this->arrHaving = array();
			$this->blnHavingOr = false;
			$this->arrOrder = array();
			$this->arrLimit = array();
		}
		
		
		/*****************************************/
		/**     ADD METHODS                     **/
		/*****************************************/
		
		
		/**
		 * Adds the table for the query.
		 *
		 * @access public
		 * @param string $strTable The name of the table
		 * @param string $strAlias The table alias
		 */
		public function addTable($strTable, $strAlias = null) {
			$this->arrTable = array(
				'Table'		=> $strTable,
				'Alias'		=> $strAlias
			);
		}
		
		
		/**
		 * Adds the joined table(s) for the query.
		 *
		 * @access public
		 * @param string $strTable The name of the table
		 * @param string $strAlias The table alias
		 * @param array $mxdJoinUsing The columns to join on (array for join ON, string for join USING)
		 * @param string $strJoinType The join type
		 */
		public function addTableJoin($strTable, $strAlias = null, $mxdJoinUsing = null, $strJoinType = null) {
			$this->arrTableJoin[] = array(
				'Table'		=> $strTable,
				'Alias'		=> $strAlias,
				'JoinUsing'	=> $mxdJoinUsing,
				'JoinType'	=> $strJoinType
			);
		}
		
		
		/**
		 * Calls the actual add column method based
		 * on the query type.
		 *
		 * @access public
		 */
		public function addColumn() {
			if ($this->strQueryType == self::SELECT_QUERY) {
				$strColumnMethod = 'addSelectColumn';
			} else {
				$strColumnMethod = 'addAlterColumn';
			}
			
			$arrFunctionArgs = func_get_args();
			call_user_func_array(array($this, $strColumnMethod), $arrFunctionArgs);
		}
		
		
		/**
		 * Adds a column for the select query.
		 *
		 * @access protected
		 * @param string $strColumn The name of the column
		 * @param string $strAlias The column alias
		 */
		protected function addSelectColumn($strColumn, $strAlias = null) {
			$this->arrColumn[] = array(
				'Column'	=> $strColumn,
				'Alias'		=> $strAlias
			);
		}
		
		
		/**
		 * Adds a column for the insert/update query.
		 *
		 * @access protected
		 * @param string $strColumn The name of the column
		 * @param string $strValue The value to set the column to, or to 
		 * @param boolean $blnNoFormat Whether the quotes should be left off the value (ie. set my_column = my_column + 1), also doesn't escape strings
		 */
		protected function addAlterColumn($strColumn, $strValue = null, $blnNoFormat = false) {
			$this->arrColumn[] = array(
				'Column'	=> $strColumn,
				'Value'		=> $strValue,
				'NoFormat'	=> $blnNoFormat
			);
		}
		
		
		/**
		 * Flags the query to select distinct rows.
		 *
		 * @access public
		 */
		public function addDistinct() {
			$this->blnDistinct = true;
		}
		
		
		/**
		 * Adds a where clause for the query.
		 *
		 * @access public
		 * @param string $strColumn The name of the column
		 * @param mixed $mxdValue The value to query by 
		 * @param string $strOperator The operator to use for the filter
		 * @param boolean $blnNoQuote If this is true then the value in the query won't be in quotation marks
		 */
		public function addWhere($strColumn, $mxdValue, $strOperator = '=', $blnNoQuote = false) {
			$this->arrWhere[] = array(
				'Column'	=> $strColumn,
				'Value'		=> $mxdValue,
				'Operator'	=> $strOperator,
				'NoQuote'	=> $blnNoQuote
			);
		}
		
		
		/**
		 * Adds a pre-built where clause for the query.
		 *
		 * @access public
		 * @param string $strWhere The raw where query
		 */
		public function addWhereRaw($strWhere) {
			$this->arrWhere[] = array(
				'Raw' => $strWhere
			);
		}
		
		
		/**
		 * Adds a having clause for the query.
		 *
		 * @access public
		 * @param string $strColumn The name of the column
		 * @param mixed $mxdValue The value to query by 
		 * @param string $strOperator The operator to use for the filter
		 * @param boolean $blnNoQuote If this is true then the value in the query won't be in quotation marks
		 */
		public function addHaving($strColumn, $mxdValue, $strOperator = '=', $blnNoQuote = false) {
			$this->arrHaving[] = array(
				'Column'	=> $strColumn,
				'Value'		=> $mxdValue,
				'Operator'	=> $strOperator,
				'NoQuote'	=> $blnNoQuote
			);
		}
			
		
		/**
		 * Adds a pre-built having clause for the query.
		 *
		 * @access public
		 * @param string $strHaving The raw having query
		 */
		public function addHavingRaw($strHaving) {
			$this->arrHaving[] = array(
				'Raw' => $strHaving
			);
		}
		
		
		/**
		 * Adds a group by clause for the query.
		 *
		 * @access public
		 * @param string $strColumn The name of the column
		 */
		public function addGroupBy($strColumn) {
			$this->arrGroupBy[] = array(
				'Column' => $strColumn
			);
		}
		
		
		/**
		 * Adds an order by clause for the query.
		 *
		 * @access public
		 * @param string $strColumn The name of the column
		 * @param string $strSort The sort order (ASC or DESC)
		 */
		public function addOrderBy($strColumn, $strSort = null) {
			if ($strSort && !stristr($strSort, 'ASC') && !stristr($strSort, 'DESC')) {
				throw new CoreException(AppLanguage::translate('Invalid sort order'));
			}
			
			$this->arrOrder[] = array(
				'Column'	=> $strColumn,
				'Sort'		=> $strSort
			);
		}
		
		
		/**
		 * Flags the query to order randomly.
		 *
		 * @access public
		 * @param integer $intSeed The randomizer seed
		 */
		public function addOrderRandom($intSeed = null) {
			$this->arrOrder[] = array(
				'Random'	=> true,
				'Seed'		=> $intSeed
			);
		}
		
		
		/**
		 * Adds the limit and offset parameters for
		 * the query.
		 *
		 * @access public
		 * @param integer $intLimit The number of records to return
		 * @param integer $intOffset The record offset to return from
		 */
		public function addLimit($intLimit, $intOffset = null) {
			$this->arrLimit = array(
				'Limit'		=> $intLimit,
				'Offset'	=> $intOffset
			);
		}
		
		
		/*****************************************/
		/**     BUILD QUERY METHODS             **/
		/*****************************************/
		
		
		/**
		 * Calls the right function to build the query 
		 * based on the properties set.
		 *
		 * @access public
		 * @return string The query
		 */
		public function buildQuery() {
			$strBuildMethod = 'build' . $this->strQueryType . 'Query';
			return $this->$strBuildMethod();
		}
		
		
		/**
		 * Builds the query to retrieve the total
		 * number of rows. If $blnPrepareCount is
		 * true then it will use the FOUND_ROWS()
		 * function.
		 *
		 * @access public
		 * @param string $strColumn The name of the column to return the count in
		 * @return string The count query
		 */
		public function buildCountQuery($strColumn = 'count') {
			if ($this->blnPrepareCount == true) {
				$strQuery = sprintf('SELECT FOUND_ROWS() AS %s', 
				                     $this->objDb->escapeString($strColumn)
				);
			} else {
				$strQuery = sprintf('SELECT COUNT(%s %s) AS %s FROM %s %s %s %s %s',
				                     $this->blnDistinct == true ? 'DISTINCT' : '',
				                     $this->buildSelectColumnQuery(),
				                     $this->objDb->escapeString($strColumn),
				                     $this->buildTableQuery(),
				                     $this->buildTableJoinQuery(),
				                     $this->buildWhereQuery(),
				                     $this->buildGroupByQuery(),
				                     $this->buildHavingQuery()
				);
			}
			
			return $strQuery;
		}
		
		
		/**
		 * Builds the select query.
		 *
		 * @access public
		 * @return string The query
		 */
		protected function buildSelectQuery() {
			return sprintf('SELECT %s %s %s FROM %s %s %s %s %s %s %s',
							$this->blnPrepareCount == true ? 'SQL_CALC_FOUND_ROWS' : '',
			                $this->blnDistinct == true ? 'DISTINCT' : '',
			                $this->buildSelectColumnQuery(),
			                $this->buildTableQuery(),
			                $this->buildTableJoinQuery(),
			                $this->buildWhereQuery(),
			                $this->buildGroupByQuery(),
			                $this->buildHavingQuery(),
			                $this->buildOrderQuery(),
			                $this->buildLimitQuery()
			);
		}
		
		
		/**
		 * Builds the insert query.
		 *
		 * @access public
		 * @return string The query
		 */
		protected function buildInsertQuery() {
			return sprintf('INSERT %s INTO %s (%s) VALUES (%s)',
			                $this->blnIgnore ? 'IGNORE' : null,
			                $this->buildTableQuery(),
			                $this->buildInsertColumnQuery(),
			                $this->buildInsertValuesQuery()
			);
		}
		
		
		/**
		 * Builds the query to insert multiple records.
		 *
		 * @access public
		 * @param array $arrQuery The array of insert query objects
		 * @return string The query
		 */
		public function buildInsertMultiQuery($arrQuery) {
			$strQuery = sprintf('INSERT %s INTO %s (%s) VALUES',
			                     $this->blnIgnore ? 'IGNORE' : null,
			                     $this->buildTableQuery(),
			                     $this->buildInsertColumnQuery()
			);
			
			//loop through each query object and add the insert values
			foreach ($arrQuery as $intId=>$objQuery) {
				$strQuery .= sprintf(' (%s)', $objQuery->buildInsertValuesQuery());
				if (!empty($arrQuery[$intId + 1])) {
					$strQuery .= ',';
				}
			}
			
			return $strQuery;
		}
		
		
		/**
		 * Builds the query to insert from a select
		 * query.
		 *
		 * @access public
		 * @param object $objQuery The select query object
		 * @return string The query
		 */
		public function buildInsertFromQuery($objQuery) {
			if (!$objQuery->isSelect()) {
				throw new CoreException(AppLanguage::translate('Invalid query object - It must be a select query'));
			}
			
			return sprintf('INSERT INTO %s %s %s',
			                $this->buildTableQuery(),
			                (($strInsertColumn = $this->buildInsertColumnQuery()) ? "($strInsertColumn)" : ''),
			                $objQuery->buildQuery()
			);
		}
		
		
		/**
		 * Builds an insert or update on duplicate
		 * key query.
		 *
		 * @access public
		 * @param object $objQuery The update query object if the update columns differ from insert
		 * @return string The query
		 */
		public function buildInsertOrUpdateQuery($objQuery = null) {
			return sprintf('%s ON DUPLICATE KEY UPDATE %s',
				$this->buildInsertQuery(),
				$objQuery ? $objQuery->buildUpdateColumnQuery() : $this->buildUpdateColumnQuery()
			);
		}
		
		
		/**
		 * Builds the update query.
		 *
		 * @access public
		 * @return string The query
		 */
		protected function buildUpdateQuery() {
			return sprintf('UPDATE %s %s SET %s %s %s %s',
			                $this->blnIgnore ? 'IGNORE' : null,
			                $this->buildTableQuery(),
			                $this->buildUpdateColumnQuery(),
			                $this->buildWhereQuery(),
			                $this->buildOrderQuery(),
			                $this->buildLimitQuery()
			);
		}
				
		
		/**
		 * Builds the delete query.
		 *
		 * @access public
		 * @return string The query
		 */
		protected function buildDeleteQuery() {
			return sprintf('DELETE FROM %s %s %s',
			                $this->buildTableQuery(),
			                $this->buildWhereQuery(),
			                $this->buildLimitQuery()
			);
		}
		
		
		/*****************************************/
		/**     BUILD PARTIAL QUERY METHODS     **/
		/*****************************************/
		
		
		/**
		 * Builds the function part of the query. This
		 * can also be used an adapter for other database
		 * types. A switch statement can be used on the 
		 * function name in order to convert the function
		 * call to the appropriate database-specific syntax.
		 *
		 * The function arguments should be passed to
		 * this method after the function name.
		 *
		 * @access public
		 * @param string $strFunction The function to apply to the column (eg. COUNT)
		 * @return string The query part
		 */
		public function buildFunction($strFunction) {
			$arrFunctionArgs = func_get_args();
			array_shift($arrFunctionArgs);
		
			$strQuery  = $strFunction . '(';
			$strQuery .= implode(', ', $arrFunctionArgs);
			$strQuery .= ')';
			
			return trim($strQuery);
		}
		
		
		/**
		 * Returns the table part of the query.
		 *
		 * @access protected
		 * @return string The query part
		 */
		protected function buildTableQuery() {
			$strQuery = '';
			
			if (!empty($this->arrTable)) {
				$strQuery = '`' . $this->arrTable['Table'] . '`';
				
				if ($this->arrTable['Alias']) {
					$strQuery .= ' AS ' . $this->arrTable['Alias'];
				}
			}
			
			return trim($strQuery);
		}
		
		
		/**
		 * Returns the table join part of the query.
		 *
		 * @access protected
		 * @return string The query part
		 */
		protected function buildTableJoinQuery() {	
			$strQuery = '';
			
			if (!empty($this->arrTableJoin)) {	
				foreach ($this->arrTableJoin as $intId=>$arrTable) {
					if ($arrTable['JoinType']) {
						$strQuery .= ' ' . $arrTable['JoinType'] . ' ';
					} else {
						$strQuery .= ' JOIN ';
					}
					
					$strQuery .= '`' . $arrTable['Table'] . '`';
					
					if ($arrTable['Alias']) {
						$strQuery .= ' AS ' . $arrTable['Alias'];
					}
					
					if (is_array($arrTable['JoinUsing'])) {
						$strQuery .= ' ON (';
						foreach ($arrTable['JoinUsing'] as $arrValue) {
							$strQuery .= $arrValue[0] . '=' . $arrValue[1] . ' AND ';
						}
						$strQuery = substr($strQuery, 0, -5) . ') ';
					} else if (!empty($arrTable['JoinUsing'])) {
						$strQuery .= ' USING (' . $arrTable['JoinUsing'] . ') ';
					}
				}
			}
			
			return trim($strQuery);
		}
		
		
		/**
		 * Returns the column part of the select query.
		 *
		 * @access protected
		 * @return string The query part
		 */
		protected function buildSelectColumnQuery() {
			$strQuery = '';
			
			if (count($this->arrColumn)) {
				foreach ($this->arrColumn as $intId=>$arrColumn) {
					$strQuery .= $arrColumn['Column'];
					
					if ($arrColumn['Alias']) {
						$strQuery .= ' AS ' . $arrColumn['Alias'];
					}
					
					if (!empty($this->arrColumn[$intId + 1])) {
						$strQuery .= ', ';	
					}
				}
			} else {
				$strQuery = '*';
			}
			
			return trim($strQuery);
		}
		
		
		/**
		 * Returns the column part of the update query.
		 *
		 * @access protected
		 * @return string The query part
		 */
		protected function buildUpdateColumnQuery() {
			$strQuery = '';
			
			if (count($this->arrColumn)) {
				foreach ($this->arrColumn as $intId=>$arrColumn) {
					$strQuery .= $arrColumn['Column'] . ' = ';
					if ($arrColumn['NoFormat']) {
						$strQuery .= $arrColumn['Value'];
					} else {
						$strQuery .= "'" . $this->objDb->escapeString($arrColumn['Value']) . "'";
					}
					
					if (!empty($this->arrColumn[$intId + 1])) {
						$strQuery .= ', ';	
					}
				}
			}
			
			return trim($strQuery);
		}
		
		
		/**
		 * Returns the column part of the insert query.
		 *
		 * @access protected
		 * @return string The query part
		 */
		protected function buildInsertColumnQuery() {
			$strQuery = '';
			
			if (count($this->arrColumn)) {
				foreach ($this->arrColumn as $intId=>$arrColumn) {
					$strQuery .= $arrColumn['Column'];
					
					if (!empty($this->arrColumn[$intId + 1])) {
						$strQuery .= ', ';	
					}
				}
			}
			
			return trim($strQuery);
		}
		
		
		/**
		 * Returns the values part of the insert query.
		 *
		 * @access protected
		 * @return string The query part
		 */
		protected function buildInsertValuesQuery() {
			$strQuery = '';
			
			if (count($this->arrColumn)) {
				foreach ($this->arrColumn as $intId=>$arrColumn) {
					if ($arrColumn['NoFormat']) {
						$strQuery .= $arrColumn['Value'];
					} else {
						$strQuery .= "'" . $this->objDb->escapeString($arrColumn['Value']) . "'";
					}
					
					if (!empty($this->arrColumn[$intId + 1])) {
						$strQuery .= ', ';	
					}
				}
			}
			
			return trim($strQuery);
		}
		
		
		/**
		 * Returns the where part of the query.
		 *
		 * @access protected
		 * @return string The query part
		 */
		protected function buildWhereQuery() {
			$strQuery = '';
			
			if (count($this->arrWhere)) {
				$strQuery .= ' WHERE ' . $this->buildFilterQuery($this->arrWhere, $this->blnWhereOr);
			}
			
			return trim($strQuery);
		}
		
		
		/**
		 * Returns the group by part of the query.
		 *
		 * @access protected
		 * @return string The query part
		 */
		protected function buildGroupByQuery() {
			$strQuery = '';
			
			if (!empty($this->arrGroupBy)) {
				$strQuery = 'GROUP BY ';
				
				foreach ($this->arrGroupBy as $intId=>$arrGroupBy) {
					$strQuery .= $arrGroupBy['Column'];
					
					if (!empty($this->arrGroupBy[$intId + 1])) {
						$strQuery .= ', ';	
					}
				}
			}
			
			return trim($strQuery);
		}
		
		
		/**
		 * Returns the having part of the query.
		 * 
		 * @access protected
		 * @return string The query part
		 */
		protected function buildHavingQuery() {
			$strQuery = '';
			
			if (count($this->arrHaving)) {
				$strQuery .= ' HAVING ' . $this->buildFilterQuery($this->arrHaving, $this->blnHavingOr);
			}
			
			return trim($strQuery);
		}
		
		
		/**
		 * Returns the order by part of the query.
		 *
		 * @access protected
		 * @return string The query
		 */
		protected function buildOrderQuery() {
			$strQuery = '';
			
			if (count($this->arrOrder)) {
				$strQuery = 'ORDER BY ';
				foreach ($this->arrOrder as $intId=>$arrOrder) {
					if (isset($arrOrder['Column'])) {
						$strQuery .= $arrOrder['Column'];
						if ($arrOrder['Sort']) {
							$strQuery .= ' ' . $arrOrder['Sort'];
						}
					} else if (!empty($arrOrder['Random'])) {
						if (array_key_exists('Seed', $arrOrder)) {
							$strQuery .= 'RAND()';
						} else {
							$strQuery .= 'RAND(' . $arrOrder[$intSeed] . ')';	
						}
					}
					
					if (!empty($this->arrOrder[$intId + 1])) {
						$strQuery .= ', ';
					}
				}
			}
			
			return trim($strQuery);
		}
		
		
		/**
		 * Returns the limit part of the query.
		 *
		 * @access protected
		 * @return string The query part
		 */
		protected function buildLimitQuery() {
			$strQuery = '';
			
			if (!empty($this->arrLimit)) {
				$strQuery = 'LIMIT ';
				
				if ($this->arrLimit['Offset']) {
					$strQuery .= $this->arrLimit['Offset'] . ', ';
				}
				
				$strQuery .= $this->arrLimit['Limit'];
			}
			
			return trim($strQuery);
		}
		
		
		/**
		 * Builds the where/having part of the query.
		 *
		 * @access protected
		 * @param array $arrFilters The where/having array elements
		 * @param boolean $blnOr Whether to use OR instead of AND
		 * @return string The query part
		 */
		protected function buildFilterQuery($arrFilters, $blnOr = false) {
			$arrQuery = array();
			
			foreach ($arrFilters as $intId=>$arrValue) {
				if (array_key_exists('Value', $arrValue)) {
					$mxdValue = $this->cleanFilterData($arrValue['Value']);
					$strQuote = empty($arrValue['NoQuote']) ? "'" : '';
					$strOperator = empty($arrValue['Operator']) ? '=' : strtoupper($arrValue['Operator']);
					
					switch (strtolower($strOperator)) {
						case '=':
						case '!=':
						case '>':
						case '>=':
						case '<':
						case '<=':
						case '&':
						case '|':
						case '^':
							$arrQuery[] = $arrValue['Column'] . " {$strOperator} {$strQuote}{$mxdValue}{$strQuote}";
							break;
							
						case 'between':
							$arrQuery[] = $arrValue['Column'] . " BETWEEN '" . $mxdValue[0] . "' AND '" . $mxdValue[1] . "'";
							break;
							
						case 'like':
							$arrQuery[] = $arrValue['Column'] . " LIKE '%{$mxdValue}%'";
							break;
							
						case 'begins with':
							$arrQuery[] = $arrValue['Column'] . " LIKE '{$mxdValue}%'";
							break;
							
						case 'ends with':
							$arrQuery[] = $arrValue['Column'] . " LIKE '%{$mxdValue}'";
							break;
							
						case 'in':
						case 'not in':
							$arrQuery[] = $arrValue['Column'] . " {$strOperator} ({$strQuote}" . implode("{$strQuote}, {$strQuote}", $mxdValue) . "{$strQuote})";
							break;
							
						case 'is null':
						case 'is not null':
							$arrQuery[] = $arrValue['Column'] . " {$strOperator}";
							break;								
							
						default:
							throw new CoreException(AppLanguage::translate('Invalid operator: %s', $strOperator));
					}
				} else if (!empty($arrValue['Raw'])) {
					$arrQuery[] = $arrValue['Raw'];
				} else {
					continue;
				}
			}
			
			return implode($blnOr ? ' OR ' : ' AND ', $arrQuery);
		}
		
		
		/*****************************************/
		/**     QUERY TYPE METHODS              **/
		/*****************************************/
		
		
		/**
		 * Returns true if the query is a select query.
		 *
		 * @access public
		 * @return boolean True if select
		 */
		public function isSelect() {
			return $this->strQueryType == self::SELECT_QUERY;
		}
		
		
		/**
		 * Returns true if the query is an insert query.
		 *
		 * @access public
		 * @return boolean True if insert
		 */
		public function isInsert() {
			return $this->strQueryType == self::INSERT_QUERY;
		}
		
		
		/**
		 * Returns true if the query is an update query.
		 *
		 * @access public
		 * @return boolean True if update
		 */
		public function isUpdate() {
			return $this->strQueryType == self::UPDATE_QUERY;
		}
		
		
		/**
		 * Returns true if the query is a delete query.
		 *
		 * @access public
		 * @return boolean True if delete
		 */
		public function isDelete() {
			return $this->strQueryType == self::DELETE_QUERY;
		}
		
		
		/*****************************************/
		/**     MAGIC METHODS                   **/
		/*****************************************/
	
	
		/**
		 * This allows daisy chaining calls using a more natural
		 * syntax. This is slower than using the usual methods,
		 * but is easier to read when building simple queries.
		 * 
		 * <code>
		 * $objQuery->select()->columns('foo', 'bar')->from('foobar')->limit(3);
		 * </code>
		 *
		 * @access public
		 * @param string $strMethodName The method called
		 * @param array $arrParameters The parameters passed to the method
		 * @return object This object
		 */
		public function __call($strMethodName, $arrParameters) {
			switch (strtolower($strMethodName)) {
				case 'select':
				case 'insert':
				case 'update':
				case 'delete':
					call_user_func_array(array($this, 'init' . ucfirst($strMethodName) . 'Query'), $arrParameters);
					break;
					
				case 'distinct':
					$this->addDistinct();
					return;
					
				case 'column':
					call_user_func_array(array($this, 'addColumn'), $arrParameters);
					break;
					
				case 'columns':
					foreach ($arrParameters as $mxdParams) {
						if (!is_array($mxdParams)) {
							$mxdParams = array($mxdParams);
						}
						call_user_func_array(array($this, 'addColumn'), $mxdParams);
					}
					break;
					
				case 'table':
				case 'from':
				case 'into':
					call_user_func_array(array($this, 'addTable'), $arrParameters);
					break;
					
				case 'join':
					call_user_func_array(array($this, 'addTableJoin'), $arrParameters);
					break;
					
				case 'where':
					call_user_func_array(array($this, 'addWhere'), $arrParameters);
					break;
				
				case 'groupby':
					call_user_func_array(array($this, 'addGroupBy'), $arrParameters);
					break;
				
				case 'having':
					call_user_func_array(array($this, 'addHaving'), $arrParameters);
					break;
				
				case 'orderby':
					call_user_func_array(array($this, 'addOrderBy'), $arrParameters);
					break;
					
				case 'random':
					call_user_func_array(array($this, 'addOrderRandom'), $arrParameters);
					break;
				
				case 'limit':
					call_user_func_array(array($this, 'addLimit'), $arrParameters);
					break;
				
				default:
					throw new CoreException(AppLanguage::translate('Method %s does not exist', $strMethodName));
					break;
					
			}
			
			return $this;
		}
	}