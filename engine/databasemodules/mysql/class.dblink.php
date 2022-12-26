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
namespace engine\databasemodules\mysql;

use Exception;
use \engine\exceptions\DatabaseException;
use \mysqli;
use \mysqli_sql_exception;

/**
 * Class Dblink
 * 
 * This class is responsible to establish and manage connections to the database.
 *
 * @package engine/databasemodules/mysql
 */
class Dblink
{

  /**
   * @var array $cnnInfo
   * Stores database connection's information.
   */
  private $cnnInfo;

  /**
   * @var array $connections
   * Stores the database connections.
   */
  private $connections;

  /**
   * @var boolean $transactionMode
   * Holds a boolean value used as a control to whether the current database operation is transactional or not (autocommit false or true).
   */
  private $transactionMode;

  /**
   * @var string $currentConnectionName
   * Stores the current connection identifier. Dblink uses it to know which connection it shall use to perform the current operation.
   */
  private $currentConnectionName;

  /**
   * @var boolean $isGetConnectionInvoked
   * Holds a boolean value used as a control to oblige the user to call Dblink::getConnection() method before perform any database operation.
   */
  private $isGetConnectionInvoked;

  /**
   * @var string $dbUserName
   * Stores the current database connection's username.
   */
  private $dbUserName;

  /**
   * @var string $dbUserPass
   * Stores the current database connection's password.
   */
  private $dbUserPass;

  /** 
   * Changes MySQL report configs, starts the properties with their initial values, then returns an object of type Dblink(instantiate the class).
   * 
   * @return Dblink 
   */
  public final function __construct()
  {
    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

    $this->currentConnectionName = null;
    $this->connections = [];
    $this->cnnInfo = [];
    $this->transactionMode = false;
    $this->isGetConnectionInvoked = false;
  }

  /** 
   * Returns a string representation of this class for printing purposes.
   * 
   * @return string 
   */
  public final function __toString()
  {
    $dbType = DBTYPE;
    $dbHost = DBHOST;
    $dbPort = DBPORT;
    $dbName = DBNAME;

    return "class:Dblink(type:{$dbType}, Host:{$dbHost}, Port:{$dbPort}, database:{$dbName}, Connection:{$this->currentConnectionName}, User:{$this->dbUserName}, Password:{$this->dbUserPass})";
  }

  /** 
   * Disconnects all currently open database connections.
   * When the instance of the class is destroyed, PHP runs this method automatically.
   * 
   * @return void 
   */
  public final function __destruct()
  {
    foreach ($this->connections as $cnnName => $cnn) {
      $this->disconnect($cnnName);
    }
  }

  /** 
   * Set a connection, based on the value passed in $connectionName parameter, as the current connection. 
   * If there is no such connection established and $attemptConnection flag is set to true, create a new one.
   * 
   * @param string $connectionName
   * @param boolean $attemptConnection = true
   * @return Dblink 
   */
  public function getConnection(string $connectionName, bool $attemptConnection = true)
  {
    if (DB_CONNECT != 'on') throw new Exception("Database connections are turned off. Turn it on in config.ini file.");

    if ($connectionName != 'reader' && $connectionName != 'writer')
      throw new Exception("Invalid Database connection mode.");

    $this->isGetConnectionInvoked = true;
    $this->currentConnectionName = $connectionName;

    if ($attemptConnection && (!array_key_exists($connectionName, $this->connections) || empty($this->connections[$this->currentConnectionName])))
      $this->connections[$this->currentConnectionName] = $this->connect();

    if (!empty($this->connections[$this->currentConnectionName]))
      $this->cnnInfo[$this->currentConnectionName] = (object) get_object_vars($this->connections[$this->currentConnectionName]);

    return $this;
  }

  /** 
   * Tries to disconnect the connection defined by the value passed on parameter $connectionName. 
   * Then reset Dblink instance's state to its default.
   * 
   * @param string $connectionName
   * @return void 
   */
  public function disconnect($connectionName)
  {
    if (array_key_exists($connectionName, $this->connections)) {
      if (!empty($this->connections[$connectionName])) {
        $this->connections[$connectionName]->close();
        unset($this->connections[$connectionName]);
      }
    }

    unset($this->cnnInfo[$this->currentConnectionName]);
    $this->currentConnectionName = null;
    $this->isGetConnectionInvoked = false;
  }

