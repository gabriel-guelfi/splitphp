<?php

class Dao
{
  // An instance of the class Dblink.
  private $dblink;
  // An instance of the class Sql.
  private $sql;
  // Holds table information
  private $workingTable;

  private $filters;

  // It sets the main table name, instantiate class Mysql and defines the table's primary key.
  public function __construct()
  {
    require_once INCLUDE_PATH . "/engine/databasemodules/class.dbmetadata.php";

    $dbconfig = [
      'dbhost' => DBHOST,
      'dbname' => DBNAME,
      'dbuser' => DBUSER,
      'dbpass' => DBPASS,
      'dbtype' => DBTYPE
    ];

    $this->dblink = System::loadClass(INCLUDE_PATH . "/engine/databasemodules/" . DBCLASS . "/class.dblink.php", 'dblink', [$dbconfig]);
    $this->sql = System::loadClass(INCLUDE_PATH . "/engine/databasemodules/" . DBCLASS . "/class.sql.php", 'sql', []);
    $this->filters = [];

    Dbmetadata::clearCache();
  }

  protected final function getTable(string $tableName)
  {
    $this->workingTable = $tableName;

    return $this;
  }

  protected final function insert($obj, bool $debug = false)
  {
    if (is_null($this->workingTable)) {
      throw new Exception('You cannot run this operation without defining a working table. Use $this->getTable() before calling this method.');
      return false;
    }

    $obj = (object) $obj;

    $sql = $this->sql->insert($obj, $this->workingTable);

    if ($debug)
      return $sql->output(true);

    $res = $this->dblink->runsql($sql->output(true));
    $key = Dbmetadata::tbInfo($this->workingTable)->key->keyname;
    $obj->$key = $res;

    $this->workingTable = null;
    $this->filter = [];

    return $obj;
  }

  protected final function update($obj, bool $debug = false)
  {
    if (is_null($this->workingTable)) {
      throw new Exception('You cannot run this operation without defining a working table. Use $this->getTable() before calling this method.');
      return false;
    }

    $obj = (object) $obj;

    $sql = $this->sql->update($obj, $this->workingTable);
    if (!empty($this->filters))
      $sql->where($this->filters);

    if ($debug)
      return $sql->output(true);

    $res = $this->dblink->runsql($sql->output(true));

    $this->workingTable = null;
    $this->filters = [];

    return $res;
  }

  protected final function delete(bool $debug = false)
  {
    if (is_null($this->workingTable)) {
      throw new Exception('You cannot run this operation without defining a working table. Use $this->getTable() before calling this method.');
      return false;
    }

    $sql = $this->sql->delete($this->workingTable);
    if (!empty($this->filters))
    $sql->where($this->filters);
    
    if ($debug)
      return $sql->output(true);

    $res = $this->dblink->runsql($sql->output(true));

    $this->workingTable = null;
    $this->filters = [];

    return $res;
  }

  protected final function find(string $sql, bool $debug = false)
  {
    // Check for defined entity:
    if (is_null($this->workingTable)) {
      throw new Exception('You cannot run this operation without defining a working table. Use $this->getTable() before calling this method.');
      return false;
    }

    // If argument is a SQL file path, include it, else treat argument as the SQL itself:
    $path = INCLUDE_PATH . "/application/sql/" . $sql . ".sql";
    if (is_file($path)) {
      $sql = file_get_contents($path);
    }

    // Check for forbidden write operations in this function:
    // if (
    //   preg_match('/\bupdate\b/i', $sql) !== 0 ||
    //   preg_match('/\bdelete\b/i', $sql) !== 0 ||
    //   preg_match('/\binsert\b/i', $sql) !== 0 ||
    //   preg_match('/\bdrop\b/i', $sql) !== 0 ||
    //   preg_match('/\bcreate\b/i', $sql) !== 0
    // ) {
    //   throw new Exception('You cannot perform write database operations with this method.');
    //   return false;
    // }

    // Sanitize Filter Data and replace values:
    for ($i = 0; $i < count($this->filters); $i++) {
      $f = &$this->filters[$i];

      if ($f->sanitize) {
        $f->value = $this->dblink->escapevar($f->value);

        if (!is_numeric($f->value) && is_string($f->value)) {
          $f->value = "'" . $f->value . "'";
        }
      }

      $sql = str_replace('?' . $f->key . '?', $f->value, $sql);
    }

    // Create SQL input object:
    $sqlObj = $this->sql->write($sql)->output(true);

    if ($debug)
      return $sqlObj;
    // Run SQL and store its result:
    $res = $this->dblink->runsql($sqlObj);

    $this->workingTable = null;
    $this->filters = [];

    return $res;
  }

  // protected final function fetch(string $sql, callable $callback)
  // {
  //   // Gets query result:
  //   $res = $this->find($sql);

  //   // Iterates over result, calling callback function for each iteration:
  //   foreach ($res as &$row) {
  //     call_user_func_array($callback, [&$row]);
  //   }
  // }

  protected final function filter($key, $sanitize = true)
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

  protected final function and($key, $sanitize = true)
  {
    if (count($this->filters) == 0) {
      throw new Exception('You can only call this method after calling "filter()" first.');
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
      throw new Exception('You can only call this method after calling "filter()" first.');
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
      throw new Exception('This method can only be called after "filter()", "and()" or "or()" methods.');
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
      throw new Exception('This method can only be called after "filter()", "and()" or "or()" methods.');
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
      throw new Exception('This method can only be called after "filter()", "and()" or "or()" methods.');
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
      throw new Exception('This method can only be called after "filter()", "and()" or "or()" methods.');
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
      throw new Exception('This method can only be called after "filter()", "and()" or "or()" methods.');
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
      throw new Exception('This method can only be called after "filter()", "and()" or "or()" methods.');
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
      throw new Exception('This method can only be called after "filter()", "and()" or "or()" methods.');
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
}
