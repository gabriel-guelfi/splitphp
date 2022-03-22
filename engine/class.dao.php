<?php

class Dao
{
  // An instance of the class Dblink.
  private $dblink;
  // An instance of the class Sql.
  private $sqlBuilder;
  private $sqlParameters;
  // Holds table information
  private $workingTable;

  private $filters;

  private $params;

  private $executionControl;

  // It sets the main table name, instantiate class Mysql and defines the table's primary key.
  public function __construct()
  {
    require_once INCLUDE_PATH . "/engine/databasemodules/" . DBTYPE . "/class.dbmetadata.php";

    $this->dblink = System::loadClass(INCLUDE_PATH . "/engine/databasemodules/" . DBTYPE . "/class.dblink.php", 'dblink');
    $this->sqlBuilder = System::loadClass(INCLUDE_PATH . "/engine/databasemodules/" . DBTYPE . "/class.sql.php", 'sql');
    $this->sqlParameters = System::loadClass(INCLUDE_PATH . "/engine/databasemodules/" . DBTYPE . "/class.sqlparams.php", 'sqlParams');
    $this->workingTable = null;
    $this->filters = [];
    $this->params = [];
    $this->tablePrefix = null;

    $this->executionControl = (object) [
      'executionPileHashes' => ['initial_state'],
      'executionStatesSnapshots' => [
        'initial_state' => (object) [
          'workingTable' => $this->workingTable,
          'filters' => $this->filters,
          'params' => $this->params,
          'tablePrefix' => $this->tablePrefix
        ]
      ]
    ];
  }

  protected final function getTable(string $tableName)
  {
    $this->updateCurrentExecution();

    $this->workingTable = $tableName;
    $this->filters = [];
    $this->params = [];
    $this->tablePrefix = null;

    $this->registerNewExecution();

    return $this;
  }

  protected final function insert($obj, bool $debug = false)
  {
    if (is_null($this->workingTable)) {
      throw new Exception('Invalid Working Table Name. Dao is not properly set up');
      return false;
    }

    $obj = (object) $obj;

    $sql = $this->sqlBuilder->insert($obj, $this->workingTable);

    if ($debug)
      return $sql->output(true);

    $res = $this->dblink->getConnection('writer')->runsql($sql->output(true));
    $key = Dbmetadata::tbPrimaryKey($this->workingTable);
    $obj->$key = $res;

    $this->returnToPreviousExecution();

    return $obj;
  }

  protected final function update($obj, bool $debug = false)
  {
    if (is_null($this->workingTable)) {
      throw new Exception('Invalid Working Table Name. Dao is not properly set up');
      return false;
    }

    $obj = (object) $obj;

    if (!empty($this->params)) {
      $parameterized = $this->sqlParameters->parameterize($this->params);
      $this->filters = $parameterized->filters;
    }

    $sql = $this->sqlBuilder->update($obj, $this->workingTable);
    if (!empty($this->filters))
      $sql->where($this->filters);

    if ($debug)
      return $sql->output(true);

    $res = $this->dblink->getConnection('writer')->runsql($sql->output(true));

    $this->returnToPreviousExecution();

    return $res;
  }

  protected final function delete(bool $debug = false)
  {
    if (is_null($this->workingTable)) {
      throw new Exception('Invalid Working Table Name. Dao is not properly set up');
      return false;
    }

    if (!empty($this->params)) {
      $parameterized = $this->sqlParameters->parameterize($this->params);
      $this->filters = $parameterized->filters;
    }

    $sql = $this->sqlBuilder->delete($this->workingTable);
    if (!empty($this->filters))
      $sql->where($this->filters);

    if ($debug)
      return $sql->output(true);

    $res = $this->dblink->getConnection('writer')->runsql($sql->output(true));

    $this->returnToPreviousExecution();

    return $res;
  }

