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

use Exception;

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
   * Returns a string representation of this class for printing purposes.
   * 
   * @return string 
   */
  public final function __toString()
  {
    return "class:SqlParams()";
  }

  /** 
   * Set DAO's filtering, sorting and pagination parameters, based on the data received in $params. If a query is passed in $sql,
   * edit this query, according to the filters set. Returns an object containing 
   * the resulting DAO filters and SQL query.
   * 
   * @param array $paramSet = []
   * @param string $sql = null
   * @return object 
   */
  public function parameterize(array $paramSet = [], string $sql = null)
  {
    $sql = !empty($sql) ? "SELECT * FROM ({$sql}) as derived_table " : null;
    $finalFilters = [];

    foreach ($paramSet as $placeholder => $paramObj) {
      if (empty($paramObj->paramList)) continue;

      $this->settings = (object) [];
      $this->filters = [];

      $params = $paramObj->paramList;
      $global = $paramObj->global;

      $sortParams = [
        "sortBy" => isset($params['$sort_by']) ? (is_numeric($params['$sort_by']) ? intval($params['$sort_by']) : $params['$sort_by']) : 1,
        "sortDirection" => isset($params['$sort_direction']) ? $params['$sort_direction'] : 'ASC'
      ];

      $pageParams = [
        "page" => isset($params['$page']) ? $params['$page'] : null,
        "limit" => isset($params['$limit']) ? $params['$limit'] : null
      ];

      // Set filtering logical operator:
      if (isset($params['$logical_operator']) && ($params['$logical_operator'] == 'AND' || $params['$logical_operator']  == 'OR')) {
        $this->settings->logicalOperator = $params['$logical_operator'];
      } else $this->settings->logicalOperator = 'AND';

      if (isset($params['$logical_operator'])) unset($params['$logical_operator']);
      if (isset($params['$sort_by'])) unset($params['$sort_by']);
      if (isset($params['$sort_direction'])) unset($params['$sort_direction']);
      if (isset($params['$page'])) unset($params['$page']);
      if (isset($params['$limit'])) unset($params['$limit']);

      // FILTER:
      $filterBLock = $this->filtering($params);

      if (!empty($sql)) {
        // If parameterization is global, attach param blocks to main query, else, replace placeholder for it:
        if ($global) {
          // SORT:
          $sortBlock = $this->sorting($sortParams);

          // PAGINATE:
          $paginationBlock = $this->pagination($pageParams);
          $sql .= $filterBLock . $sortBlock . $paginationBlock;
        } else {
          $sql = str_replace("#{$placeholder}#", $filterBLock, $sql);
        }

        // Clear any placeholders that were not replaced by any parameters:
        $sql = preg_replace('/\#.*\#/', '', $sql);
      }

      $finalFilters = array_merge($finalFilters, $this->filters);
    }


    return (object) [
      "filters" => $finalFilters,
      "sql" => $sql
    ];
  }

  /** 
   * Based on the received parameters, add DAO filters for filtering and edit SQL's WHERE clause. Returns 
   * an object containing the resulting SQL query.
   * 
   * @param array $params = []
   * @return string 
   */
  private function filtering(array $params = [])
  {
    $sqlBlock = '';
    $firstIteration = true;
    foreach ($params as $paramName => $strInstruction) {
      if (is_string($strInstruction) && strpos($strInstruction, '|') !== false) {
        $instruction = explode('|', $strInstruction);
      } else $instruction = ['', $strInstruction];

      // Treat FILTER GROUPING param option:
      $filterGroupStart = '';
      $filterGroupEnd = '';
      if (strpos($instruction[0], '$startFilterGroup') !== false) {
        $filterGroupStart = '(';
        $instruction[0] = str_replace('$startFilterGroup', '', $instruction[0]);
      }
      if (strpos($instruction[0], '$endFilterGroup') !== false) {
        $filterGroupEnd = ')';
        $instruction[0] = str_replace('$endFilterGroup', '', $instruction[0]);
      }

      // Treat LOGICAL OPERATOR($or/$and) param option:
      $logicalOperator = '';
      $logicalOperatorMethod = '';
      if ($firstIteration && empty($this->filters)) {
        $logicalOperator = 'WHERE';
        $logicalOperatorMethod = 'filter';
      } elseif (strpos($instruction[0], '$and') !== false) {
        $logicalOperator = 'AND';
        $logicalOperatorMethod = 'and';
        $instruction[0] = str_replace('$and', '', $instruction[0]);
      } elseif (strpos($instruction[0], '$or') !== false) {
        $logicalOperator = 'OR';
        $logicalOperatorMethod = 'or';
        $instruction[0] = str_replace('$or', '', $instruction[0]);
      } else {
        $logicalOperator = $this->settings->logicalOperator;
        $logicalOperatorMethod = strtolower($this->settings->logicalOperator);
      }

      // Treat COMPARISON OPERATOR param option:
      $alreadyFiltered = false;
      switch ($instruction[0]) {
        case '$eqto':
          $comparisonOperatorMethod = 'equalsTo';
          $comparisonOperator = ' = ';
          break;
        case '$difr':
          $comparisonOperatorMethod = 'differentFrom';
          $comparisonOperator = ' != ';
          break;
        case '$bgth':
          $comparisonOperatorMethod = 'biggerThan';
          $comparisonOperator = ' > ';
          break;
        case '$bgeq':
          $comparisonOperatorMethod = 'biggerOrEqualsTo';
          $comparisonOperator = ' >= ';
          break;
        case '$lsth':
          $comparisonOperatorMethod = 'lessThan';
          $comparisonOperator = ' < ';
          break;
        case '$lseq':
          $comparisonOperatorMethod = 'lesserOrEqualsTo';
          $comparisonOperator = ' <= ';
          break;
        case '$lkof':
          $comparisonOperatorMethod = 'likeOf';
          $comparisonOperator = ' LIKE ';
          $instruction[1] = '%' . $instruction[1] . '%';
          break;
        case '$btwn':
          $sqlBlock .= $logicalOperator . $filterGroupStart . " (" . $paramName . " >= ?" . $paramName . "_start? ";
          $sqlBlock .= "AND " . $paramName . " <= ?" . $paramName . "_end?)" . $filterGroupEnd . " ";

          $this->$logicalOperatorMethod($paramName . '_start')->lesserOrEqualsTo($instruction[1]);
          $this->and($paramName . '_end')->biggerOrEqualsTo($instruction[2]);
          $alreadyFiltered = true;
          break;
        case '$in':
          $comparisonOperatorMethod = 'in';
          $comparisonOperator = ' IN ';
          $instruction[1] = array_slice($instruction, 1);
          break;
        case '$notin':
          $comparisonOperatorMethod = 'notIn';
          $comparisonOperator = ' NOT IN ';
          $instruction[1] = array_slice($instruction, 1);
          break;
        default:
          $comparisonOperatorMethod = 'equalsTo';
          $comparisonOperator = ' = ';
          break;
      }

      // Filter Dao and query:
      if (!$alreadyFiltered) {
        $condition = $paramName . $comparisonOperator . "?" . $paramName . "?";
        // Filtering by lists of values with "IN/NOT IN" operators:
        if (is_array($instruction[1])) {
          $hasNullValue = false;
          foreach ($instruction[1] as $k => $in_val)
            if (is_null($in_val) || strtoupper($in_val) === 'NULL') {
              $hasNullValue = true;
              unset($instruction[1][$k]);
            }

          $complement = '';
          $complementLogOp = '';
          if ($hasNullValue) {
            $complement = "{$paramName} IS NULL";
            $complementLogOp = 'OR';
            if ($comparisonOperatorMethod == 'notIn') {
              $complement = "{$paramName} IS NOT NULL";
              $complementLogOp = 'AND';
            }
          }

          if (!empty($instruction[1]))
            $condition = "{$condition} {$complementLogOp} {$complement}";
          else $condition = $complement;
        }
        // Filtering with NULL values:
        elseif (is_null($instruction[1])) {
          $comparisonOperator = $comparisonOperator == '!=' ? 'IS NOT' : 'IS';
          $condition = "{$paramName} {$comparisonOperator} NULL";
        }

        $sqlBlock .= $logicalOperator . ' ' . $filterGroupStart .  $condition . $filterGroupEnd . " ";
        if (!is_null($instruction[1]))
          $this->$logicalOperatorMethod($paramName)->$comparisonOperatorMethod($instruction[1]);
      }

      $firstIteration = false;
    }

    return $sqlBlock;
  }

  /** 
   * Based on the received parameters, add DAO filters for sorting and edit SQL's ORDER BY clause. Returns 
   * an object containing the resulting SQL query.
   * 
   * @param array $params
   * @return string 
   */
  private function sorting(array $params)
  {
    $sqlBlock = '';
    $params['sortDirection'] = strtoupper($params['sortDirection']);

    if ($params['sortDirection'] != 'ASC' && $params['sortDirection'] != 'DESC') throw new Exception("Parameter Sort Direction is invalid.");

    if (empty($this->filters)) {
      $this->filter('sortBy')->equalsTo($params['sortBy']);
      $sqlBlock .= " ORDER BY ?sortBy? " . $params['sortDirection'] . " ";
    } else {
      $this->and('sortBy')->equalsTo($params['sortBy']);
      $sqlBlock .= " ORDER BY ?sortBy? " . $params['sortDirection'] . " ";
    }

    return $sqlBlock;
  }

  /** 
   * Based on the received parameters, add DAO filters for pagination purposes and edit SQL's LIMIT/OFFSET clause. Returns 
   * an object containing the resulting SQL query.
   * 
   * @param array $params
   * @return string 
   */
  private function pagination(array $params)
  {
    $sqlBlock = '';

    if (!empty($params['page']) && !empty($params['limit'])) {
      if (is_numeric($params['page']) == false || is_numeric($params['limit']) == false)
        throw new Exception("Invalid input.");

      $offset = ($params['page'] - 1) * $params['limit'];

      $params['limit'] = 5 * $params['limit'];

      $sqlBlock .= "LIMIT " . $params['limit'] . " OFFSET " . $offset;
    } elseif (!empty($params['limit'])) {
      if (is_numeric($params['limit']) == false) throw new Exception("Invalid input.");

      $sqlBlock .= "LIMIT " . $params['limit'];
    }

    return $sqlBlock;
  }

  /** 
   * Add filter data to DAO filter and returns this class instance.
   * 
   * @param string $key
   * @param boolean $sanitize = true
   * @return SqlParams 
   */
  private function filter(string $key, $sanitize = true)
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
  private function and(string $key, bool $sanitize = true)
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
  private function or(string $key, bool $sanitize = true)
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
  private function equalsTo($value)
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
  private function differentFrom($value)
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
  private function biggerThan($value)
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
  private function lessThan($value)
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
  private function biggerOrEqualsTo($value)
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
  private function lesserOrEqualsTo($value)
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
  private function likeOf($value)
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

  /**
   * Edit the last added DAO filter data, specifying comparison operator to "IN" and setting its value based on what it has received in $value.
   * Returns this class instance.
   *
   * @param array $value
   * @return Dao
   */
  private function in(array $value)
  {
    $i = count($this->filters);
    if ($i == 0 || !is_null($this->filters[$i - 1]->value)) {
      throw new Exception('This method can only be called right after one of the filtering methods.');
      return false;
    }

    $i--;

    $this->filters[$i]->value = $value;
    $this->filters[$i]->operator = 'IN';

    return $this;
  }

  /**
   * Edit the last added DAO filter data, specifying comparison operator to "NOT IN" and setting its value based on what it has received in $value.
   * Returns this class instance.
   *
   * @param array $value
   * @return Dao
   */
  private function notIn(array $value)
  {
    $i = count($this->filters);
    if ($i == 0 || !is_null($this->filters[$i - 1]->value)) {
      throw new Exception('This method can only be called right after one of the filtering methods.');
      return false;
    }

    $i--;

    $this->filters[$i]->value = $value;
    $this->filters[$i]->operator = 'NOT IN';

    return $this;
  }
}