  /** 
   * Returns an object, which contains the current connection's information. (Stored at Dblink::cnnInfo property).
   * 
   * @return object 
   */
  public function info()
  {
    if (!$this->isGetConnectionInvoked) throw new Exception("You must invoke getConnection() before perform this operation");

    if (empty($this->cnnInfo[$this->currentConnectionName])) return "No connection info.";
    $this->isGetConnectionInvoked = false;
    return $this->cnnInfo[$this->currentConnectionName];
  }

  /** 
   * Run a SQL query, updates connection information, then returns the query's results.
   * In case of SELECT type queries returns an array containing the results of the query.
   * In case of INSERT type queries returns the primary key (id) of the newly created register.
   * In case of DELETE or UPDATE type queries returns the number of the affected rows.
   * 
   * @param SqlObj $sqlObj
   * @param integer $currentTry = 1
   * 
   * @return mixed 
   */
  public function runsql(Sqlobj $sqlobj, int $currentTry = 1)
  {
    if (!$this->isGetConnectionInvoked) throw new Exception("You must invoke getConnection() before perform this operation");

    try {
      $res = $this->connections[$this->currentConnectionName]->query($sqlobj->sqlstring);
    } catch (mysqli_sql_exception $ex) {
      if ($currentTry < DB_WORK_AROUND_FACTOR) {
        sleep(1);
        $res = $this->runsql($sqlobj, $currentTry + 1);
        return;
      } else {
        $sqlState = "Only for PHP 8 or >";
        if (preg_match('/8\..*/', phpversion())) $sqlState = $ex->getSqlState();
        throw new DatabaseException($ex, $sqlState, $sqlobj->sqlstring);
      }
    }

    if ($res === true || $res === false) {
      if (strpos(strtoupper($sqlobj->sqlstring), 'INSERT') !== false) {
        $this->lastresult = $this->connections[$this->currentConnectionName]->insert_id;
        $ret = $this->connections[$this->currentConnectionName]->insert_id;
      } else {
        $this->lastresult = $res;
        $ret = mysqli_affected_rows($this->connections[$this->currentConnectionName]);
      }
    } else {
      $ret = array();
      while ($row = $res->fetch_assoc()) {
        $ret[] = (object) $row;
      }

      $res->close();
    }

    $this->cnnInfo[$this->currentConnectionName] = (object) get_object_vars($this->connections[$this->currentConnectionName]);
    $this->isGetConnectionInvoked = false;

    return $ret;
  }

  /** 
   * Check if the connection specified on $connectionName exists.
   * 
   * @param string $connectionName
   * @return boolean 
   */
  public function checkConnection($connectionName)
  {
    return empty($this->connections[$connectionName]);
  }

  /** 
   * Changes Dblink::transactionMode to true, set current connection's autocommit to false and updates connection's information.
   * 
   * @return void 
   */
  public function startTransaction()
  {
    if (!$this->isGetConnectionInvoked) throw new Exception("You must invoke getConnection() before perform this operation");

    if ($this->transactionMode) {
      throw new Exception("There is already an active transaction. It must be finished before starting a new one.");
    }

    if (!empty($this->connections[$this->currentConnectionName])) {
      $this->connections[$this->currentConnectionName]->autocommit(false);
      $this->cnnInfo[$this->currentConnectionName] = (object) get_object_vars($this->connections[$this->currentConnectionName]);
      $this->transactionMode = true;
    }

    $this->isGetConnectionInvoked = false;
  }

  /** 
   * Changes Dblink::transactionMode to false, commits the previously opened transaction, 
   * containing the database operations, and updates connection's information.
   * 
   * @return void 
   */
  public function commitTransaction()
  {
    if (!$this->isGetConnectionInvoked) throw new Exception("You must invoke getConnection() before perform this operation");

    if ($this->transactionMode) {
      $this->transactionMode = false;

      if (!empty($this->connections[$this->currentConnectionName]))
        $this->connections[$this->currentConnectionName]->commit();
    }

    if (!empty($this->connections[$this->currentConnectionName]))
      $this->cnnInfo[$this->currentConnectionName] = (object) get_object_vars($this->connections[$this->currentConnectionName]);
    $this->isGetConnectionInvoked = false;
  }