  protected final function find(string $sql = null, bool $debug = false)
  {
    // Check for defined entity:
    if (is_null($this->workingTable)) {
      throw new Exception('Invalid Working Table Name. Dao is not properly set up');
      return false;
    }

    // If argument is a SQL file path, include it, else treat argument as the SQL itself:
    $path = INCLUDE_PATH . "/application/sql/" . $sql . ".sql";
    if (is_file($path)) {
      $sql = file_get_contents($path);
    }

    $buildWhereClause = false;
    if (empty($sql)) {
      $sql = "SELECT * FROM `" . $this->workingTable . "`";
      $buildWhereClause = true;
    }

    if (!empty($this->params)) {
      $parameterized = $this->sqlParameters->parameterize($this->params, $sql, $this->tablePrefix);
      $this->filters = $parameterized->filters;
      $sql = $parameterized->sql;
      $buildWhereClause = false;
    }

    if ($buildWhereClause) {
      $sqlObj = $this->sqlBuilder->write($sql, $this->workingTable)->where($this->filters)->output(true);
    } else {
      // Sanitize Filter Data and replace values:
      for ($i = 0; $i < count($this->filters); $i++) {
        $f = &$this->filters[$i];

        if ($f->sanitize) {
          $f->value = $this->dblink->getConnection('reader')->escapevar($f->value);

          if (!is_numeric($f->value) && is_string($f->value)) {
            $f->value = "'" . $f->value . "'";
          }
        }

        $sql = str_replace('?' . $f->key . '?', $f->value, $sql);
      }

      // Create SQL input object:
      $sqlObj = $this->sqlBuilder->write($sql, $this->workingTable)->output(true);
    }

    if ($debug)
      return $sqlObj;
    // Run SQL and store its result:
    $res = $this->dblink->getConnection('reader')->runsql($sqlObj);

    $this->returnToPreviousExecution();
    $this->dblink->disconnect('reader');

    return $res;
  }

  protected final function first(string $sql = null, bool $debug = false)
  {
    $dbData = $this->find($sql, $debug);

    if ($debug) return $dbData;

    if (!empty($dbData)) return $dbData[0];
    else return null;
  }

  protected final function fetch(callable $callback, string $sql = null, $debug = false)
  {
    // Gets query result:
    $res = $this->find($sql, $debug);

    // Iterates over result, calling callback function for each iteration:
    foreach ($res as &$row) {
      $callback($row);
    }

    return $res;
  }

  protected final function bindParams($params, $tbPrefix = null)
  {
    if(!empty($this->filters)) throw new Exception("You cannot use bindParams() alongside filtering methods.");

    $this->params = $params;
    $this->tablePrefix = $tbPrefix;

    return $this;
  }

  protected final function filter($key, $sanitize = true)
  {
    if(!empty($this->filters)) throw new Exception("You cannot use filter() method alongside bindParams().");

    $filter = (object) [
      'key' => $key,
      'value' => null,
      'joint' => null,
      'operator' => null,
      'sanitize' => $sanitize
    ];
    array_push($this->filters, $filter);

    return $this;
  }

  protected final function and($key, $sanitize = true)
  {
    if (count($this->filters) == 0) {
      throw new Exception('You can only call this method after calling filter() first.');
      return false;
    }
    $filter = (object) [
      'key' => $key,
      'value' => null,
      'joint' => 'AND',
      'operator' => null,
      'sanitize' => $sanitize
    ];

    array_push($this->filters, $filter);

    return $this;
  }

  protected final function or($key, $sanitize = true)
  {
    if (count($this->filters) == 0) {
      throw new Exception('You can only call this method after calling filter() first.');
      return false;
    }
    $filter = (object) [
      'key' => $key,
      'value' => null,
      'joint' => 'OR',
      'operator' => null,
      'sanitize' => $sanitize
    ];

    array_push($this->filters, $filter);

    return $this;
  }

  protected final function equalsTo($value)
  {
    $i = count($this->filters);
    if ($i == 0 || !is_null($this->filters[$i - 1]->value)) {
      throw new Exception('This method can only be called right after one of the filtering methods.');
      return false;
    }

    $i--;

    $this->filters[$i]->value = $value;
    $this->filters[$i]->operator = '=';

    return $this;
  }

