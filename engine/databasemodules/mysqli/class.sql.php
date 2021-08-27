<?php

/* //////////////////////////////
  MYSQLI SQLOBJ PACKAGE CLASS////
 */ //////////////////////////////

class Sqlobj
{

  // SQL string, itself.
  public $sqlstring;
  // Current table name.
  public $table;

  public function __construct($str, $table)
  {
    $this->sqlstring = $str;
    $this->table = $table;
  }
}

/* ///////////////////////////////
  MYSQLI SQL QUERY BUILDER CLASS//
 */ ///////////////////////////////

class Sql
{

  // SQL string, itself.
  private $sqlstring;
  // Current table name.
  private $table;
  // An instance of the class Mysql.
  private $dblink;

  public function __construct()
  {
    $this->dblink = System::loadClass(INCLUDE_PATH . "/engine/databasemodules/mysqli/class.dblink.php", 'dblink');
    $this->sqlstring = "";
  }

  // Build a insert type query string with argument passed in dataset and return it.
  public function insert($dataset, $table)
  {
    $dataset = $this->dblink->escapevar($dataset);

    $fields = "";
    $values = " VALUES (";

    foreach ($dataset as $key => $val) {
      if (is_array($val)) {
        $fields = "";
        foreach ($val as $f => $v) {
          if ($f != Dbmetadata::tbInfo($table)->key->keyname) {
            if (!empty($v)) {
              $fields .= $this->escape($f) . ",";
              $values .= (is_numeric($v) ? $v : "'" . $v . "'") . ",";
            }
          }
        }
        $values = rtrim($values, ",") . "),(";
      } else {
        if ($key != Dbmetadata::tbInfo($table)->key->keyname) {
          $fields .= $this->escape($key) . ",";
          if(is_null($val)) $values .= "NULL,";
          else $values .= (is_numeric($val) ? $val : "'" . $val . "'") . ",";
        }
      }
    }
    $fields = rtrim($fields, ",") . ")";
    $values = rtrim($values, ",") . ")";
    $values = rtrim($values, "),(") . ")";

    $this->write("INSERT INTO " . $this->escape($table) . " (" . $fields . $values, null, $table);
    return $this;
  }

  // Build a update type query string with argument passed in dataset and return it.
  public function update($dataset, $table)
  {
    $dataset = $this->dblink->escapevar($dataset);

    $sql = "UPDATE " . $this->escape($table) . " SET ";
    foreach ($dataset as $key => $val) {
      if (!is_null($val) && $val !== false && $val !== "") {
        $sql .= $this->escape($key) . "=" . (is_numeric($val) ? $val : "'" . $val . "'") . ",";
      } elseif (is_null($val) || $val === "") {
        $sql .= $this->escape($key) . '=NULL,';
      }
    }
    $sql = rtrim($sql, ",");

    $this->write($sql, null, $table);
    return $this;
  }

  // Build a delete type query string with argument passed in dataset and return it.
  public function delete($table)
  {
    $this->write("DELETE " . $this->escape($table) . " FROM " . $this->escape($table), null, $table);
    return $this;
  }

  /* Build a Mysql where clause string based on conditions passed on params,
     * the join OR or AND and operator as = or LIKE, then return the string.
     */

  public function where(array $params)
  {
    $where = ' WHERE ';
    if (!empty($params)) {
      foreach ($params as $cond) {
        $key = $cond->key;
        $val = $cond->value;
        $join = $cond->joint;
        $operator = $cond->operator;

        if (!is_null($join))
          $where .= ' ' . $join . ' ';

        if (strtoupper($operator) == "LIKE") {
          $where .= $key . ' LIKE "%' . $this->dblink->escapevar($val) . '%"';
        } else if (is_array($val) && !empty($val)) {
          $joined_values = array();

          foreach ($val as $in_val) {
            $joined_values[] = is_numeric($in_val) ? $in_val : '"' . $in_val . '"';
          }
          $joined_values = $this->dblink->escapevar($joined_values);

          $where .= $key . ' IN (' . join(',', $joined_values) . ')';
        } else {
          $where .= $key . $operator . (is_numeric($val) ? $val : "'" . $this->dblink->escapevar($val) . "'");
        }
      }
      $this->write($where, null, null, false);
    }

    return $this;
  }

  // Register SQL query data, then return the object.
  public function write($sqlstr, $values = null, $table = null, $overwrite = true)
  {
    unset($values);
    if ($overwrite) {
      $this->sqlstring = $sqlstr;
      $this->table = $table;
    } else {
      $this->sqlstring .= $sqlstr;
    }

    return $this;
  }

  private function escape($val)
  {
    return $val == "*" ? $val : "`" . $val . "`";
  }

  public function output($clear = false)
  {
    $obj = new Sqlobj($this->sqlstring, $this->table);

    if ($clear)
      $this->reset();

    return $obj;
  }

  public function reset()
  {
    $this->sqlstring = "";
    $this->table = null;
    return $this;
  }
}
