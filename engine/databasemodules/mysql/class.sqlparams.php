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

/**
 * Class SqlParams
 * 
 * This class parameterizes database operations automatically, editing the SQL command string and adding filters to the DAO, 
 * according to the parameters passed to it.
 *
 * @package engine/databasemodules/mysql
 */
class SqlParams
{
  /**
   * @var object $settings
   * An object containing some param settings.
   */
  private $settings;
  
  /**
   * @var array $filters
   * An array of objects, on which each object contains settings of the filters that wil be applied on the operation.
   */
  private $filters;

  /** 
   * Set DAO's filtering, sorting and pagination parameters, based on the data received in $params. If a query is passed in $sql,
   * edit this query, according to the filters set. If a $paramPrefix is set, add this prefix on all parameter names. Returns an object containing 
   * the resulting DAO filters and SQL query.
   * 
   * @param array $params = []
   * @param string $sql = null
   * @param string $paramPrefix = null
   * @return object 
   */
  public function parameterize(array $params = [], string $sql = null, string $paramPrefix = null)
  {
    $this->filters = [];
    if (!empty($params)) {
      $sortParams = [
        "sortBy" => isset($params['$sort_by']) ? $params['$sort_by'] : 1,
        "sortDirection" => isset($params['$sort_direction']) ? $params['$sort_direction'] : 'ASC'
      ];

      $pageParams = [
        "page" => isset($params['$page']) ? $params['$page'] : null,
        "limit" => isset($params['$limit']) ? $params['$limit'] : null
      ];

      $this->setup($params);

      if (isset($params['$logical_operator'])) unset($params['$logical_operator']);
      if (isset($params['$sort_by'])) unset($params['$sort_by']);
      if (isset($params['$sort_direction'])) unset($params['$sort_direction']);
      if (isset($params['$page'])) unset($params['$page']);
      if (isset($params['$limit'])) unset($params['$limit']);

      if (!empty($sql) && file_exists(INCLUDE_PATH . '/application/sql/' . $sql . '.sql')) {
        $sql = file_get_contents(INCLUDE_PATH . '/application/sql/' . $sql . '.sql');
      }

      // FILTER:
      $filtered = $this->filtering($params, $sql, $paramPrefix);
      $sql = $filtered->sql;

      if (!empty($sql)) {
        // SORT:
        $sort = $this->sorting($sortParams, $sql);
        $sql = $sort->sql;

        // PAGINATE:
        $sort = $this->pagination($pageParams, $sql);
        $sql = $sort->sql;
      }
    }

    return (object) [
      "filters" => $this->filters,
      "sql" => $sql
    ];
  }

  /** 
   * Set the default logic operator ("AND" / "OR"), based on the parameters.
   * 
   * @param array $params = []
   * @return void 
   */
  private function setup(array $params)
  {
    $this->settings = (object) [];

    // Set filtering logical operator:
    if (isset($params['$logical_operator']) && ($params['$logical_operator'] == 'AND' || $params['$logical_operator']  == 'OR')) {
      $this->settings->logicalOperator = $params['$logical_operator'];
    } else $this->settings->logicalOperator = 'AND';
  }