  protected final function differentFrom($value)
  {
    $i = count($this->filters);
    if ($i == 0 || !is_null($this->filters[$i - 1]->value)) {
      throw new Exception('This method can only be called right after one of the filtering methods.');
      return false;
    }

    $i--;

    $this->filters[$i]->value = $value;
    $this->filters[$i]->operator = '<>';

    return $this;
  }

  protected final function biggerThan($value)
  {
    $i = count($this->filters);
    if ($i == 0 || !is_null($this->filters[$i - 1]->value)) {
      throw new Exception('This method can only be called right after one of the filtering methods.');
      return false;
    }

    $i--;

    $this->filters[$i]->value = $value;
    $this->filters[$i]->operator = '>';

    return $this;
  }

  protected final function lesserThan($value)
  {
    $i = count($this->filters);
    if ($i == 0 || !is_null($this->filters[$i - 1]->value)) {
      throw new Exception('This method can only be called right after one of the filtering methods.');
      return false;
    }

    $i--;

    $this->filters[$i]->value = $value;
    $this->filters[$i]->operator = '<';

    return $this;
  }

  protected final function biggerOrEqualsTo($value)
  {
    $i = count($this->filters);
    if ($i == 0 || !is_null($this->filters[$i - 1]->value)) {
      throw new Exception('This method can only be called right after one of the filtering methods.');
      return false;
    }

    $i--;

    $this->filters[$i]->value = $value;
    $this->filters[$i]->operator = '>=';

    return $this;
  }

  protected final function lesserOrEqualsTo($value)
  {
    $i = count($this->filters);
    if ($i == 0 || !is_null($this->filters[$i - 1]->value)) {
      throw new Exception('This method can only be called right after one of the filtering methods.');
      return false;
    }

    $i--;

    $this->filters[$i]->value = $value;
    $this->filters[$i]->operator = '<=';

    return $this;
  }

  protected final function likeOf($value)
  {
    $i = count($this->filters);
    if ($i == 0 || !is_null($this->filters[$i - 1]->value)) {
      throw new Exception('This method can only be called right after one of the filtering methods.');
      return false;
    }

    $i--;

    $this->filters[$i]->value = $value;
    $this->filters[$i]->operator = 'LIKE';

    return $this;
  }

  protected final function getFilters()
  {
    return $this->filters;
  }

  private function updateCurrentExecution()
  {
    $currentExecutionHash = $this->executionControl->executionPileHashes[0];

    $this->executionControl->executionStatesSnapshots[$currentExecutionHash] = (object) [
      'workingTable' => $this->workingTable,
      'filters' => $this->filters,
      'params' => $this->params,
      'tablePrefix' => $this->tablePrefix
    ];
  }

  private function registerNewExecution()
  {
    $newExecutionHash = 'daoexc-' . uniqid();

    array_unshift($this->executionControl->executionPileHashes, $newExecutionHash);

    $this->executionControl->executionStatesSnapshots[$newExecutionHash] = (object) [
      'workingTable' => $this->workingTable,
      'filters' => $this->filters,
      'params' => $this->params,
      'tablePrefix' => $this->tablePrefix
    ];
  }

  private function returnToPreviousExecution()
  {
    // 1. Unset the first hash in executionPileHashes array and its respective execution state snapshot:
    unset($this->executionControl->executionStatesSnapshots[$this->executionControl->executionPileHashes[0]]);
    array_shift($this->executionControl->executionPileHashes);

    // 2. Restore the Dao's working table and filters with the data in the snapshot,
    // identified by the remaining first element of the executionPileHashes array:
    $remainingHash = $this->executionControl->executionPileHashes[0];
    $this->workingTable = $this->executionControl->executionStatesSnapshots[$remainingHash]->workingTable;
    $this->filters = $this->executionControl->executionStatesSnapshots[$remainingHash]->filters;
    $this->params = $this->executionControl->executionStatesSnapshots[$remainingHash]->params;
    $this->tablePrefix = $this->executionControl->executionStatesSnapshots[$remainingHash]->tablePrefix;
  }
}
