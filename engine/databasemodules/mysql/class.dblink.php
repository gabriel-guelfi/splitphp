<?php
/* //////////////////////
  MYSQLI DATABASE CLASS//
 */ //////////////////////

class Dblink
{

  // Information of current connection.
  private $cnnInfo;
  // Connection's link identifier.
  private $connections;
  // If true, deactivate automatic commit.
  private $transaction_mode;
  private $currentConnectionName;
  private $isGetConnectionInvoked;

  /**
   *  Verifies if database connection data is valid, then sets the properties with those values.
   * Connect to mysql server and save the connection in a property.
   */

  public function __construct()
  {
    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

    $this->currentConnectionName = null;
    $this->connections = [];
    $this->cnnInfo = [];
    $this->transaction_mode = false;
    $this->isGetConnectionInvoked = false;
  }

  // When this class's object is destructed, close the connection to mysql server.
  public function __destruct()
  {
    $this->disconnect($this->currentConnectionName);
  }

  public function getConnection(string $connectionName)
  {
    if ($connectionName != 'reader' && $connectionName != 'writer')
      throw new Exception("Invalid Database connection mode.");

    $this->isGetConnectionInvoked = true;
    $this->currentConnectionName = $connectionName;

    if (!array_key_exists($connectionName, $this->connections) || empty($this->connections[$this->currentConnectionName]))
      $this->connections[$this->currentConnectionName] = $this->connect();

    $this->cnnInfo[$this->currentConnectionName] = (object) get_object_vars($this->connections[$this->currentConnectionName]);

    return $this;
  }

  // Force the current connection to close.
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

  // Returns all current connection information.
  public function info()
  {
    if(!$this->isGetConnectionInvoked) throw new Exception("You must invoke getConnection() before perform this operation");

    if (empty($this->cnnInfo[$this->currentConnectionName])) return "No connection info.";
    $this->isGetConnectionInvoked = false;
    return $this->cnnInfo[$this->currentConnectionName];
  }

  /* Triggers the sql query, save current connection information, then returns result data.
   * If it's a mysql resource, process it into an array of objects before returning.
  */

  public function runsql(Sqlobj $sqlobj, int $currentTry = 1)
  {
    if(!$this->isGetConnectionInvoked) throw new Exception("You must invoke getConnection() before perform this operation");

    try {
      $res = $this->connections[$this->currentConnectionName]->query($sqlobj->sqlstring);
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

  public function startTransaction()
  {
    if(!$this->isGetConnectionInvoked) throw new Exception("You must invoke getConnection() before perform this operation");

    if ($this->transaction_mode) {
      throw new Exception("There is already an active transaction. It must be finished before starting a new one.");
      return false;
    }

    $this->connections[$this->currentConnectionName]->autocommit(false);
    $this->transaction_mode = true;
    $this->cnnInfo[$this->currentConnectionName] = (object) get_object_vars($this->connections[$this->currentConnectionName]);
    $this->isGetConnectionInvoked = false;
    return true;
  }

  public function commitTransaction()
  {
    if(!$this->isGetConnectionInvoked) throw new Exception("You must invoke getConnection() before perform this operation");

    if ($this->transaction_mode) {
      $this->transaction_mode = false;

      $this->connections[$this->currentConnectionName]->commit();
    }

    $this->cnnInfo[$this->currentConnectionName] = (object) get_object_vars($this->connections[$this->currentConnectionName]);
    $this->isGetConnectionInvoked = false;
  }

  public function rollbackTransaction()
  {
    if(!$this->isGetConnectionInvoked) throw new Exception("You must invoke getConnection() before perform this operation");

    if ($this->transaction_mode) {
      $this->transaction_mode = false;

      $this->connections[$this->currentConnectionName]->rollBack();
    }

    $this->cnnInfo[$this->currentConnectionName] = (object) get_object_vars($this->connections[$this->currentConnectionName]);
    $this->isGetConnectionInvoked = false;
  }

  // Escape data properly for mysql statements.
  public function escapevar($dataset)
  {
    if(!$this->isGetConnectionInvoked) throw new Exception("You must invoke getConnection() before perform this operation");

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

  /* Tries to connect to mysql database much times as configured. If all attempts fails, 
  * Register an error, then returns false. Returns true on first success.
  */

  private function connect(int $currentTry = 1)
  {
    if ($this->currentConnectionName == 'writer') {
      $dbUsername = DBUSER_WRITER;
      $dbUserpass = DBPASS_WRITER;
    } elseif ($this->currentConnectionName == 'reader') {
      $dbUsername = DBUSER_READER;
      $dbUserpass = DBPASS_READER;
    } else {
      throw new Exception("Invalid Database connection mode.");
    }

    try {
      $connection = new mysqli(DBHOST, $dbUsername, $dbUserpass, DBNAME);
    } catch (mysqli_sql_exception $ex) {
      if ($currentTry < DB_WORK_AROUND_FACTOR) {
        $connection = $this->connect($currentTry + 1);
      } else {
        System::errorLog('db_error', $ex);
      }
      $this->disconnect($this->currentConnectionName);
    }
    return $connection;
  }
}
