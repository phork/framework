<?php
	/**
	 * SqlQuery.interface.php
	 *
	 * The SQL interface for the database query classes 
	 * to implement.
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
	interface SqlQuery {
	
		public function __construct(Sql $objDb);
		public function useWhereOr();
		public function useHavingOr();
		public function initSelectQuery($blnPrepareCount = false);
		public function initInsertQuery();
		public function initUpdateQuery();
		public function initDeleteQuery();
		public function addTable($strTable, $strAlias = null);
		public function addTableJoin($strTable, $strAlias = null, $mxdJoinUsing = null, $strJoinType = null);
		public function addColumn();
		public function addDistinct();
		public function addWhere($strColumn, $strValue, $strOperator = '=', $blnNoQuote = false);
		public function addWhereRaw($strWhere);
		public function addHaving($strColumn, $strValue, $strOperator = '=', $blnNoQuote = false);
		public function addHavingRaw($strHaving);
		public function addGroupBy($strColumn);
		public function addOrderBy($strColumn, $strSort = null);
		public function addOrderRandom();
		public function addLimit($intLimit, $intOffset = null);
		public function buildQuery();
		public function buildCountQuery($strColumn = 'count');
		public function buildInsertMultiQuery(array $arrQuery);
		public function buildInsertFromQuery(SqlQuery $objQuery);
		public function buildInsertOrUpdateQuery(SqlQuery $objQuery = null);
		public function buildFunction($strFunction);
	}