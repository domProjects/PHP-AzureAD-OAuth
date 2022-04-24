<?php
/**
 * 
 * Original project: Katy Nicholson - https://github.com/CoasterKaty
 *
 */

require_once dirname(__FILE__) . '/config.inc';

class modDB
{
	/**
	 * xxxxx xxxxx xxxxx
	 *
	 * @return xxxxx
	 */
	protected $connectDb;

	/**
	 * xxxxx xxxxx xxxxx
	 *
	 * @return xxxxx
	 */
	public function __construct()
	{
		error_reporting(0);
		mysqli_report(MYSQLI_REPORT_OFF);

		$mysqli = mysqli_connect(_MYSQL_HOST, _MYSQL_USER, _MYSQL_PASS, _MYSQL_DB);

		if ($mysqli->connect_errno)
		{
			throw new RuntimeException('mysqli connection error: ' . $mysqli->connect_error);
		}

		mysqli_set_charset($mysqli, _MYSQL_CHARSET);

		if ($mysqli->errno)
		{
			throw new RuntimeException('mysqli error: ' . $mysqli->error);
		}

		$this->connectDb = $mysqli;
	}

	/**
	 * xxxxx xxxxx xxxxx
	 *
	 * @return array
	 */
	public function QueryArray($strQuery): array
	{
		$query = $this->Query($strQuery);

		while ($myRow = $this->Fetch($query))
		{
			$ret[] = $myRow;
		}

		return $ret;
	}

	/**
	 * xxxxx xxxxx xxxxx
	 *
	 * @return xxxxx
	 */
	public function QuerySingle($strQuery)
	{
		$query = $this->Query($strQuery);

		if (! is_bool($query))
		{
			return mysqli_fetch_array($query, MYSQLI_ASSOC);
		}
	}

	/**
	 * xxxxx xxxxx xxxxx
	 *
	 * @return xxxxx
	 */
	public function Fetch(&$mysqlQuery)
	{
		return mysqli_fetch_array($mysqlQuery, MYSQLI_ASSOC);
	}

	/**
	 * xxxxx xxxxx xxxxx
	 *
	 * @return xxxxx
	 */
	public function Query($strQuery)
	{
		return mysqli_query($this->connectDb, $strQuery);
	}

	/**
	 * xxxxx xxxxx xxxxx
	 *
	 * @return xxxxx
	 */
	public function Delete($table, $conditionArray)
	{
		$query = 'DELETE FROM `' . $table . '` WHERE';
		$intCount = 0;

		foreach ($conditionArray as $fieldName => $fieldValue)
		{
			if ($intCount > 0) $query .= ' AND ';
				$query .= '`' . $fieldName . '` = \'' . mysqli_real_escape_string($this->connectDb, $fieldValue) . '\'';
				$intCount++;
		}

		return mysqli_query($this->connectDb, $query);
	}

	/**
	 * xxxxx xxxxx xxxxx
	 *
	 * @return xxxxx
	 */
	public function Insert($table, $fieldArray)
	{
		$query = 'INSERT INTO `' . $table . '` (';
		$intCount = 0;

		foreach ($fieldArray as $fieldName => $fieldValue)
		{
			if ($intCount > 0) $query .= ', ';

			if (substr($fieldName, 0, 1) == '!')
			{
				$query .= '`' . substr($fieldName, 1) . '`';
			}
			else
			{
				$query .= '`' . $fieldName . '`';
			}

			$intCount++;
		}

		$query .= ') VALUES (';
		$intCount = 0;

		foreach ($fieldArray as $fieldName => $fieldValue)
		{
			if ($intCount > 0) $query .= ', ';

			if (substr($fieldName, 0, 1) == '!')
			{
				$query .= mysqli_real_escape_string($this->connectDb, $fieldValue);
			}
			else
			{
				$query .= '\'' . mysqli_real_escape_string($this->connectDb, $fieldValue) . '\'';
			}

			$intCount++;
		}

		$query .= ')';

		$myQry = mysqli_query($this->connectDb, $query);
		return mysqli_insert_id($this->connectDb);
	}

	/**
	 * xxxxx xxxxx xxxxx
	 *
	 * @return xxxxx
	 */
	public function Update($table, $fieldArray, $conditionArray)
	{
		$query = 'UPDATE `' . $table . '` SET ';
		$intCount = 0;

		foreach ($fieldArray as $fieldName => $fieldValue)
		{
			if ($intCount > 0) $query .= ', ';

			if (substr($fieldName, 0, 1) == '!')
			{
				$query .= '`' . substr($fieldName, 1) . '`=' . mysqli_real_escape_string($this->connectDb, $fieldValue);
			}
			else
			{
				$query .= '`' . $fieldName . '`=\'' . mysqli_real_escape_string($this->connectDb, $fieldValue) . '\'';
			}

			$intCount++;
		}

		$intCount = 0;
		$query .= ' WHERE ';

		foreach ($conditionArray as $fieldName => $fieldValue)
		{
			if ($intCount > 0) $query .= ' AND ';
			$query .= '`' . $fieldName . '` = \'' . mysqli_real_escape_string($this->connectDb, $fieldValue) . '\'';
			$intCount++;
		}

		return mysqli_query($this->connectDb, $query);
	}

	/**
	 * xxxxx xxxxx xxxxx
	 *
	 * @return xxxxx
	 */
	public function Count($query)
	{
		$query = 'SELECT COUNT(*) as cnt FROM (' . $query . ') as tDerivedCount';
		$count = $this->QuerySingle($query);

		return (! empty($count['cnt']) ? $count['cnt'] : 0);
	}

	/**
	 * xxxxx xxxxx xxxxx
	 *
	 * @return xxxxx
	 */
	public function Escape($string)
	{
		return mysqli_real_escape_string($this->connectDb, $string);
	}

	/**
	 * xxxxx xxxxx xxxxx
	 *
	 * @return xxxxx
	 */
	public function Error()
	{
		return mysqli_error($this->connectDb);
	}
}

?>