<?php
/**
 * MEIDSQLServer DAO
 * 
 * @package dao
 * @copyright University of Melbourne, 2007
 * @author Patrick Maslen <pmaslen@unimelb.edu.au>
 * @author Damian Sweeney <dsweeney@unimelb.edu.au>
 */

/**
 * Required files
 */
require_once(dirname(__FILE__) . "/../find_path.inc.php");
require_once($_SERVER["DOCUMENT_ROOT"] . LIBPATH . "/lib/daophp5/DAO.class.php");
require_once($_SERVER["DOCUMENT_ROOT"] . LIBPATH . "/lib/helpers/get_id_from_name.inc.php");
require_once($_SERVER["DOCUMENT_ROOT"] . LIBPATH . "/includes/dbconnect.inc.php");

/**
 * MEIDSQLServer DAO to collect together some useful methods
 * based on daophp5
 * allows creation, retrieval, updating and deletion of QuestionSets in the database
 * Originally designed to work with MS-SQL SERVER only. Now redesigned to work more generically,
 * also works for POSTGRESQL
 * 
 * @package dao
 */
class MEIDSQLServerDao extends DAO
{
	/**
 	 * @var string $dbconnect_string
	 * the database connection string, which will be sent to other DAO objects
	 * created by this one
 	 */
	protected $dbconnect_string;
	/**
	 * constructor
	 * @param string database connection string
	 * (dbtype://user:pass@host/dbname)
	 */
	public function __construct($dsn) 
	{
		try
		{
			parent::__construct($dsn);
			$this->dbconnect_string = $dsn;
		}
		catch (Exception $e)
		{
			echo "Couldn't connect to database";
			exit;
		}
	}

	/**
	 * updates an item in a table with a primary key
	 * @param string $table, the name of the table (table must have a primary key)
	 * @param int $id, id number of the item 
	 * @param array of string=>string $var_array values to update: a list of object variables: column=>value
	 * @param array of string=>string $key_columns, a list of column=>values which together
	 * define a unique table entry, for those tables with no primary key. This attribute is NULL
	 * by default.
	 * @TODO the conditionals before assigning to $settings are duplicated in insertTableItem. Refactor? (DRY)
	 */
	public function updateTableItem($table, $id, $var_array, $key_columns=null)
	{
		$settings = array();
		$key_conditions = array();
		foreach ($var_array as $key => $value)
		{
			if (is_string($value))
			{
				$value = trim($value);
				if (strlen($value) == 0)
				{
					$value === null;
				}
			}
			$settings[] = strtolower($key) . " = " . $this->da->escapeContext($value);
		}
		$settext = implode(", ", $settings);
		if (is_null($key_columns))
		{
			$primary_key = $this->getPrimaryColumn($table);
			$keytext = $this->da->escape($primary_key) . " = " . $this->da->escapeContext($id);
		}
		else
		{
			foreach ($key_columns as $column_name => $value)
			{
				$key_conditions[] = strtolower($column_name) . " = " . $this->da->escapeContext($value);
			}
			$keytext = implode(" AND ", $key_conditions);
		}
		$sql = "update " . $this->da->escape($table) . " set " . $settext . " where " . $keytext;
		$result = $this->update($sql);
		return $result;
	}

