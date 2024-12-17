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

use engine\System;

/**
 * Class SqlObj
 * 
 * This class is meant to be an input object to perform SQL queries.
 *
 * @package engine/databasemodules/mysql
 */
class Sqlobj
{

  /**
   * @var string $sqlstring
   * A string containing the SQL query, itself.
   */
  public $sqlstring;

  /**
   * @var string $table
   * The name of the main table where the query will be executed.
   */
  public $table;

  /** 
   * set the properties sqlstring and table, then returns an object of type Sqlobj(instantiate the class).
   * 
   * @return Sqlobj 
   */
  public final function __construct($str, $table)
  {
    $this->sqlstring = $str;
    $this->table = $table;
  }

  /** 
   * Returns a string representation of this class for printing purposes.
   * 
   * @return string 
   */
  public final function __toString()
  {
    return "class:Sqlobj(SqlString:{$this->sqlstring}, Table:{$this->table})";
  }
}

/**
 * Class Sql
 * 
 * This is a SQL builder class, responsible for building and managing the SQL query commands. 
 *
 * @package engine/databasemodules/mysql
 */
class Sql
{

  /**
   * @var string $sqlstring
   * A string containing the SQL query, itself.
   */
  private $sqlstring;

  /**
   * @var string $table
   * The name of the main table where the query will be executed.
   */
  private $table;

  /**
   * @var Dblink $dblink
   * Holds the instance of the Dblink connection class.
   */
  private $dblink;

  /** 
   * Instantiate Dblink class, storing it on Sql::dblink property, set Sql::sqlstring property to an empty string, then returns an object
   * of type Sql(instantiate the class).
   * 
   * @return Sql 
   */
  public final function __construct()
  {
    if (DB_CONNECT == 'on')
      $this->dblink = System::loadClass(ROOT_PATH . "/engine/databasemodules/mysql/class.dblink.php", 'dblink');

    $this->sqlstring = "";
  }

  /** 
   * Returns a string representation of this class for printing purposes.
   * 
   * @return string 
   */
  public final function __toString()
  {
    return "class:SqlBuilder(SqlString:{$this->sqlstring}, Table:{$this->table})";
  }

  /** 
   * Build a insert type query command with the values passed in $dataset, set the working table with the name passed on $table, 
   * then returns the instance of the class.
   * 
   * @param object|array $dataset
   * @param string $table
   * @return Sql 
   */
  public function insert($dataset, string $table)
  {
    $dataset = $this->dblink->getConnection('writer')->escapevar($dataset);

    $fields = "";
    $values = " VALUES (";

    foreach ($dataset as $key => $val) {
      if (is_array($val)) {
        $fields = "";
        foreach ($val as $f => $v) {
          $fields .= $this->escape($f) . ",";
          if (!is_null($v)) $values .= (!is_string($v) ? $v : "'" . $v . "'") . ",";
          else $values .= "NULL,";
        }
        $values = rtrim($values, ",") . "),(";
      } else {
        $fields .= $this->escape($key) . ",";
        if (is_null($val)) $values .= "NULL,";
        else $values .= (!is_string($val) ? $val : "'" . $val . "'") . ",";
      }
    }
    $fields = rtrim($fields, ",") . ")";
    $values = rtrim($values, ",") . ")";
    $values = rtrim($values, "),(") . ")";

    $this->write("INSERT INTO " . $this->escape($table) . " (" . $fields . $values, $table);
    return $this;
  }

  /** 
   * Build a update type query command with the values passed in $dataset, set the working table with the name passed on $table, 
   * then returns the instance of the class.
   * 
   * @param object|array $dataset
   * @param string $table
   * @return Sql 
   */
  public function update($dataset, string $table)
  {
    $dataset = $this->dblink->getConnection('writer')->escapevar($dataset);

    $sql = "UPDATE " . $this->escape($table) . " SET ";
    foreach ($dataset as $key => $val) {
      if (!is_null($val) && $val !== false && $val !== "") {
        $sql .= $this->escape($key) . "=" . (!is_string($val) ? $val : "'" . $val . "'") . ",";
      } elseif (is_null($val) || $val === "") {
        $sql .= $this->escape($key) . '=NULL,';
      }
    }
    $sql = rtrim($sql, ",");

    $this->write($sql, $table);
    return $this;
  }

  /** 
   * Build a delete type query command, setting the table passed on $table, then returns the instance of the class.
   * 
   * @param string $table
   * @return Sql 
   */
  public function delete(string $table)
  {
    $this->write("DELETE " . $this->escape($table) . " FROM " . $this->escape($table), $table);
    return $this;
  }

  /** 
   * Build a MySQL "WHERE clause" command, add it to the current SQL command, then returns the instance of the class.
   * 
   * @param array $params
   * @return Sql 
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

        // Full text filtering with "LIKE" operator:
        if (strtoupper($operator) == "LIKE") {
          $where .= $key . ' LIKE "%' . $this->dblink->getConnection('writer')->escapevar($val) . '%"';
        }
        // Filtering by lists of values with "IN/NOT IN" operators:
        else if (is_array($val)) {
          $val = $this->dblink->getConnection('writer')->escapevar($val);

          $joined_values = array();
          $hasNullValue = false;
          if (!empty($val)) {
            foreach ($val as $in_val) {
              if (is_null($in_val)) $hasNullValue = true;
              else $joined_values[] = !is_string($in_val) ? $in_val : '"' . $in_val . '"';
            }
          } else $hasNullValue = true;

          $complement = '';
          $complementLogOp = '';
          if ($hasNullValue) {
            if ($operator == 'NOT IN') {
              $complement = " {$key} IS NOT NULL";
              $complementLogOp = ' AND';
            } else {
              $complement = " {$key} IS NULL";
              $complementLogOp = ' OR';
            }
          }

          if (!empty($joined_values))
            $where .= $key . " {$operator} (" . implode(',', $joined_values) . ')' . $complementLogOp . $complement;
          else $where .= $complement;
        }
        // Filtering with NULL values:
        elseif (is_null($val)) {
          if ($operator == '<>') $where .= "{$key} IS NOT NULL";
          else $where .= "{$key} IS NULL";
        }
        // General filtering:
        else {
          $where .= $key . ' ' . $operator . ' ' . (!is_string($val) ? $val : "'" . $this->dblink->getConnection('writer')->escapevar($val) . "'");
        }
      }
      $this->write($where, null, false);
    }

    return $this;
  }

  /** 
   * Registers or updates the SQL command in the instance of the class and returns the instance of the class.
   * 
   * @param string $sqlstr
   * @param string $table = null
   * @param boolean $overwrite = true
   * @return Sql 
   */
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

  /** 
   * Create an instance of the Sqlobj input class, which reflects the state of the instance of this class and returns it.
   * If $clear tag is set to true, reset the state of this class.
   * 
   * @param boolean $clear = false
   * @return Sqlobj 
   */
  public function output($clear = false)
  {
    $obj = new Sqlobj($this->sqlstring, $this->table);

    if ($clear)
      $this->reset();

    return $obj;
  }

  /** 
   * Reset the state of the instance of this class, setting Sql::sqlstring and Sql::table to their initial values.
   * Returns the instance of the class.
   * 
   * @return Sql 
   */
  public function reset()
  {
    $this->sqlstring = "";
    $this->table = null;
    return $this;
  }

  /** 
   * Escapes a value, surrounding it between two grave accents (`), then returns this modified value.
   * 
   * @param mixed $val
   * @return string 
   */
  private function escape($val)
  {
    return $val == "*" ? $val : "`" . $val . "`";
  }
}
