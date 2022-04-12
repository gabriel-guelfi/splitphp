<?php
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//                                                                                                                                                                //
//                                                                ** SPLIT PHP FRAMEWORK **                                                                       //
// This file is part of *SPLIT PHP Framework*                                                                                                                     //
//                                                                                                                                                                //
// Why "SPLIT"? Firstly because the word "split" is a reference to micro-services and split systems architecture (of course you can make monoliths with it,       //
// if that's your thing). Furthermore, it is an acronym for these 5 bound concepts which are the bases that this framework leans on, which are: "Simplicity",     //
// "Purity", "Lightness", "Intuitiveness", "Target Minded"                                                                                                        //
//                                                                                                                                                                //
// See more info about it at: https://github.com/gabriel-guelfi/split-php                                                                                         //
//                                                                                                                                                                //
// MIT License                                                                                                                                                    //
//                                                                                                                                                                //
// Copyright (c) 2022 SPLIT PHP Framework Community                                                                                                               //
//                                                                                                                                                                //
// Permission is hereby granted, free of charge, to any person obtaining a copy of this software and associated documentation files (the "Software"), to          //
// deal in the Software without restriction, including without limitation the rights to use, copy, modify, merge, publish, distribute, sublicense, and/or         //
// sell copies of the Software, and to permit persons to whom the Software is furnished to do so, subject to the following conditions:                            //
//                                                                                                                                                                //
// The above copyright notice and this permission notice shall be included in all copies or substantial portions of the Software.                                 //
//                                                                                                                                                                //
// THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,FITNESS     //
// FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY           //
// WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.     //
//                                                                                                                                                                //
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

namespace engine;

use Exception;
use engine\databasemodules\mysql\Dbmetadata;

/**
 * Class Dao
 * 
 * This class is responsible to manage operations on the database.
 *
 * @package engine
 */
class Dao
{
  /**
   * @var Dblink $dblink
   * Stores an instance of the class Dblink.
   */
  private $dblink;

  /**
   * @var Sql $sqlBuilder
   * Stores an instance of the class Sql.
   */
  private $sqlBuilder;

  /**
   * @var SqlParams $sqlParameters
   * Stores an instance of the class SqlParams.
   */
  private $sqlParameters;

  /**
   * @var string $workingTable
   * Stores the name of the current execution's working table.
   */
  private $workingTable;

  /**
   * @var array $filters
   * An array of objects, on which each object contains settings of the filters that wil be applied on the operation.
   */
  private $filters;
  
  /**
   * @var array $params
   * An array containing the parameters dataset, which will be applied to current operation when not empty.
   */
  private $params;
  
  /**
   * @var object $executionControl
   * This object contains information about the state of multiple nested operations, storing the states and indexes of each nested execution. 
   */
  private $executionControl;

  /** 
   * Instantiates this class, loading the required classes, setting the state properties to their initial values and registering a first initial 
   * execution on Dao::executionControl. Returns the instance of this class created this way (constructor).
   * 
   * @return Dao 
   */
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

  /** 
   * Updates current execution control with the current state, resets this state, setting Dao::workingTable with the passed $tableName, registers 
   * a new execution on execution control, then returns the instance of the class.
   * 
   * @param string $tableName
   * @return Dao 
   */
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

  /** 
   * Inserts the data passed on $obj as a new register on the database, then returns this object, with the newly created primary ey added to it. 
   * If $debug were set to true, return the resulting Sqlobj, instead.
   * 
   * @param object|array $obj
   * @param boolean $debug = false
   * @return object|Sqlobj
   */
  protected final function insert( $obj, bool $debug = false)
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

  /** 
   * Updates registers on the database, with the data passed on $obj, filtered by the filters set on Dao::filters, then 
   * returns the number of affected rows of the operation. If $debug were set to true, return the resulting Sqlobj, instead.
   * 
   * @param object|array $obj
   * @param boolean $debug = false
   * @return integer|Sqlobj
   */
  protected final function update( $obj, bool $debug = false)
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

  /** 
   * Removes registers from the database. The removal is filtered by the filters set on Dao::filters, then 
   * returns the number of affected rows of the operation. If $debug were set to true, return the resulting Sqlobj, instead.
   * 
   * @param boolean $debug = false
   * @return integer|Sqlobj
   */
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

  /** 
   * Reads data from the database, executing the command passed on $sql, filtered by the filter set on Dao::filters. If no SQL 
   * is specified, assumes a default. Returns the SQL's resulting data. If $debug were set to true, return the resulting Sqlobj, instead.
   * 
   * @param string $sql = null
   * @param boolean $debug = false
   * @return array|Sqlobj
   */
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
      $this->filters = array_merge($parameterized->filters, $this->filters);
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