	/**
	 * and here's one for insertTableItem, while I'm at it
	 * @param string $table
	 * @param array of strings $var_array a list of object variables: column=>value
	 */
	public function insertTableItem($table, $var_array)
	{
		$settings = array();
		$column_list = array();
		//functionality duplicated in updateTableItem above.
		foreach ($var_array as $key => $value)
		{
		// adding a test for column data type to cope with numeric entries to test fields
		// might need to make this a generic test function and use it with updateTableItem too.
			if(get_class($this->da) == "DataAccessMssql" and is_numeric($value))
			{
				$value = $this->dataScrub($table, $key, $value);
			}	
			if (is_string($value))
			{
				$value = trim($value);
				if (empty($value))
				{
					$value === null;
				}
			}
			$column_list[] = strtolower($key);
			$settings[] = $this->da->escapeContext($value);
		}
		$columns = implode(", ", $column_list);
		$values = implode(", ", $settings);
		$sql = "insert into " . $table . " (".$columns.") values (".$values.")";
		$result = $this->update($sql);
		return $result;	
	}
	/**
	 * and a protected one for deleteTableItem
	 * (and I'd recommend you use a transaction block -- see $this->transaction)
	 * @param string $table, table to delete from
	 * @param int $id, id number of the item to delete OR
	 * @param array of string=>string $key_columns, a list of column=>values which together
	 * define a unique table entry, for those tables with no primary key. This attribute is NULL
	 * by default.
	 * @return data result
	 */
	protected function deleteTableItem($table, $id, $key_columns=null)
	{
		if (!is_null($key_columns))
		{
			$keytext = $this->makeConstraintSQL($key_columns, $table);
		}
		else
		{
			$primary_key = $this->getPrimaryColumn($table);
			$keytext = $this->da->escape($primary_key) . " = " . $this->da->escapeContext($id);
		}
		$sql = "delete from " . $this->da->escape($table)  . " where " . $keytext;
		$result = $this->update($sql);
		return $result;
	}
	/**
	 * dataScrub checks the context of a numeric value to be entered into a ms-sql database.
	 * If the field to be inserted is a numeric datatype, a numeric value is returned.
	 * If the field is a text field, a text value is returned
	 * @param string $table, the db table to check
	 * @param string $column, the column to check
	 * @param mixed $number, a numeric type
	 * @return mixed $value, either a string or number, depending on context
	 */
	 public function dataScrub($table, $column, $value)
	 {
	 	$type = $this->getDataType($table, $column);
		switch($type)
		{
			case "char":
				;
			case "varchar":
				;
			case "text":
				$value = (string)$value;
				break;
			default:
				$value = $value;
		}
		return $value;
	 }
	/**
	 * Retrieves data from a given table by primary key, or a list of key=>column values
	 * modelled on QuestionSetDao::getPollData
	 * @param string table, the name of the table to retrieve from
	 * @param int id, the id number of the record to retrieve
	 * @param array of string=>string $key_columns, a list of column=>values which together
	 * define a unique table entry, for those tables with no primary key. This attribute is NULL
	 * by default.
	 * @param string column, the column name of the table to retrieve
	 * if a single column is required (default = '*', meaning all columns)
	 * @return an associative array of column=>value, or FALSE if not found
	 * @todo (nice to have but probably would break some existing code):
	 * If only one column value returned, return it instead of an array containing it.
	 */
	public function pluck($table, $id, $key_columns=null, $column = '*')
	{
		$key_conditions = array();
		if (is_null($key_columns))
		{
			$primary_key = $this->getPrimaryColumn($table);
			//print"<pre>$primary_key</pre>";//test
			//$keytext = $this->da->escape($primary_key) . " = " . $this->da->escapeContext($id);
			$keytext = $primary_key . " = " . $this->da->escapeContext($id);
		}
		else
		{
			$eql = " = ";
			foreach ($key_columns as $column_name => $value)
			{
				$key_conditions[] = strtolower($column_name) . $eql . $this->da->escapeContext($value);
			}
			$keytext = implode(" AND ", $key_conditions);
		}
		$sql = 'SELECT '. $this->da->escape($column) . 
		' FROM ' . $this->da->escape($table) . 
		' WHERE ' . $keytext;
		$result = $this->retrieve($sql);
		$result->setFormat("ASSOC");
		if ($result->getNumRows() == 1) 
		{
			$row = $result->getRow();
			$data = array();
			if ($column != "*")
			{
				$data[$column] = $row->offsetGet($column);
			}
			else
			{
				foreach ($row as $col => $value)
				{
					$data[$col] = $value;
				}
			}
			return $data;
		}
		else
		{
			return false;
		}
	}

