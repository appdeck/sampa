<?php
/**
*
*	Abstraction to SQL queries by using PDO
*
*	@package sampa\Core\SQL
*	@copyright 2013 appdeck
*	@link http://github.com/appdeck/sampa
*	@version 0.1
*	@since 0.1
*	@license http://www.gnu.org/licenses/gpl-3.0.html GNU General Public License, version 3
*/

namespace sampa\Core;

use sampa\Exception;

final class SQL {
	private $pdo;
	private $statement = null;
	private $id = null;
	private $cache = array();
	private $cache_status = false;

	/**
	*	Class constructor
	*
	*	@params string $dsn
	*	@params string $user
	*	@params string $pass
	*	@params boolean $pool
	*	@return void
	*/
	public function __construct($dsn, $user, $pass, $pool = false) {
		try {
			$options = array(
				\PDO::ATTR_EMULATE_PREPARES => false,
				\PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION
			);
			if ($pool)
				$options[\PDO::ATTR_PERSISTENT] = true;
			$this->pdo = new \PDO($dsn, $user, $pass, $options);
		} catch (\Exception $e) {
			throw new Exception\DatabaseConnection($e->getMessage());
		}
	}

	/**
	*	Enable/disable query cache
	*
	*	@param boolean $value Cache state
	*	@return void
	*/
	public function enable_cache($value = true) {
		$this->cache_status = $value;
	}

	/**
	*	Starts a transaction block
	*
	*	@return boolean
	*/
	public function transaction_begin() {
		if ($this->pdo->inTransaction())
			return false;
		return $this->pdo->beginTransaction();
	}

	/**
	*	Returns the current transaction block state
	*
	*	@return boolean
	*/
	public function in_transaction() {
		return $this->pdo->inTransaction();
	}

	/**
	*	Commits a transaction block
	*
	*	@return boolean
	*/
	public function transaction_end() {
		if ($this->pdo->inTransaction())
			return $this->pdo->commit();
		return false;
	}

	/**
	*	Cancels (rollback) a transaction block
	*
	*	@return boolean
	*/
	public function transaction_cancel() {
		if ($this->pdo->inTransaction())
			return $this->pdo->rollBack();
		return false;
	}

	/**
	*	Dumps the active statement content
	*
	*	@return void
	*/
	public function debug() {
		if (!is_null($this->statement))
			$this->statement->debugDumpParams();
	}

	/**
	*	Binds parameters and values to the statement handler
	*
	*	@return void
	*/
	private function bind(array $markers) {
		foreach ($markers as $marker => $value) {
			if (is_null($value))
				$type = \PDO::PARAM_NULL;
			else if (is_numeric($value))
				$type = \PDO::PARAM_INT;
			else if (is_bool($value))
				$type = \PDO::PARAM_BOOL;
			else
				$type = \PDO::PARAM_STR;
			$this->statement->bindValue($marker, $value, $type);
		}
	}

	/**
	*	Executes a SQL query with a single data set
	*
	*	@param string $sql SQL query to be executed
	*	@param array $markers an array with placeholder => value
	*	@return boolean
	*/
	public function exec($sql, array $markers = array()) {
		try {
			$this->id = sha1($sql);
			if ($this->cache_status) {
				if (isset($this->cache[$this->id]))
					$this->statement = $this->cache[$this->id];
				else {
					$this->statement = $this->pdo->prepare($sql);
					if ($this->statement === false)
						return false;
					$this->cache[$this->id] = $this->statement;
				}
			} else {
				$this->statement = $this->pdo->prepare($sql);
				if ($this->statement === false)
					return false;
			}
			if (count($markers))
				$this->bind($markers);
			return $this->statement->execute();
		} catch (\Exception $e) {
			throw new Exception\DatabaseQuery(sprintf('%s (%s)', $e->getMessage(), $sql));
		}
	}

	/**
	*	Executes a SQL query with multiple data sets
	*
	*	@param string $sql SQL query to be executed
	*	@param array $markers an array with placeholder => value
	*	@return boolean
	*/
	public function multi_exec($sql, array $markers) {
		try {
			$this->id = sha1($sql);
			if ($this->cache_status) {
				if (isset($this->cache[$this->id]))
					$this->statement = $this->cache[$this->id];
				else {
					$this->statement = $this->pdo->prepare($sql);
					if ($this->statement === false)
						return false;
					$this->cache[$this->id] = $this->statement;
				}
			} else {
				$this->statement = $this->pdo->prepare($sql);
				if ($this->statement === false)
						return false;
			}
			$flag = true;
			foreach ($markers as $internal) {
				$this->bind($internal);
				$flag |= $this->statement->execute();
			}
			return $flag;
		} catch (\Exception $e) {
			throw new Exception\DatabaseQuery(sprintf('%s (%s)', $e->getMessage(), $sql));
		}
	}

	/**
	*	Executes a raw SQL query
	*
	*	@param string $sql SQL query to be executed
	*	@return mixed
	*/
	public function raw($sql) {
		try {
			return $this->pdo->exec($sql);
		} catch (\Exception $e) {
			throw new Exception\DatabaseQuery(sprintf('%s (%s)', $e->getMessage(), $sql));
		}
	}

	/**
	*	Returns an extended error information associated with the last operation on the database handle
	*
	*	@return array
	*/
	public function last_connection_error() {
		if (!$this->status())
			return false;
		return $this->pdo->errorInfo();
	}

	/**
	*	Returns an extended error information associated with the last operation on the statement handle
	*
	*	@param string $tag Statement tag name
	*	@return array
	*/
	public function last_statement_error($tag = null) {
		if (is_null($tag)) {
			if (is_null($this->statement))
				return false;
			return $this->statement->errorInfo();
		}
		if (isset($this->cache[$tag]))
			return $this->cache[$tag]->errorInfo;
		return false;
	}

	/**
	*	Returns the id of the last inserted row
	*
	*	@return int
	*/
	public function last_id($name = null) {
		return $this->pdo->lastInsertId($name);
	}

	/**
	*	Tags the current statement
	*
	*	@return string
	*/
	public function tag() {
		if ($this->cache_status === false)
			throw new Exception\DatabaseCache;
		if (!isset($this->cache[$this->id]))
			$this->cache[$this->id] = $this->statement;
		return $this->id;
	}

	/**
	*	Returns the number of rows affected by INSERT/UPDATE/DELETE queries
	*
	*	@return int
	*/
	public function count($tag = null) {
		if (is_null($tag)) {
			if (is_null($this->statement))
				return -1;
			return $this->statement->rowCount();
		}
		if (isset($this->cache[$tag]))
			return $this->cache[$tag]->rowCount();
		return -1;
	}

	/**
	*	Fetches all the results from a SELECT query
	*
	*	@param string $tag Statement tag name
	*	@return array
	*/
	public function results($tag = null) {
		if (is_null($tag)) {
			if (is_null($this->statement))
				return array();
			return $this->statement->fetchAll(\PDO::FETCH_ASSOC);
		}
		if (isset($this->cache[$tag]))
			return $this->cache[$tag]->fetchAll(\PDO::FETCH_ASSOC);
		return array();
	}

	/**
	*	Fetches the next result from a SELECT query
	*
	*	@param string $tag Statement tag name
	*	@return array
	*/
	public function next($tag = null) {
		if (is_null($tag)) {
			if (is_null($this->statement))
				return array();
			return $this->statement->fetch(\PDO::FETCH_ASSOC);
		}
		if (isset($this->cache[$tag]))
			return $this->cache[$tag]->fetch(\PDO::FETCH_ASSOC);
		return array();
	}
}
