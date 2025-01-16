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
// Copyright (c) 2025 Lightertools Open Source Community                                                                                                               //
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
use \DateTime;

/**
 * Class Dbcnn
 * 
 * This class is responsible to establish and manage connections to the database.
 *
 * @package engine/databasemodules/mysql
 */
class DbCnn
{
  /**
   * @var object $cnn
   * Stores database connection's resource object.
   */
  private $cnn;

  /**
   * @var array $cnnInfo
   * Stores database connection's information.
   */
  private $cnnInfo;

  /**
   * @var boolean $transactionMode
   * Holds a boolean value used as a control to whether the current database operation is transactional or not (autocommit false or true).
   */
  private $transactionMode;

  /**
   * @var string $host
   * Stores the current database connection's host.
   */
  private $host;

  /**
   * @var mixed $port
   * Stores the current database connection's port.
   */
  private $port;

  /**
   * @var string $name
   * Stores the current database connection's database name.
   */
  private $name;

  /**
   * @var string $user
   * Stores the current database connection's database user.
   */
  private $user;

  /**
   * @var string $pass
   * Stores the current database connection's database password.
   */
  private $pass;

  /** 
   * Set MySQL error report on, set connection's database credentials, set connection's info and controls, then returns an instance of the class (contructor).
   * 
   * @return DbCnn 
   */
  public final function __construct(string $host, $port, string $name, string $user, string $pass)
  {
    // Set MySQL error report on:
    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

    // Set connection's database credentials:
    $this->host = $host;
    $this->port = $port;
    $this->name = $name;
    $this->user = $user;
    $this->pass = $pass;

    // Set connection's info and controls:
    $this->transactionMode = false;

    $this->connect();
  }

  /** 
   * Returns a string representation of this class for printing purposes.
   * 
   * @return string 
   */
  public final function __toString()
  {
    $dbType = DBTYPE;
    $dbHost = $this->host;
    $dbPort = $this->port;
    $dbName = $this->name;

    return "class:DbCnn(type:{$dbType}, Host:{$dbHost}, Port:{$dbPort}, database:{$dbName}, User:{$this->user}, Password:{$this->pass})";
  }

  /** 
   * Disconnects database connection.
   * When the instance of the class is destroyed, PHP runs this method automatically.
   * 
   * @return void 
   */
  public final function __destruct()
  {
    $this->disconnect();
  }

  /** 
   * Disconnects the connection established. 
   * 
   * @return void 
   */
  public function disconnect()
  {
    if (!empty($this->cnn)) $this->cnn->close();
    $this->cnn = null;
    $this->cnnInfo = null;
  }

  /** 
   * Returns an object, which contains the current connection's information. (Stored at Dbcnn::cnnInfo property).
   * 
   * @return object 
   */
  public function info()
  {
    if (empty($this->cnn)) return "No connection info.";
    return $this->cnnInfo;
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
    try {
      $res = $this->cnn->query($sqlobj->sqlstring);
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
        $ret = $this->cnn->insert_id;
      } else {
        $ret = mysqli_affected_rows($this->cnn);
      }
    } else {
      $ret = array();
      while ($row = $res->fetch_assoc()) {
        $ret[] = (object) $row;
      }

      $res->close();
    }

    $this->cnnInfo = (object) get_object_vars($this->cnn);
    return $ret;
  }

  /** 
   * Changes DbCnn::transactionMode to true, set current connection's autocommit to false and updates connection's information.
   * 
   * @return void 
   */
  public function startTransaction()
  {
    if ($this->transactionMode) {
      throw new Exception("There is already an active transaction. It must be finished before starting a new one.");
    }

    if (!empty($this->cnn)) {
      $this->cnn->autocommit(false);
      $this->cnnInfo = (object) get_object_vars($this->cnn);
      $this->transactionMode = true;
    }
  }

  /** 
   * Changes DbCnn::transactionMode to false, commits the previously opened transaction, 
   * containing the database operations, and updates connection's information.
   * 
   * @return void 
   */
  public function commitTransaction()
  {
    if ($this->transactionMode) {
      $this->transactionMode = false;

      if (!empty($this->cnn))
        $this->cnn->commit();
    }

    if (!empty($this->cnn))
      $this->cnnInfo = (object) get_object_vars($this->cnn);
  }

  /** 
   * Changes DbCnn::transactionMode to false, rolls back the previously opened transaction, 
   * cancelling all the database operations contained, and updates connection's information.
   * 
   * @return void 
   */
  public function rollbackTransaction()
  {
    if ($this->transactionMode) {
      $this->transactionMode = false;

      if (!empty($this->cnn))
        $this->cnn->rollBack();
    }

    if (!empty($this->cnn))
      $this->cnnInfo = (object) get_object_vars($this->cnn);
  }

  /** 
   * Escapes and sanitizes, properly for mysql statements, the passed data.
   * 
   * @param mixed $dataset
   * @return mixed 
   */
  public function escapevar(&$dataset)
  {
    if (is_null($dataset)) return null;

    elseif (is_array($dataset) || gettype($dataset) === "object") {
      foreach ($dataset as &$data) {
        $this->escapevar($data);
      }
    } elseif (is_string($dataset) && !is_numeric($dataset)) $dataset = mysqli_real_escape_string($this->cnn, $dataset);

    elseif (is_float($dataset)) $dataset = (float) $dataset;

    elseif (is_int($dataset)) $dataset = (int) $dataset;

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
    try {
      $this->cnn = new mysqli($this->host, $this->user, $this->pass, $this->name, $this->port);

      //Setup database's settings per connection:
      mysqli_set_charset($this->cnn, DB_CHARSET);
      $this->syncMysqlTimezone($this->cnn);
    } catch (mysqli_sql_exception $ex) {
      if ($currentTry < DB_WORK_AROUND_FACTOR) {
        sleep(1);
        $this->cnn = $this->connect($currentTry + 1);
      } else {
        $sqlState = "Only for PHP 8 or >";
        if (preg_match('/8\..*/', phpversion())) $sqlState = $ex->getSqlState();

        throw new DatabaseException($ex, $sqlState);
      }
    }
    return $this->cnn;
  }

  private function syncMysqlTimezone($cnn)
  {
    $now = new DateTime();
    $mins = $now->getOffset() / 60;
    $sgn = ($mins < 0 ? -1 : 1);
    $mins = abs($mins);
    $hrs = floor($mins / 60);
    $mins -= $hrs * 60;
    $offset = sprintf('%+d:%02d', $hrs * $sgn, $mins);

    $cnn->query("SET time_zone='{$offset}';");
  }
}