	/**
	 * the complement to pluck, retrieves all of the rows of a particular column for a set of conditions
	 * (in other words, a limited SELECT statement).
	 * This function supercedes functions such as QuestionDao::RetrieveAnswer 
	 * @param string table, the name of the table to retrieve from
	 * @param string column, the name of the column to retrieve
	 * column can also be an array, or the special character '*' (multi-column suck)
	 * @param array of string=>string $key_columns, a list of column=>values which together
	 * define the WHERE clause of the select statement
	 * @param array (string) $ordering or simple string, one or more column names from $table
	 * @return array(mixed) a list of results for the column. 
	 * array of the form (column1 => results, column2 => results)
	 * For a single column, returns a simple array of results.
	 */
	public function suck($table, $column, $key_columns=null, $ordering = null)
	{
		$c = null;
		$sql = $this->makeBaseSQL($table, $column);
		if (!is_null($key_columns))
		{
			$sql .= ' WHERE ' . $this->makeConstraintSQL($key_columns, $table);
		}
		if (!is_null($ordering))
		{
			$sql .= ' ' . $this->makeOrderingSQL($ordering);
		}
		$result = $this->retrieve($sql);
		return $this->processMulticolumnResults($result, $column);
	}

	/**
	 * A more general function than suck(), search() allows for a variety of comparison types to be used in retrieving records. While it has the same parameter structure as suck(), the major difference is in the contents of the $key_columns array
	 * @param string table, the name of the table to retrieve from
	 * @param string column, the name of the column(s) to retrieve
	 * column can also be an array, or the special character '*' (multi-column suck)
	 * @param array key_columns, array of the form (operator1 => array(column1 => search_term,  column2 => search_term), operator2 => array(column1 => search_term,  column2 => search_term))
	 * operator can be one of equal, contains, starts_with, ends_with, greater_than, less_than, greater_than_or_equal, less_than_or_equal
	 * search_term can be an array, but only for an equals operator (sql 'IN' clause)
	 * define the WHERE clause of the select statement
	 * @return array(mixed) a list of results for the column. 
	 * array of the form (column1 => results, column2 => results)
	 */
	public function search($table, $column, $key_columns = null)
	{
		// sanity checks
		// for a generic search without any constraints we can just use suck()
		if ($key_columns == null)
		{
			return $this->suck($table, $column);
		}
		// otherwise we'll need an array to work with
		if (!is_array($key_columns))
		{
			throw new Exception('Expected an array for third paramater in MEIDSQLServerDAO->search()');
		}
		// If the only constraints are of the type 'equal', then we can use suck
		if (count($key_columns) == 1 and array_key_exists('equal', $key_columns))
		{
			return $this->suck($table, $column, $key_columns['equal']);
		}

		// now the real fun begins
		else
		{
			// check that we have the right sort of array
			$operators = array(
				'equal',
				'contains',
				'starts_with',
				'ends_with',
				'greater_than',
				'less_than',
				'greater_than_or_equal',
				'less_than_or_equal'
				);
			foreach (array_keys($key_columns) as $key)
			{
				if (array_search($key, $operators) === false)
				{
					throw new Exception("Poorly structured array for key_columns parameter in MEIDSQLServerDAO::search(), $key is not a valid operator.");
				}
			}
			// construct the SQL
			$sql = $this->makeBaseSQL($table, $column) . ' WHERE ';
			$constraint_sql = array();
			foreach ($key_columns as $operator => $constraints)
			{
				$constraint_sql[] = $this->makeConstraintSQL($constraints, $table, $operator);
			}
			$sql .= implode(' AND ', $constraint_sql);
			$result = $this->retrieve($sql);
			return $this->processMulticolumnResults($result, $column);
		}
	}

