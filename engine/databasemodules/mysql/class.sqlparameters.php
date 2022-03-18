<?php
class SqlParameters
{
  private $settings;
  private $filters;
  
  public function parameterize(array $params = [], string $sql = null, string $paramPrefix = null)
  {
    $this->filters = [];
    if (!empty($params)) {
      $sortParams = [
        "sortBy" => isset($params['sort_by']) ? $params['sort_by'] : 1,
        "sortDirection" => isset($params['sort_direction']) ? $params['sort_direction'] : 'ASC'
      ];

      $pageParams = [
        "page" => isset($params['page']) ? $params['page'] : null,
        "limit" => isset($params['limit']) ? $params['limit'] : null
      ];

      $this->setup($params);

      if (isset($params['logical_operator'])) unset($params['logical_operator']);
      if (isset($params['sort_by'])) unset($params['sort_by']);
      if (isset($params['sort_direction'])) unset($params['sort_direction']);
      if (isset($params['page'])) unset($params['page']);
      if (isset($params['limit'])) unset($params['limit']);

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

  private function setup(array $params)
  {
    $this->settings = (object) [];

    // Set filtering logical operator:
    if (isset($params['logical_operator']) && ($params['logical_operator'] == 'AND' || $params['logical_operator']  == 'OR')) {
      $this->settings->logicalOperator = $params['logical_operator'];
    } else $this->settings->logicalOperator = 'AND';
  }

  private function filtering(array $params = [], string $sql = null, string $paramPrefix = null)
  {
    if (!empty($sql) && substr($sql, -1) != " ") $sql .= " ";

    $firstIteration = true;
    foreach ($params as $key => $strInstruction) {
      $instruction = explode('|', $strInstruction);
      $paramName = empty($paramPrefix) ? $key : $paramPrefix . '.' . $key;

      $logicalOperator = '';
      if (strpos($instruction[0], '$and') !== false) {
        $logicalOperator = 'AND';
        $instruction[0] = str_replace('$and', '', $instruction[0]);
      } elseif (strpos($instruction[0], '$or') !== false) {
        $logicalOperator = 'OR';
        $instruction[0] = str_replace('$or', '', $instruction[0]);
      } else $logicalOperator = $this->settings->logicalOperator;

      switch ($instruction[0]) {
        case '$eqto':
          if ($firstIteration && empty($this->filters)) {
            if (!empty($sql)) $sql .= "WHERE " . $paramName . " = ?" . $key . "? ";
            $this->filter($key)->equalsTo($instruction[1]);
          } else {
            if (!empty($sql)) $sql .= $logicalOperator . " " . $paramName . " = ?" . $key . "? ";
            $this->and($key)->equalsTo($instruction[1]);
          }
          break;
        case '$difr':
          if ($firstIteration && empty($this->filters)) {
            if (!empty($sql)) $sql .= "WHERE " . $paramName . " != ?" . $key . "? ";
            $this->filter($key)->differentFrom($instruction[1]);
          } else {
            if (!empty($sql)) $sql .= $logicalOperator . " " . $paramName . " != ?" . $key . "? ";
            $this->and($key)->differentFrom($instruction[1]);
          }
          break;
        case '$bgth':
          if ($firstIteration && empty($this->filters)) {
            if (!empty($sql)) $sql .= "WHERE " . $paramName . " > ?" . $key . "? ";
            $this->filter($key)->biggerThan($instruction[1]);
          } else {
            if (!empty($sql)) $sql .= $logicalOperator . " " . $paramName . " > ?" . $key . "? ";
            $this->and($key)->biggerThan($instruction[1]);
          }
          break;
        case '$bgeq':
          if ($firstIteration && empty($this->filters)) {
            if (!empty($sql)) $sql .= "WHERE " . $paramName . " >= ?" . $key . "? ";
            $this->filter($key)->biggerOrEqualsTo($instruction[1]);
          } else {
            if (!empty($sql)) $sql .= $logicalOperator . " " . $paramName . " >= ?" . $key . "? ";
            $this->and($key)->biggerOrEqualsTo($instruction[1]);
          }
          break;
        case '$lsth':
          if ($firstIteration && empty($this->filters)) {
            if (!empty($sql)) $sql .= "WHERE " . $paramName . " < ?" . $key . "? ";
            $this->filter($key)->lesserThan($instruction[1]);
          } else {
            if (!empty($sql)) $sql .= $logicalOperator . " " . $paramName . " < ?" . $key . "? ";
            $this->and($key)->lesserThan($instruction[1]);
          }
          break;
        case '$lseq':
          if ($firstIteration && empty($this->filters)) {
            if (!empty($sql)) $sql .= "WHERE " . $paramName . " <= ?" . $key . "? ";
            $this->filter($key)->lesserOrEqualsTo($instruction[1]);
          } else {
            if (!empty($sql)) $sql .= $logicalOperator . " " . $paramName . " <= ?" . $key . "? ";
            $this->and($key)->lesserOrEqualsTo($instruction[1]);
          }
          break;
        case '$lkof':
          if ($firstIteration && empty($this->filters)) {
            if (!empty($sql)) $sql .= "WHERE " . $paramName . " LIKE ?" . $key . "? ";
            $this->filter($key)->likeOf('%' . $instruction[1] . '%');
          } else {
            if (!empty($sql)) $sql .= $logicalOperator . " " . $paramName . " LIKE ?" . $key . "? ";
            $this->and($key)->likeOf('%' . $instruction[1] . '%');
          }
          break;
        case '$btwn':
          if ($firstIteration && empty($this->filters)) {
            if (!empty($sql)) {
              $sql .= "WHERE (" . $paramName . " >= ?" . $key . "_start? ";
              $sql .= "AND " . $paramName . " <= ?" . $key . "_end?) ";
            }

            $this->filter($key . '_start')->lesserOrEqualsTo($instruction[1]);
            $this->and($key . '_end')->biggerOrEqualsTo($instruction[2]);
          } else {
            if (!empty($sql)) {
              $sql .= $logicalOperator . " (" . $paramName . " >= ?" . $key . "_start? ";
              $sql .= "AND " . $paramName . " <= ?" . $key . "_end?) ";
            }

            $this->and($key . '_start')->lesserOrEqualsTo($instruction[1]);
            $this->and($key . '_end')->biggerOrEqualsTo($instruction[2]);
          }
          break;
        default:
          if ($firstIteration && empty($this->filters)) {
            if (!empty($sql)) $sql .= "WHERE " . $paramName . " = ?" . $key . "? ";
            $this->filter($key)->equalsTo($instruction[0]);
          } else {
            if (!empty($sql)) $sql .= $logicalOperator . " " . $paramName . " = ?" . $key . "? ";
            $this->and($key)->equalsTo($instruction[0]);
          }
          break;
      }
      $firstIteration = false;
    }

    return (object) [
      "sql" => $sql
    ];
  }

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

  private final function filter($key, $sanitize = true)
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

  private final function and($key, $sanitize = true)
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

  private final function or($key, $sanitize = true)
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

  private final function equalsTo($value)
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

  private final function differentFrom($value)
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

  private final function biggerThan($value)
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

  private final function lesserThan($value)
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

  private final function biggerOrEqualsTo($value)
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

  private final function lesserOrEqualsTo($value)
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

  private final function likeOf($value)
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