  /** 
   * Changes Dblink::transactionMode to false, rolls back the previously opened transaction, 
   * cancelling all the database operations contained, and updates connection's information.
   * 
   * @return void 
   */
  public function rollbackTransaction()
  {
    if (!$this->isGetConnectionInvoked) throw new Exception("You must invoke getConnection() before perform this operation");

    if ($this->transactionMode) {
      $this->transactionMode = false;

      if (!empty($this->connections[$this->currentConnectionName]))
        $this->connections[$this->currentConnectionName]->rollBack();
    }

    if (!empty($this->connections[$this->currentConnectionName]))
      $this->cnnInfo[$this->currentConnectionName] = (object) get_object_vars($this->connections[$this->currentConnectionName]);
    $this->isGetConnectionInvoked = false;
  }

  /** 
   * Escapes and sanitizes, properly for mysql statements, the passed data.
   * 
   * @param mixed $dataset
   * @return mixed 
   */
  public function escapevar($dataset)
  {
    if (!$this->isGetConnectionInvoked) throw new Exception("You must invoke getConnection() before perform this operation");

    if (is_null($dataset))
      return $dataset;

    if (is_array($dataset)) {
      foreach ($dataset as $key => $data) {
        if (is_null($data))
          continue;

        if (!is_numeric($data) && !is_array($data))
          $dataset[$key] = mysqli_real_escape_string($this->connections[$this->currentConnectionName], $data);
        elseif (is_array($data)) {
          foreach ($data as $k => $d) {
            if (is_null($d))
              continue;

            if (!is_numeric($d))
              $dataset[$key][$k] = mysqli_real_escape_string($this->connections[$this->currentConnectionName], $d);
            else {
              if (is_float($d))
                $dataset[$key][$k] = (float) $dataset[$key][$k];
              elseif (is_int($d))
                $dataset[$key][$k] = (int) $dataset[$key][$k];
            }
          }
        }
      }
    } elseif (gettype($dataset) === "object") {
      foreach ($dataset as $key => $data) {
        if (is_null($data))
          continue;

        if (!is_numeric($data))
          $dataset->$key = mysqli_real_escape_string($this->connections[$this->currentConnectionName], $data);
        else {
          if (is_float($data))
            $dataset->$key = (float) $dataset->$key;
          elseif (is_int($data))
            $dataset->$key = (int) $dataset->$key;
        }
      }
    } elseif (!is_numeric($dataset)) {
      $dataset = mysqli_real_escape_string($this->connections[$this->currentConnectionName], $dataset);
    } elseif (is_float($dataset)) {
      $dataset = (float) $dataset;
    } elseif (is_int($dataset)) {
      $dataset = (int) $dataset;
    }
    $this->isGetConnectionInvoked = false;
    return $dataset;
  }

  /**
   * Tries to connect to mysql database much times as configured. If all attempts fail, 
   * throws an exception. On first success returns the connection object.
   * 
   * @param integer $currentTry = 1
   * @return Mysqli
   */
  private function connect(int $currentTry = 1)
  {
    if ($this->currentConnectionName == 'writer') {
      $this->dbUsername = DBUSER_MAIN;
      $this->dbUserpass = DBPASS_MAIN;
    } elseif ($this->currentConnectionName == 'reader') {
      $this->dbUsername = DBUSER_READONLY;
      $this->dbUserpass = DBPASS_READONLY;
    } else {
      throw new Exception("Invalid Database connection mode.");
    }

    try {
      $connection = new mysqli(DBHOST, $this->dbUsername, $this->dbUserpass, DBNAME);
      mysqli_set_charset($connection, DB_CHARSET);
    } catch (mysqli_sql_exception $ex) {
      if ($currentTry < DB_WORK_AROUND_FACTOR) {
        sleep(1);
        $connection = $this->connect($currentTry + 1);
      } else {
        $sqlState = "Only for PHP 8 or >";
        if (preg_match('/8\..*/', phpversion())) $sqlState = $ex->getSqlState();

        throw new DatabaseException($ex, $sqlState);
      }
    }
    return $connection;
  }
}