	/**
	 * Replaces a list of entities with another, deleting excess entries as appropriate
	 * Usually used with many-many tables
	 * A more general version of QuestionDao::updateStyleHints
	 * @param string $table, the table to operate on
	 * @param array(string=>string) $key_columns a list of column=>values which together
	 * define the identity
	 * @param string $column the column to update
	 * @param array(mixed) $items, the items to add
	 */
	public function replaceList($table, $key_columns, $column, $items)
	{
		if (is_array($items))
		{
			$existing_items = $this->suck($table, $column, $key_columns);
			$already_there = array_intersect($items, $existing_items);
			$to_delete_list = array_diff($existing_items, $already_there);
			$to_delete_list2 = array();
			foreach ($to_delete_list as $d)
			{
				$to_delete_list2[] = $this->da->escapeContext($d);
			}
			$to_delete = implode(", ", $to_delete_list2);
			$to_add_list = array_diff($items, $already_there);
			$keytext = $this->makeConstraintSQL($key_columns, $table);
			if (!empty($to_delete_list))
			{
				$sql = "DELETE FROM ". $this->da->escape($table) . " WHERE " . $keytext . " AND " . $this->da->escape($column) ." IN ($to_delete)";
				$result = $this->update($sql);
			}
			foreach($to_add_list as $add)
			{
				$insert_array = array_merge($key_columns, array($column => $add));
				$this->insertTableItem($table, $insert_array);
			}
		}
	}
	/**
	 * Gets the latest id of a QuestionSet or other table with an identity (primary key)
	 * @param string table name of table having an IDENTITY column
	 * @return int id of latest IDENTITY value
	 * NB. This function is duplicated in question_dao.class.php
	 * and answerset_dao.class.php
	 * @TODO concurrency issue -- research commit and rollback for this function
	 * (or those which use it). see $this->transaction and $this->assessTransaction below
	 */
	public function getLastID($table)
	{
		// require_once($_SERVER["DOCUMENT_ROOT"] . LIBPATH . "/lib/dao/get_last_id.inc.php");
		// note: mssql function only. This $sql will need to change if the db changes.
		switch (get_class($this->da))
		{
			case "DataAccessMssql":
				$sql = "SELECT IDENT_CURRENT('" . $this->da->escape($table) . "')";
				break;
			case "DataAccessPgsql":
				$primary = $this->getPrimaryColumn($table);
				$sequence = $table . '_' . $primary . '_seq';
				$sql = "SELECT last_value FROM $sequence";
				break;
			default:
	   			// this actually won't work for anything but pgsql and mssql at the moment
				$sql = "SELECT IDENT_CURRENT('" . $this->da->escape($table) . "')";
		}
		$result = $this->retrieve($sql);
		$result->setFormat("NUM");
		$row = $result->getRow();
		return (int)$row[0];
	}
	/**
	 * getMaximum
	 * A bit of a hack; works like getLastID for non-primary unique keys governed by a sequence
	 * @param string table name of table
	 * @param string column to compare, should be unique and based on a sequence
	 * @return integer, the highest value of $column; should be the latest in a sequence
	 * @TODO behaviour with a broken sequence (eg 1, 2, 3, 6, 7) *should* work but not sure
	 */
	public function getMaximum($table, $column)
	{
		$sql = "select max(" . $this->da->escape($column) . ") from " . $this->da->escape($table);
		$result = $this->retrieve($sql);
		$result->setFormat("NUM");
		$row = $result->getRow();
		return $row[0];
	}

	/**
	 * Another helper function which would be a candidate for putting in the DAO superclass
	 * (or perhaps inserting a 'helper' layer above these dao objects.
	 * This function returns the column name of the primary key for a given table. Cross-platform, SQL compliant.
	 * @param string $table, the table name
	 * @return string the name of the primary key column
	 * @note for Postgres, requires the information_schema.key_column_usage table 
	 * to be owned by the database user
	 * @TODO there is a weak case for moving the switch statement functionality into
	 * the various DataAccess objects themselves, eg. a getPrimaryKeyPattern() method,
	 * or defining an accessible constant (or both). 
	 */
	public function getPrimaryColumn($table)
	{
		$tablename = strtolower($table);
		switch (get_class($this->da))
		{
			case "DataAccessMssql":
				$primary_key_pattern = 'PK__%';
				break;
			case "DataAccessPgsql":
				$primary_key_pattern = '%_pkey';
				break;
			default:
	   			// this actually won't work for anything but pgsql and mssql at the moment
				$primary_key_pattern = '';
		}
		$sql = "SELECT column_name FROM information_schema.key_column_usage WHERE table_name = '" . $this->da->escape(strtolower($table)) . "' AND constraint_name LIKE '$primary_key_pattern'";
		$result = $this->retrieve($sql);
		$result->setFormat("ASSOC");
		$row = $result->getRow();
		return $row['column_name'];
	}

