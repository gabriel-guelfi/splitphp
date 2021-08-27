<?php

/* //////////////////////
  PDO DATABASE CLASS//
 */ //////////////////////

class Dblink
{

  // Information of current connection.
  private $cnnInfo;
  // Connection's link identifier. If connection fails, a PDOExcetion object, containing error info.
  private $connection;
  // Data types constants.
  private $datatypes;
  // If true, deactivate automatic commit.
  private $transaction_mode;
  // A PDOException object for connections failures
  private $cnnerror;
  // An array of PDOException objects for query errors
  private $queryerrors;

  /* Verifies if database connection data is valid, then sets the properties with those values.
     * Connect to database server and save the connection in a property.
     */

  public function __construct(array $dbinfo)
  {
    $this->cnnerror = 0;
    $this->queryerrors = [];
    $this->datatypes = array(
      'boolean' => PDO::PARAM_BOOL,
      'integer' => PDO::PARAM_INT,
      'double' => PDO::PARAM_STR,
      'string' => PDO::PARAM_STR,
      'resource' => PDO::PARAM_LOB
    );

    $this->cnnInfo = "No connection info.";
    $this->transaction_mode = false;

    $this->connection = $this->connect($dbinfo);
  }

  // When this class's object is destructed, close the connection to database server.
  public function __destruct()
  {
    $this->disconnect();
  }

  /* Tries to connect to database server much times as configured. If all attempts fails, 
     * write an error to property and returns false. Returns true on first success.
     */

  private function connect(array $dbinfo, int $currentTry = 1)
  {
    try {
      $connection = new PDO($dbinfo['dbtype'] . ':host=' . $dbinfo['dbhost'] . ';dbname=' . $dbinfo['dbname'], $dbinfo['dbuser'], $dbinfo['dbpass']);
      $connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    } catch (PDOException $ex) {
      $this->disconnect();
      if ($currentTry < DB_WORK_AROUND_FACTOR) {
        $connection = $this->connect($dbinfo, $currentTry + 1);
      } else {
        $this->cnnerror = $ex;
        System::log('db_error', $ex->getMessage());
      }
    }
    return $connection;
  }

  // Force the current connection to close.
  public function disconnect()
  {
    $this->connection = null;
  }

  /* Verifies if database connection data suplied is valid, sets the properties with those values, then
     * reconnect to database server with the new information.
     */

  public function reconnect(array $dbinfo)
  {
    $this->disconnect();

    $this->connection = $this->connect($dbinfo);
  }

  // Returns all current connection information.
  public function info()
  {
    return $this->cnnInfo;
  }

  private function prepare_statement(string $sqlstring, array $sqlvalues)
  {
    $stmt = $this->connection->prepare($sqlstring);

    if (!empty($sqlvalues)) {
      foreach ($sqlvalues as $key => $val) {
        $stmt->bindValue(($key + 1), $val, $this->datatypes[gettype($val)]);
      }
    }

    return $stmt;
  }

  public function runsql(Sqlobj $sqlobj, int $currentTry = 1)
  {
    try {
      $stmt = $this->prepare_statement($sqlobj->sqlstring, array_values($sqlobj->sqlvalues));
      $res = $stmt->execute();
    } catch (PDOException $ex) {
      if ($currentTry < DB_WORK_AROUND_FACTOR) {
        $res = $this->runsql($sqlobj, $currentTry + 1);
      } else {
        $this->queryerrors[] = $ex;

        if ($this->transaction_mode) {
          $this->endTransaction();
        } else {
          System::log('db_error', $ex->getMessage());
        }
      }
    }

    if ($stmt->columnCount() > 0) {
      $res = array();
      while ($row = $stmt->fetch(PDO::FETCH_OBJ)) {
        $res[] = $row;
      }
    } elseif (strpos(strtoupper($sqlobj->sqlstring), 'INSERT') !== false) {
      $res = $this->connection->lastInsertId();
    }

    $this->cnnInfo = (object) get_object_vars($this->connection);

    return $res;
  }

  public function startTransaction()
  {
    if ($this->transaction_mode) {
      throw new Exception("There is already an active transaction. It must be finished before starting a new one.");
      return false;
    }

    $this->connection->beginTransaction();
    $this->transaction_mode = true;
    return true;
  }

  public function endTransaction()
  {
    $r = false;

    if ($this->transaction_mode) {
      $this->transaction_mode = false;

      if (!empty($this->queryerrors)) {
        foreach ($this->queryerrors as $ex) {
          System::log('db_error', $ex->getMessage());
        }

        $this->connection->rollBack();

        throw new Exception('There were some errors processing current transaction. You will find more info on the file located at: "/application/log/db_error.log".');
      } else {
        $this->connection->commit();
        $r = true;
      }
    }

    return $r;
  }

  public function transaction($sqlset)
  {
    try {
      $this->startTransaction();

      foreach ($sqlset as $sql) {
        $this->runsql($sql);
      }

      return $this->endTransaction();
    } catch (Exception $ex) {
      System::log('db_error', $ex->getMessage());
    }
  }
}