  /** 
   * Based on the received parameters, add DAO filters for filtering and edit SQL's WHERE clause. Returns 
   * an object containing the resulting SQL query.
   * 
   * @param array $params = []
   * @param string $sql = null
   * @param string $paramPrefix = null
   * @return object 
   */
  private function filtering(array $params = [], string $sql = null, string $paramPrefix = null)
  {
    if (!empty($sql) && substr($sql, -1) != " ") $sql .= " ";

    $firstIteration = true;
    foreach ($params as $key => $strInstruction) {
      $instruction = explode('|', $strInstruction);

      $paramName = empty($paramPrefix) ? $key : $paramPrefix . '.' . $key;

      $regexMatches = [];
      preg_match('/\$tbprefix=(.+)/', $instruction[0], $regexMatches);
      if (!empty($regexMatches[1])) {
        $paramName = $regexMatches[1] . '.' . $key;
        $instruction[0] = preg_replace('/\$tbprefix=.+/', '', $instruction[0]);
      }

      $logicalOperator = '';
      if (strpos($instruction[0], '$and') !== false) {
        $logicalOperator = 'AND';
        $logicalOperatorMethod = 'and';
        $instruction[0] = str_replace('$and', '', $instruction[0]);
      } elseif (strpos($instruction[0], '$or') !== false) {
        $logicalOperator = 'OR';
        $logicalOperatorMethod = 'or';
        $instruction[0] = str_replace('$or', '', $instruction[0]);
      } else $logicalOperator = $this->settings->logicalOperator;

      switch ($instruction[0]) {
        case '$eqto':
          if ($firstIteration && empty($this->filters)) {
            if (!empty($sql)) $sql .= "WHERE " . $paramName . " = ?" . $key . "? ";
            $this->filter($key)->equalsTo($instruction[1]);
          } else {
            if (!empty($sql)) $sql .= $logicalOperator . " " . $paramName . " = ?" . $key . "? ";
            $this->$logicalOperatorMethod($key)->equalsTo($instruction[1]);
          }
          break;
        case '$difr':
          if ($firstIteration && empty($this->filters)) {
            if (!empty($sql)) $sql .= "WHERE " . $paramName . " != ?" . $key . "? ";
            $this->filter($key)->differentFrom($instruction[1]);
          } else {
            if (!empty($sql)) $sql .= $logicalOperator . " " . $paramName . " != ?" . $key . "? ";
            $this->$logicalOperatorMethod($key)->differentFrom($instruction[1]);
          }
          break;
        case '$bgth':
          if ($firstIteration && empty($this->filters)) {
            if (!empty($sql)) $sql .= "WHERE " . $paramName . " > ?" . $key . "? ";
            $this->filter($key)->biggerThan($instruction[1]);
          } else {
            if (!empty($sql)) $sql .= $logicalOperator . " " . $paramName . " > ?" . $key . "? ";
            $this->$logicalOperatorMethod($key)->biggerThan($instruction[1]);
          }
          break;
        case '$bgeq':
          if ($firstIteration && empty($this->filters)) {
            if (!empty($sql)) $sql .= "WHERE " . $paramName . " >= ?" . $key . "? ";
            $this->filter($key)->biggerOrEqualsTo($instruction[1]);
          } else {
            if (!empty($sql)) $sql .= $logicalOperator . " " . $paramName . " >= ?" . $key . "? ";
            $this->$logicalOperatorMethod($key)->biggerOrEqualsTo($instruction[1]);
          }
          break;
        case '$lsth':
          if ($firstIteration && empty($this->filters)) {
            if (!empty($sql)) $sql .= "WHERE " . $paramName . " < ?" . $key . "? ";
            $this->filter($key)->lesserThan($instruction[1]);
          } else {
            if (!empty($sql)) $sql .= $logicalOperator . " " . $paramName . " < ?" . $key . "? ";
            $this->$logicalOperatorMethod($key)->lesserThan($instruction[1]);
          }
          break;
        case '$lseq':
          if ($firstIteration && empty($this->filters)) {
            if (!empty($sql)) $sql .= "WHERE " . $paramName . " <= ?" . $key . "? ";
            $this->filter($key)->lesserOrEqualsTo($instruction[1]);
          } else {
            if (!empty($sql)) $sql .= $logicalOperator . " " . $paramName . " <= ?" . $key . "? ";
            $this->$logicalOperatorMethod($key)->lesserOrEqualsTo($instruction[1]);
          }
          break;
        case '$lkof':
          if ($firstIteration && empty($this->filters)) {
            if (!empty($sql)) $sql .= "WHERE " . $paramName . " LIKE ?" . $key . "? ";
            $this->filter($key)->likeOf('%' . $instruction[1] . '%');
          } else {
            if (!empty($sql)) $sql .= $logicalOperator . " " . $paramName . " LIKE ?" . $key . "? ";
            $this->$logicalOperatorMethod($key)->likeOf('%' . $instruction[1] . '%');
          }
          break;
        case '$btwn':
          if ($firstIteration && empty($this->filters)) {
            if (!empty($sql)) {
              $sql .= "WHERE (" . $paramName . " >= ?" . $key . "_start? ";
              $sql .= "AND " . $paramName . " <= ?" . $key . "_end?) ";
            }

            $this->filter($key . '_start')->lesserOrEqualsTo($instruction[1]);
            $this->$logicalOperatorMethod($key . '_end')->biggerOrEqualsTo($instruction[2]);
          } else {
            if (!empty($sql)) {
              $sql .= $logicalOperator . " (" . $paramName . " >= ?" . $key . "_start? ";
              $sql .= "AND " . $paramName . " <= ?" . $key . "_end?) ";
            }

            $this->$logicalOperatorMethod($key . '_start')->lesserOrEqualsTo($instruction[1]);
            $this->$logicalOperatorMethod($key . '_end')->biggerOrEqualsTo($instruction[2]);
          }
          break;
        default:
          if ($firstIteration && empty($this->filters)) {
            if (!empty($sql)) $sql .= "WHERE " . $paramName . " = ?" . $key . "? ";
            $this->filter($key)->equalsTo($instruction[0]);
          } else {
            if (!empty($sql)) $sql .= $logicalOperator . " " . $paramName . " = ?" . $key . "? ";
            $this->$logicalOperatorMethod($key)->equalsTo($instruction[0]);
          }
          break;
      }
      $firstIteration = false;
    }

    return (object) [
      "sql" => $sql
    ];
  }