	/**
	 * Another helper function to return the data_type of a given column in a given table
	 * Cross-platform, SQL compliant (I think).
	 * @param string $table, the table name
	 * @param string $column, the column name
	 * @return string $datatype the datatype of the column
	 */
	public function getDataType($table, $column)
	{
		$sql = "SELECT data_type FROM information_schema.columns WHERE table_name = '".$this->da->escape(strtolower($table))."' AND column_name = '".$this->da->escape(strtolower($column))."'";
		$result = $this->retrieve($sql);
		$result->setFormat("NUM");
		$row = $result->getRow();
		return $row[0];
	}

	/**
	 * Build the first part of a sql select statement
	 *
	 * @param string table the table name
	 * @param string column, the name of the column to retrieve
	 * column can also be an array, or the special character '*'
	 * @return string sql statement
	 */
	private function makeBaseSQL($table, $column)
	{
		$all_columns = "";
		if (is_array($column))
		{
			foreach ($column as $column_name)
			{
				$all_columns .= $this->da->escape($column_name) . ', ';
			}
			$all_columns = rtrim($all_columns, ', ');
		}
		else if ($column == '*')
		{
			$all_columns = $column;
		}
		else
		{
			$all_columns = $this->da->escape($column);
		}
		return 'SELECT '. $all_columns . ' FROM ' . $this->da->escape($table);
	}
	/**
	 * adds an ordering clause to a SQL statement
	 * @param array(string) or simple string $ordering, a list of columns to order the query by
	 * For convenience, a single term need not be enclosed in an array
	 * @return string
	 */
	private function makeOrderingSQL($ordering)
	{
		$all_columns = "";
		if (is_array($ordering))
		{
			foreach ($ordering as $column_name)
			{
				$all_columns .= $this->da->escape($column_name) . ', ';
			}
			$all_columns = rtrim($all_columns, ', ');
		}
		else
		{
			$all_columns = $this->da->escape($ordering);
		}
		return 'ORDER BY ' . $all_columns;
	}

	/**
	 * gets a list of column=>value and returns a sql-compatible string
	 * Used by various functions above
	 * @param array(string=>string) $key_columns column=>value
	 * OR array(string=>array) column=>values for an 'IN' clause
	 * @param string $table, table name, necessary for mssql databases.
	 * @param string $operator one of equal, contains, starts_with, ends_with, greater_than, less_than, greater_than_or_equal, less_than_or_equal
	 * @return string $keytext, the SQL appropriate for a WHERE clause
	 */
	public function makeConstraintSQL($key_columns, $table, $operator_requested = 'equal')
	{
		$keytext = "";
		$key_conditions = array();
		if (is_array($key_columns))
		{
			foreach ($key_columns as $column_name => $value)
			{
				$final_value = "";
				$operator = ' = ';
				if (is_array($value))
				{
					if ($operator_requested == 'equal')
					{
						$operator = ' IN ';
						$final_value .= '(';
						foreach ($value as $item)
						{
							$final_value .= $this->da->escapeContext($item) . ', ';
						}
						$final_value = rtrim($final_value, ', ');
						$final_value .= ')';
					}
					else
					{
						throw new Exception("Can't search multiple values using the $operator_requested operator.");
					}
				}
				else
				{
					switch ($operator_requested)
					{
						case 'contains':
							$final_value = $this->da->escapeContext("%$value%");
							break;
						case 'starts_with':
							$final_value = $this->da->escapeContext("$value%");
							break;
						case 'ends_with':
							$final_value = $this->da->escapeContext("%$value");
							break;
						default:
							$final_value = $this->da->escapeContext($value);
							break;
					}
				}
				switch ($operator_requested)
				{
					case 'contains':
					case 'starts_with':
					case 'ends_with':
						$operator = ' LIKE ';
						break;
					case 'greater_than':
						$operator = ' > ';
						break;
					case 'less_than':
						$operator = ' < ';
						break;
					case 'greater_than_or_equal':
						$operator = ' >= ';
						break;
					case 'less_than_or_equal':
						$operator = ' <= ';
						break;
					default:
						break;
				}
				if (get_class($this->da) == "DataAccessMssql")
				{
					$type = $this->getDataType($table, $column_name);
					if ($type == 'text')
					{
						if ($operator_requested == 'equal')
						{
							$operator = " LIKE ";
						}
						else
						{
							throw new Exception("Can't search using this operator on this type of database.");
						}
					}
				}
				$key_conditions[] = strtolower($column_name) . $operator . $final_value;
			}
		}
		$keytext = implode(" AND ", $key_conditions);
		return $keytext;
	}

