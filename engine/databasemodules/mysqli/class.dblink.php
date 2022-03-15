<?php
/* //////////////////////
  MYSQLI DATABASE CLASS//
 */ //////////////////////

class Dblink
{

  // Information of current connection.
  private $cnnInfo;
  // Connection's link identifier.
  private $connection;
  // If true, deactivate automatic commit.
  private $transaction_mode;
  // An Exception object for connection errors.
  private $cnnerror;

  /* Verifies if database connection data is valid, then sets the properties with those values.
     * Connect to mysql server and save the connection in a property.
     */

  public function __construct(array $dbinfo)
  {
    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

    $this->cnnerror = 0;

    $this->cnnInfo = "No connection info.";
    $this->transaction_mode = false;

    $this->connection = $this->connect($dbinfo);
  }

  // When this class's object is destructed, close the connection to mysql server.
  public function __destruct()
  {
    $this->disconnect();
  }

  /* Tries to connect to mysql database much times as configured. If all attempts fails, 
  * Register an error, then returns false. Returns true on first success.
  */

  private function connect(array $dbinfo, int $currentTry = 1)
  {
    try {
      $connection = new mysqli($dbinfo['dbhost'], $dbinfo['dbuser'], $dbinfo['dbpass'], $dbinfo['dbname']);
    } catch (mysqli_sql_exception $ex) {
      $this->disconnect();
      if ($currentTry < DB_WORK_AROUND_FACTOR) {
        $connection = $this->connect($dbinfo, $currentTry + 1);
      } else {
        $this->cnnerror = $ex;
        System::errorLog('db_error', $ex);
      }
    }
    return $connection;
  }

  // Force the current connection to close.
  public function disconnect()
  {
    if (!is_null($this->connection))
      $this->connection->close();
  }

  /* Verifies if database connection data suplied is valid, sets the properties with those values, then
     * reconnect to mysql server with the new information.
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

  /* Triggers the sql query, save current connection information, then returns result data.
     * If it's a mysql resource, process it into an array of objects before returning.
     */

  public function runsql(Sqlobj $sqlobj, int $currentTry = 1)
  {
    try {
      $res = $this->connection->query($sqlobj->sqlstring);
    } catch (mysqli_sql_exception $ex) {
      if ($currentTry < DB_WORK_AROUND_FACTOR) {
        $res = $this->runsql($sqlobj, $currentTry + 1);
      } else {
        System::errorLog('db_error', $ex, ['sql' => $sqlobj->sqlstring]);
        throw $ex;
      }
    }

    if ($res === true || $res === false) {
      if (strpos(strtoupper($sqlobj->sqlstring), 'INSERT') !== false) {
        $this->lastresult = $this->connection->insert_id;
        $ret = $this->connection->insert_id;
      } else {
        $this->lastresult = $res;
        $ret = mysqli_affected_rows($this->connection);
      }
    } else {
      $ret = array();
      while ($row = $res->fetch_assoc()) {
        $ret[] = (object) $row;
      }

      $res->close();
    }

    $this->cnnInfo = (object) get_object_vars($this->connection);

    return $ret;
  }

  public function startTransaction()
  {
    if ($this->transaction_mode) {
      throw new Exception("There is already an active transaction. It must be finished before starting a new one.");
      return false;
    }

    $this->connection->autocommit(false);
    $this->transaction_mode = true;
    return true;
  }

  public function commitTransaction()
  {
    if ($this->transaction_mode) {
      $this->transaction_mode = false;

      $this->connection->commit();
    }
  }

  public function rollbackTransaction()
  {
    if ($this->transaction_mode) {
      $this->transaction_mode = false;

      $this->connection->rollBack();
    }
  }

  public function transaction($sqlset)
  {
    try {
      $result = [];
      $this->startTransaction();

      foreach ($sqlset as $sql) {
        $result[] = $this->runsql($sql);
      }

      if ($this->commitTransaction()) {
        return $result;
      } else throw new Exception("Something went wrong on the attempt to commit database transaction.");
    } catch (Exception $ex) {
      $this->rollbackTransaction();
      System::errorLog('db_error', $ex);
    }
  }

  // Escape data properly for mysql statements.
  public function escapevar($dataset)
  {
    if (is_null($dataset))
      return $dataset;

    if (is_array($dataset)) {
      foreach ($dataset as $key => $data) {
        if (is_null($data))
          continue;

        if (!is_numeric($data) && !is_array($data))
          $dataset[$key] = mysqli_real_escape_string($this->connection, $data);
        elseif (is_array($data)) {
          foreach ($data as $k => $d) {
            if (is_null($d))
              continue;

            if (!is_numeric($d))
              $dataset[$key][$k] = mysqli_real_escape_string($this->connection, $d);
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
          $dataset->$key = mysqli_real_escape_string($this->connection, $data);
        else {
          if (is_float($data))
            $dataset->$key = (float) $dataset->$key;
          elseif (is_int($data))
            $dataset->$key = (int) $dataset->$key;
        }
      }
    } elseif (!is_numeric($dataset)) {
      $dataset = mysqli_real_escape_string($this->connection, $dataset);
    } elseif (is_float($dataset)) {
      $dataset = (float) $dataset;
    } elseif (is_int($dataset)) {
      $dataset = (int) $dataset;
    }

    return $dataset;
  }
}
