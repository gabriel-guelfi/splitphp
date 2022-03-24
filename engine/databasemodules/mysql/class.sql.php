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
    $this->dblink = System::loadClass(INCLUDE_PATH . "/engine/databasemodules/mysql/class.dblink.php", 'dblink');
    $this->sqlstring = "";
  }

  // Build a insert type query string with argument passed in dataset and return it.
  public function insert($dataset, $table)
  {
    $dataset = $this->dblink->getConnection('writer')->escapevar($dataset);

    $fields = "";
    $values = " VALUES (";

    foreach ($dataset as $key => $val) {
      if (is_array($val)) {
        $fields = "";
        foreach ($val as $f => $v) {
          if ($f != Dbmetadata::tbPrimaryKey($table)) {
            if (!empty($v)) {
              $fields .= $this->escape($f) . ",";
              $values .= (is_numeric($v) ? $v : "'" . $v . "'") . ",";
            }
          }
        }
        $values = rtrim($values, ",") . "),(";
      } else {
        if ($key != Dbmetadata::tbPrimaryKey($table)) {
          $fields .= $this->escape($key) . ",";
          if (is_null($val)) $values .= "NULL,";
          else $values .= (is_numeric($val) ? $val : "'" . $val . "'") . ",";
        }
      }
    }
    $fields = rtrim($fields, ",") . ")";
    $values = rtrim($values, ",") . ")";
    $values = rtrim($values, "),(") . ")";

    $this->write("INSERT INTO " . $this->escape($table) . " (" . $fields . $values, $table);
    return $this;
  }

  // Build a update type query string with argument passed in dataset and return it.
  public function update($dataset, $table)
  {
    $dataset = $this->dblink->getConnection('writer')->escapevar($dataset);

    $sql = "UPDATE " . $this->escape($table) . " SET ";
    foreach ($dataset as $key => $val) {
      if (!is_null($val) && $val !== false && $val !== "") {
        $sql .= $this->escape($key) . "=" . (is_numeric($val) ? $val : "'" . $val . "'") . ",";
      } elseif (is_null($val) || $val === "") {
        $sql .= $this->escape($key) . '=NULL,';
      }
    }
    $sql = rtrim($sql, ",");

    $this->write($sql, $table);
    return $this;
  }

  // Build a delete type query string with argument passed in dataset and return it.
  public function delete($table)
  {
    $this->write("DELETE " . $this->escape($table) . " FROM " . $this->escape($table), $table);
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
          $where .= $key . ' LIKE "%' . $this->dblink->getConnection('writer')->escapevar($val) . '%"';
        } else if (is_array($val) && !empty($val)) {
          $joined_values = array();

          foreach ($val as $in_val) {
            $joined_values[] = is_numeric($in_val) ? $in_val : '"' . $in_val . '"';
          }
          $joined_values = $this->dblink->getConnection('writer')->escapevar($joined_values);

          $where .= $key . ' IN (' . join(',', $joined_values) . ')';
        } else {
          $where .= $key . ' ' . $operator . ' ' . (is_numeric($val) ? $val : "'" . $this->dblink->getConnection('writer')->escapevar($val) . "'");
        }
      }
      $this->write($where, null, false);
    }

    return $this;
  }

  // Register SQL query data, then return the object.
  public function write($sqlstr, $table = null, $overwrite = true)
  {
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