	/**
	 * takes a result object and returns an array of the results
	 * @param object result from $this->retrieve($sql)
	 * @param array result values organised more sanely
	 */
	public function processMulticolumnResults($result, $column)
	{
		$result_array = array();
		if (count($column) > 1 or $column == '*')
		{
			$result->setFormat("ASSOC");
		}
		else
		{
			$result->setFormat("NUM");
		}
		if ($result->getNumRows() >= 1) 
		{
			$data = array();
			foreach ($result as $row)
			{
				if ($column == '*')
				{
					foreach ($row as $c => $value)
					{
						$data[$c] = $value;
					}
					$result_array[] = $data;
				}
				else if (is_array($column))
				{
					foreach ($column as $c)
					{
						$data[$c] = $row->offsetGet($c);
					}
					$result_array[] = $data;
				}
				else
				{
					$result_array[] = $row->offsetGet(0);
				}
			}
		}
		return $result_array;
	}

	/**
	 * set identity insert
	 * used only for mssql databases, sets the IDENTITY INSERT for a particular table to
	 * true or false, depending on the $veracity variable
	 * @param string table name to activate
	 * @param boolean $veracity, true for ON, false for OFF
	 * The identity insert needs to wrap inserts which preserve their unique ids from db to db.
	 * @return the database result, or false if not mssql
	 */
	public function setIdentityInsert($table, $veracity)
	{
		$da_class = get_class($this->da);
		$sql = "";
		$activity = "";
		if ($veracity == true)
		{
			$activity = "ON";
		}
		else
		{
			$activity = "OFF";
		}
		if (get_class($this->da) == "DataAccessMssql")
		{
			$sql = "SET IDENTITY_INSERT " . $this->da->escape($table) . " $activity";
			$result = $this->update($sql);
			return $result;	
		}
		else
		{
			return false;
		}
	}
	/**
	 * transaction
	 * uses database commands for commit
	 * Note: syntax is the same for postgresql and mssql. Have not tested other dbs.
	 * @param string $operation, one of 'begin', 'commit' or 'rollback'
	 * @return the database result
	 */
	public function transaction($operation)
	{
		$sql = '';
		$result = false;
		switch (strtolower($operation))
		{
			case "begin":
			$sql = "begin";
			break;
			case "commit":
			$sql = "commit";
			break;
			case "rollback":
			$sql = "rollback";
			break;
			default:
			;
		}
		if (!empty($sql))
		{
			$sql .= " transaction";
			$result = $this->update($sql);
		}
		return $result;
	}
	/**
	 * assessTransaction
	 * determines whether to COMMIT or ROLLBACK a transaction
	 * based on the boolean truth of the transaction results
	 * Requires a previously existing BEGIN (see transaction method above)
	 * Transplanted from ShortCourseDao.
	 * USAGE:
	 *	1. $this->transaction('begin')
	 *	2. add transactions to $transaction_array
	 *	3. $this->assessTransaction($transaction_array) to complete transaction with rollback or commit
	 * @param array(transaction results), the results from a series of dao updates or retrievals
	 * @return boolean true if all of the results are not false
	 */
	public function assessTransaction($result_array)
	{
		$committed = false;
		if (!is_array($result_array))
		{
			$result_array = array();
		}
		$transaction_count = count($result_array);
		$true_transactions = count(array_filter($result_array));
		if ($true_transactions == $transaction_count)
		{
			$this->transaction('commit');
			$committed = true;
		}
		else
		{
			$this->transaction('rollback');
		}
		return $committed;
	}
}
?>
