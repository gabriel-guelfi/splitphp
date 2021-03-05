<?php

/* ///////////////////////////
  PDO SQLOBJ PACKAGE CLASS////
 *////////////////////////////

  class Sqlobj {

    // SQL string, itself.
    public $sqlstring;
    // The values to be inserted in sql.
    public $sqlvalues;
    // Current table name.
    public $table;
    // An index for select queries responses for data mapping purposes
    public $responseIndex;

    public function __construct($str, $vals, $table, $responseIndex = null) {
        $this->sqlstring = $str;
        $this->sqlvalues = $vals;
        $this->table = $table;
        $this->responseIndex = $responseIndex;
    }

}

/* ////////////////////////////
  PDO SQL QUERY BUILDER CLASS//
 */////////////////////////////

  class Sql {

    // SQL string, itself.
    private $sqlstring;
    // The values to be inserted in sql.
    private $sqlvalues;
    // Current table name.
    private $table;
    // An index for select queries responses for data mapping purposes
    private $responseIndex;

    public function __construct() {
        $this->sqlstring = "";
        $this->sqlvalues = array();
        $this->responseIndex = null;
    }

    // Build a insert type query string with argument passed in dataset.
    public function insert($dataset, $table) {
        $fields = "";
        $values = " VALUES (";
        $arrVals = [];

        foreach ($dataset as $key => $val) {
            if(is_array($val)){
                $fields = "";
                foreach($val as $f => $v){
                    if ($f != Dbmetadata::tbInfo($table)->key->keyname) {
                        if (!empty($v)) {
                            $fields .= $this->escape($f) . ",";
                            $values .= "?,";
                            $arrVals[] = $v;
                        }
                    }
                }
                $values = rtrim($values, ","). "),(";
            } else{
                if ($key != Dbmetadata::tbInfo($table)->key->keyname) {
                    if (!empty($val)) {
                        $values .= "?,";
                        $arrVals[] = $val;
                        $fields .= $this->escape($key) . ",";
                    }
                }
            }
        }
        $fields = rtrim($fields, ",") . ")";
        $values = rtrim($values, ",") . ")";
        $values = rtrim($values, "),(") . ")";

        $this->write("INSERT INTO " . $this->escape($table) . " (" . $fields . $values, $arrVals, $table);
        return $this;
    }

    // Build a update type query string with argument passed in dataset.
    public function update($dataset, $table) {
        $sql = "UPDATE " . $this->escape($table) . " SET ";
        foreach ($dataset as $key => $val) {
            if (!is_null($val) && $val !== false) {
                $sql .= $this->escape($key) . "= ? ,";
            }
        }
        $sql = rtrim($sql, " ,");

        $this->write($sql, $dataset, $table);
        return $this;
    }

    // Build a select type query string with argument passed in dataset.
    public function select($fields, $table) {
        if (is_string($fields)) {
            $fields = array($fields);
        }
        $tb_key = Dbmetadata::tbInfo($table)->key;

        $sql = "SELECT " . $table . "." . $this->escape($tb_key->keyname) . " AS " . $this->escape($tb_key->keyalias) . ",";
        $this->responseIndex[$table][] = $tb_key->keyalias;

        foreach ($fields as $f) {
            if (is_array($f)) {
                if ($f[1] === "*") {
                    foreach (Dbmetadata::tbInfo($f[0])->fields as $c) {
                        if ($f[0] == $table && $c->Field === $tb_key->keyname)
                            continue;
                        $sql .= $f[0] . "." . $this->escape($c->Field) . " AS " . $this->escape($f[0] . "_" . $c->Field) . ",";
                        $this->responseIndex[$f[0]][] = $f[0] . "_" . $c->Field;
                    }
                    $sql = rtrim($sql, ",");
                } elseif ($f[1] === $tb_key->keyname) {
                    continue;
                } else {
                    $sql .= $f[0] . "." . $this->escape($f[1]) . " AS " . $this->escape($f[0] . "_" . $f[1]);
                    $this->responseIndex[$f[0]][] = $f[0] . "_" . $f[1];
                }
            } else {
                if ($f === "*") {
                    foreach (Dbmetadata::tbInfo($table)->fields as $c) {
                        if ($c->Field === $tb_key->keyname)
                            continue;
                        $sql .= $table . "." . $this->escape($c->Field) . " AS " . $this->escape($table . "_" . $c->Field) . ",";
                        $this->responseIndex[$table][] = $table . "_" . $c->Field;
                    }
                    $sql = rtrim($sql, ",");
                } elseif ($f === $tb_key->keyname) {
                    continue;
                } else {
                    $sql .= $table . "." . $this->escape($f) . " AS " . $this->escape($table . "_" . $f);
                    $this->responseIndex[$table][] = $table . "_" . $f;
                }
            }
            $sql .= ",";
        }
        $sql = rtrim($sql, ",");

        $this->write($sql . " FROM " . $this->escape($table), array(), $table);
        return $this;
    }

    // Build a delete type query string with argument passed in dataset.
    public function delete($table) {
        $this->write("DELETE " . $this->escape($table) . " FROM " . $this->escape($table), array(), $table);
        return $this;
    }

    /* Build a Mysql where clause string based on conditions passed on params,
     * the join OR or AND and operator as = or LIKE.
     */

    public function where(Array $params, $join = 'AND', $operator = '=') {
        $where = '';
        if (!empty($params)) {
            $_conditions = array();
            foreach ($params as $key => $val) {
                $key = $this->table . "." . $this->escape($key);
                if (strtoupper($operator) == "LIKE") {
                    $_conditions[] = $key . ' LIKE ? ';
                } else if (is_array($val) && !empty($val)) {
                    $joined_values = array();

                    foreach ($val as $in_val) {
                        $joined_values[] = ' ? ';
                    }

                    $_conditions[] = $key . ' IN (' . join(',', $joined_values) . ')';
                } else {
                    $_conditions[] = $key . $operator . ' ? ';
                }
            }
            $join = strtoupper($join);
            $join = 'AND' == $join || 'OR' == $join ? " {$join} " : null;

            $where = $join !== null ? ' WHERE ' . join($join, $_conditions) : '';
        }

        $arrvalues = array();
        foreach ($params as $c) {
            if (is_array($c)) {
                $arrvalues = array_merge($arrvalues, $c);
            } else {
                $arrvalues[] = $c;
            }
        }

        $this->write($where, (isset($arrvalues) ? $arrvalues : array()), null, false);

        return $this;
    }

    public function join($table2join, $matches, $way = 'INNER', $operators = "=", $joint = 'AND') {
        $str = " " . $way . " JOIN " . $this->escape($table2join) . " ON ";
        if(is_string($matches)){
            $str .= $matches;
        } elseif(is_array($matches)) {
            $counter = 0;
            foreach ($matches as $m) {

                $str .=  $m . " " . (is_array($joint) ? $joint[$counter] : $joint) . " ";
                $counter++;
            }
            $str = rtrim($str, " " . $joint . " ");
        }

        $this->write($str, array(), null, false);
        return $this;
    }

    // Register SQL query data, then return the object.
    public function write($sqlstr, $values = [], $table = null, $overwrite = true) {
        if ($overwrite) {
            $this->sqlstring = $sqlstr;
            $this->sqlvalues = $values;
            $this->table = $table;
        } else {
            $this->sqlstring .= $sqlstr;
            $this->sqlvalues = array_merge($this->sqlvalues, array_values($values));
        }
        return $this;
    }

    public function orderBy(array $factor){
        $sql = 'ORDER BY ';
        foreach($factor as $f => $o){
            $sql .= $this->escape($f).' '.$o.', ';
        }
        $sql = rtrim($sql, ', ');

        $this->write(' '.$sql, array(), null, false);

        return $this;
    }

    private function escape($val) {
        return $val == "*" ? $val : "`" . $val . "`";
    }

    public function output($clear = false) {
        $obj = new Sqlobj($this->sqlstring, $this->sqlvalues, $this->table, $this->responseIndex);

        if ($clear)
            $this->reset();

        return $obj;
    }

    // Erase SQL query data, then return the object.
    public function reset() {
        $this->sqlstring = "";
        $this->table = "";
        $this->responseIndex = null;
        $this->sqlvalues = array();
        return $this;
    }

}

?>