  /** 
   * Based on the received parameters, add DAO filters for sorting and edit SQL's ORDER BY clause. Returns 
   * an object containing the resulting SQL query.
   * 
   * @param array $params
   * @param string $sql = null
   * @return object 
   */
  private function sorting(array $params, string $sql = null)
  {
    $params['sortDirection'] = strtoupper($params['sortDirection']);

    if ($params['sortDirection'] != 'ASC' && $params['sortDirection'] != 'DESC') throw new Exception("Parameter Sort Direction is invalid.");

    if (empty($this->filters)) {
      $this->filter('sortBy')->equalsTo($params['sortBy']);
      if (!empty($sql)) $sql .= " ORDER BY ?sortBy? " . $params['sortDirection'] . " ";
    } else {
      $this->and('sortBy')->equalsTo($params['sortBy']);
      if (!empty($sql)) $sql .= " ORDER BY ?sortBy? " . $params['sortDirection'] . " ";
    }

    return (object) [
      "sql" => $sql
    ];
  }

  /** 
   * Based on the received parameters, add DAO filters for pagination purposes and edit SQL's LIMIT/OFFSET clause. Returns 
   * an object containing the resulting SQL query.
   * 
   * @param array $params
   * @param string $sql = null
   * @return object 
   */
  private function pagination(array $params, string $sql = null)
  {
    if (!empty($params['page']) && !empty($params['limit'])) {
      if (is_numeric($params['page']) == false || is_numeric($params['limit']) == false)
        throw new Exception("Invalid input.");

      $offset = ($params['page'] - 1) * $params['limit'];

      $params['limit'] = 5 * $params['limit'];

      if (!empty($sql)) $sql .= "LIMIT " . $params['limit'] . " OFFSET " . $offset;
    } elseif (!empty($params['limit'])) {
      if (is_numeric($params['limit']) == false) throw new Exception("Invalid input.");

      if (!empty($sql)) $sql .= "LIMIT " . $params['limit'];
    }

    return (object) [
      "sql" => $sql
    ];
  }

  /** 
   * Add filter data to DAO filter and returns this class instance.
   * 
   * @param string $key
   * @param boolean $sanitize = true
   * @return SqlParams 
   */
  private final function filter(string $key, $sanitize = true)
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