  /** 
   * Reads data from the database, executing the command passed on $sql, filtered by the filter set on Dao::filters. If no SQL 
   * is specified, assumes a default. Returns the first result from SQL's resulting data or null if results were empty. 
   * If $debug were set to true, returns the resulting Sqlobj, instead.
   * 
   * @param string $sql = null
   * @param boolean $debug = false
   * @return object|Sqlobj
   */
  protected final function first(string $sql = null, bool $debug = false)
  {
    $dbData = $this->find($sql, $debug);

    if ($debug) return $dbData;

    if (!empty($dbData)) return $dbData[0];
    else return null;
  }

  /** 
   * Reads data from the database, executing the command passed on $sql, filtered by the filter set on Dao::filters. If no SQL 
   * is specified, assumes a default. Executes the passed callback function, passing each result found as 
   * this callback's argument, then returns this altered results. If $debug were set to true, return the resulting Sqlobj, instead.
   * 
   * @param callable $callback
   * @param string $sql = null
   * @param boolean $debug = false
   * @return array|Sqlobj
   */
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

  /** 
   * Stores passed array of parameters into Dao::params. If Dao::params is not empty, when executing a database operation, 
   * it performs automatic parameterization using SqlParams class object.
   * 
   * @param array $params
   * @param string $tbPrefix = null
   * @return Dao
   */
  protected final function bindParams(array $params, string $tbPrefix = null)
  {
    $this->params = $params;
    $this->tablePrefix = $tbPrefix;

    return $this;
  }

  /** 
   * Add filter data to DAO filter and returns this class instance.
   * 
   * @param string $key
   * @param boolean $sanitize = true
   * @return Dao 
   */
  protected final function filter(string $key, bool $sanitize = true)
  {
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

  /** 
   * Add filter data to DAO filter, specifying logical operator to "AND", then returns this class instance.
   * 
   * @param string $key
   * @param boolean $sanitize = true
   * @return Dao 
   */
  protected final function and(string $key, bool $sanitize = true)
  {
    if (count($this->filters) == 0) {
      throw new Exception('You can only call this method after calling filter() first.');
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

  /** 
   * Add filter data to DAO filter, specifying logical operator to "OR", then returns this class instance.
   * 
   * @param string $key
   * @param boolean $sanitize = true
   * @return Dao 
   */
  protected final function or(string $key, bool $sanitize = true)
  {
    if (count($this->filters) == 0) {
      throw new Exception('You can only call this method after calling filter() first.');
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

  /** 
   * Edit the last added DAO filter data, specifying comparison operator to "=" and setting its value based on what it has received in $value.
   * Returns this class instance.
   * 
   * @param mixed $value
   * @return Dao 
   */
  protected final function equalsTo( $value)
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

  /** 
   * Edit the last added DAO filter data, specifying comparison operator to "!=" and setting its value based on what it has received in $value.
   * Returns this class instance.
   * 
   * @param mixed $value
   * @return Dao 
   */
  protected final function differentFrom( $value)
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

  /** 
   * Edit the last added DAO filter data, specifying comparison operator to ">" and setting its value based on what it has received in $value.
   * Returns this class instance.
   * 
   * @param mixed $value
   * @return Dao 
   */
  protected final function biggerThan( $value)
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

  /** 
   * Edit the last added DAO filter data, specifying comparison operator to "<" and setting its value based on what it has received in $value.
   * Returns this class instance.
   * 
   * @param mixed $value
   * @return Dao 
   */
  protected final function lesserThan( $value)
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

  /** 
   * Edit the last added DAO filter data, specifying comparison operator to ">=" and setting its value based on what it has received in $value.
   * Returns this class instance.
   * 
   * @param mixed $value
   * @return Dao 
   */
  protected final function biggerOrEqualsTo( $value)
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

  /** 
   * Edit the last added DAO filter data, specifying comparison operator to "<=" and setting its value based on what it has received in $value.
   * Returns this class instance.
   * 
   * @param mixed $value
   * @return Dao 
   */
  protected final function lesserOrEqualsTo( $value)
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

  /** 
   * Edit the last added DAO filter data, specifying comparison operator to "LIKE" and setting its value based on what it has received in $value.
   * Returns this class instance.
   * 
   * @param mixed $value
   * @return Dao 
   */
  protected final function likeOf( $value)
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

  /** 
   * Returns all filters set on Dao until the moment.
   * 
   * @return array 
   */
  protected final function getFilters()
  {
    return $this->filters;
  }

  /** 
   * Updates the current execution control, with the current state of the class instance.
   * 
   * @return void 
   */
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

  /** 
   * Registers a new execution control, with the current state of the class instance.
   * 
   * @return void 
   */
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

  /** 
   * Removes the current execution control from the pile and restores this class instance's state with the 
   * information stored in the previous execution control in line.
   * 
   * @return void 
   */
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