  /** 
   * Add filter data to DAO filter, specifying logical operator to "AND", then returns this class instance.
   * 
   * @param string $key
   * @param boolean $sanitize = true
   * @return SqlParams 
   */
  private final function and(string $key, bool $sanitize = true)
  {
    if (count($this->filters) == 0) {
      throw new Exception('You can only call this method after calling filter() first.');
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

  /** 
   * Add filter data to DAO filter, specifying logical operator to "OR", then returns this class instance.
   * 
   * @param string $key
   * @param boolean $sanitize = true
   * @return SqlParams 
   */
  private final function or(string $key, bool $sanitize = true)
  {
    if (count($this->filters) == 0) {
      throw new Exception('You can only call this method after calling filter() first.');
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

  /** 
   * Edit the last added DAO filter data, specifying comparison operator to "=" and setting its value based on what it has received in $value.
   * Returns this class instance.
   * 
   * @param mixed $value
   * @return SqlParams 
   */
  private final function equalsTo( $value)
  {
    $i = count($this->filters);
    if ($i == 0 || !is_null($this->filters[$i - 1]->value)) {
      throw new Exception('This method can only be called right after one of the filtering methods.');
      return false;
    }

    $i--;

    $this->filters[$i]->value = $value;
    $this->filters[$i]->operator = '=';

    return $this;
  }

  /** 
   * Edit the last added DAO filter data, specifying comparison operator to "!=" and setting its value based on what it has received in $value.
   * Returns this class instance.
   * 
   * @param mixed $value
   * @return SqlParams 
   */
  private final function differentFrom( $value)
  {
    $i = count($this->filters);
    if ($i == 0 || !is_null($this->filters[$i - 1]->value)) {
      throw new Exception('This method can only be called right after one of the filtering methods.');
      return false;
    }

    $i--;

    $this->filters[$i]->value = $value;
    $this->filters[$i]->operator = '<>';

    return $this;
  }

  /** 
   * Edit the last added DAO filter data, specifying comparison operator to ">" and setting its value based on what it has received in $value.
   * Returns this class instance.
   * 
   * @param mixed $value
   * @return SqlParams 
   */
  private final function biggerThan( $value)
  {
    $i = count($this->filters);
    if ($i == 0 || !is_null($this->filters[$i - 1]->value)) {
      throw new Exception('This method can only be called right after one of the filtering methods.');
      return false;
    }

    $i--;

    $this->filters[$i]->value = $value;
    $this->filters[$i]->operator = '>';

    return $this;
  }

  /** 
   * Edit the last added DAO filter data, specifying comparison operator to "<" and setting its value based on what it has received in $value.
   * Returns this class instance.
   * 
   * @param mixed $value
   * @return SqlParams 
   */
  private final function lesserThan( $value)
  {
    $i = count($this->filters);
    if ($i == 0 || !is_null($this->filters[$i - 1]->value)) {
      throw new Exception('This method can only be called right after one of the filtering methods.');
      return false;
    }

    $i--;

    $this->filters[$i]->value = $value;
    $this->filters[$i]->operator = '<';

    return $this;
  }

  /** 
   * Edit the last added DAO filter data, specifying comparison operator to ">=" and setting its value based on what it has received in $value.
   * Returns this class instance.
   * 
   * @param mixed $value
   * @return SqlParams 
   */
  private final function biggerOrEqualsTo( $value)
  {
    $i = count($this->filters);
    if ($i == 0 || !is_null($this->filters[$i - 1]->value)) {
      throw new Exception('This method can only be called right after one of the filtering methods.');
      return false;
    }

    $i--;

    $this->filters[$i]->value = $value;
    $this->filters[$i]->operator = '>=';

    return $this;
  }

  /** 
   * Edit the last added DAO filter data, specifying comparison operator to "<=" and setting its value based on what it has received in $value.
   * Returns this class instance.
   * 
   * @param mixed $value
   * @return SqlParams 
   */
  private final function lesserOrEqualsTo( $value)
  {
    $i = count($this->filters);
    if ($i == 0 || !is_null($this->filters[$i - 1]->value)) {
      throw new Exception('This method can only be called right after one of the filtering methods.');
      return false;
    }

    $i--;

    $this->filters[$i]->value = $value;
    $this->filters[$i]->operator = '<=';

    return $this;
  }

  /** 
   * Edit the last added DAO filter data, specifying comparison operator to "LIKE" and setting its value based on what it has received in $value.
   * Returns this class instance.
   * 
   * @param mixed $value
   * @return SqlParams 
   */
  private final function likeOf( $value)
  {
    $i = count($this->filters);
    if ($i == 0 || !is_null($this->filters[$i - 1]->value)) {
      throw new Exception('This method can only be called right after one of the filtering methods.');
      return false;
    }

    $i--;

    $this->filters[$i]->value = $value;
    $this->filters[$i]->operator = 'LIKE';

    return $this;
  }
}
