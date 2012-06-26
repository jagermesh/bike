<?php

/**
 * Project:     Breeze framework
 * Author:      Jager Mesh (jagermesh@gmail.com)
 *
 * @version 1.1.0.0
 * @package Breeze Core
 */

require_once(dirname(__FILE__).'/BrGenericDBProvider.php');

class BrMySQLRegExp {

  private $value;

  public function __construct($value) {

    $this->value = $value;
      
  }

  public function getValue() {

    return $this->value;

  }

}

class BrMySQLProviderCursor implements Iterator {

  private $sql;
  private $position = 0;
  private $rows = array();
  private $limit;
  private $skip;

  public function __construct($sql, $args, &$provider) {

    $this->sql = $sql;
    $this->args = $args;
    $this->provider = $provider;
    $this->position = -1;
      
  }

  private function getData() {

    if ($this->position == -1) {
      if (strlen($this->limit)) {
        $this->sql = $this->provider->getLimitSQL($this->sql, $this->skip, $this->limit);
      }
      $this->rows = $this->provider->getRows($this->sql, $this->args);
      $this->position = 0;
    }

  }

  function limit($limit) {

    $this->limit = $limit;
    return $this;
    
  }

  function count() {

    return $this->provider->count($this->sql, $this->args);

  }

  function skip($skip) {

    $this->skip = $skip;
    return $this;

  }

  function rewind() {

    $this->getData();
    $this->position = 0;

  }

  function current() {

    return $this->rows[$this->position];

  }

  function key() {

    return $this->position;

  }

  function next() {

    ++$this->position;

  }

  function valid() {

    return isset($this->rows[$this->position]);

  }

  function sort($order) {

    if ($order) {
      $sql = ' ORDER BY ';
      foreach($order as $field => $direction) {
        $sql .= $field . ' ' . ($direction == 1?'ASC':'DESC') .', ';
      }
      $sql = rtrim($sql, ', ');
      $this->sql .= $sql;
    }

    return $this;

  }

}

class BrMySQLProviderTable {
  
  private $tableName;
  private $provider;

  function __construct(&$provider, $tableName) {

    $this->tableName = $tableName;
    $this->provider = $provider;

  }

  private function compileJoin($filter, $tableName, $fieldName, $link, &$joins, &$joinsTables, &$where, &$args) {

    foreach($filter as $joinTableName => $joinField) {
      if (!in_array($joinTableName, $joinsTables)) {
        $joinsTables[] = $joinTableName;
        if (strpos($fieldName, '.') === false) {
          $joins .= ' INNER JOIN '.$joinTableName.' ON '.$tableName.'.'.$fieldName.' = '.$joinTableName.'.'.$joinField;
        } else {
          $joins .= ' INNER JOIN '.$joinTableName.' ON '.$fieldName.' = '.$joinTableName.'.'.$joinField;        
        }
      } else {

      }
    }

  }

  private function compileExists($filter, $tableName, $fieldName, $link, &$joins, &$joinsTables, &$where, &$args) {

    $where .= $link.' EXISTS (';
    if (is_array($filter)) {
      if ($existsSql = br($filter, '$sql')) {
        $where .= str_replace('$', $tableName, $existsSql) . ')';
      }
    } else {
      $where .= str_replace('$', $tableName, $filter) . ')';
    }

  }

  private function compileFilter($filter, $tableName, $fieldName, $link, &$joins, &$joinsTables, &$where, &$args) {

    foreach($filter as $currentFieldName => $filterValue) {
      $currentFieldName = (string)$currentFieldName;
      if (strpos($currentFieldName, '.') === false) {
        $fname = $tableName.'.'.$currentFieldName;
      } else {
        $fname = $currentFieldName;
      }
      switch($currentFieldName) {
        // FUCKING BUG! 0 = '$and' //
        case '$and':
          $where .= $link . ' ( 1=1 ';
          $this->compileFilter($filterValue, $tableName, '', ' AND ', $joins, $joinsTables, $where, $args);
          $where .= ' ) ';
          break;
        case '$or':
          $where .= $link . ' ( 1=2 ';
          $this->compileFilter($filterValue, $tableName, '', ' OR ', $joins, $joinsTables, $where, $args);
          $where .= ' ) ';
          break;
        case '$exists':
          $this->compileExists($filterValue, $tableName, '', $link, $joins, $joinsTables, $where, $args);
          break;
        case '$join':
          $this->compileJoin($filterValue, $tableName, $fieldName, $link, $joins, $joinsTables, $where, $args);
          break;
        case '$in':
          $where .= $link . $tableName . '.' . $fieldName . ' IN (?@)';
          $args[] = br()->removeEmptyKeys($filterValue);
          break;
        case '$ne':
          $where .= $link . $tableName . '.' . $fieldName . ' != ?';
          $args[] = $filterValue;
          break;
        case '$eq':
          $where .= $link . $tableName . '.' . $fieldName . ' = ?';
          $args[] = $filterValue;
          break;
        case '$gt':
          $where .= $link . $tableName . '.' . $fieldName . ' > ?';
          $args[] = $filterValue;
          break;
        case '$gte':
          $where .= $link . $tableName . '.' . $fieldName . ' >= ?';
          $args[] = $filterValue;
          break;
        case '$lt':
          $where .= $link . $tableName . '.' . $fieldName . ' < ?';
          $args[] = $filterValue;
          break;
        case '$lte':
          $where .= $link . $tableName . '.' . $fieldName . ' <= ?';
          $args[] = $filterValue;
          break;
        default:
          if (is_array($filterValue)) {
            $this->compileFilter($filterValue, $tableName, $currentFieldName, $link, $joins, $joinsTables, $where, $args);
          } else {
            if (is_object($filterValue) && ($filterValue instanceof BrMySQLRegExp)) {
              $where .= $link.$fname.' REGEXP ?';
              $args[] = rtrim(ltrim($filterValue->getValue(), '/'), '/i');
            } else {
              if (!strlen($filterValue)) {
                $where .= $link.$fname.' IS NULL';
              } else {          
                $where .= $link.$fname.' = ?';
                $args[] = $filterValue;
              }
            }
          }
          break;
      }

    }
  }

  function find($filter = array(), $fields = array()) {

    $where = '';
    $joins = '';
    $joinsTables = array();
    $args = array();

    $filter = array('$and' => $filter);

    $this->compileFilter($filter, $this->tableName, '', ' AND ', $joins, $joinsTables, $where, $args);

    $sql = 'SELECT ';
    if ($fields) {
      foreach($fields as $field) {
        $sql .= $this->tableName.'.'.$field.',';
      }
      $sql = rtrim($sql, ',').' ';
    } else {
      $sql = 'SELECT '.$this->tableName.'.* ';
    }

    $sql .= ' FROM '.$this->tableName.$joins.' WHERE 1=1 '.$where;

    //$sql .= $where;
    return new BrMySQLProviderCursor($sql, $args, $this->provider);

  }
  
  function remove($filter) {

    $where = '';
    $joins = '';
    $joinsTables = array();
    $args = array();

    $filter = array('$and' => $filter);
    $this->compileFilter($filter, $this->tableName, '', ' AND ', $joins, $joinsTables, $where, $args);
    $sql = 'DELETE ';
    $sql .= ' FROM '.$this->tableName.$joins.' WHERE 1=1 '.$where;
    return $this->provider->runQuery($sql, $args);

  }
  
  function findOne($filter) {

    if ($rows = $this->find($filter)) {
      foreach($rows as $row) {
        return $row;
      }
    }
  }

  function save($values) {

    $fields_str = '';
    $values_str = '';

    $sql = 'UPDATE '.$this->tableName.' SET ';
    foreach($values as $field => $value) {
      if ($field != $this->provider->rowidField()) {
        $sql .= $field . ' = ?, ';
      }
    }
    $sql = rtrim($sql, ', ');  
    $sql .= ' WHERE ' . $this->provider->rowidField() . ' = ?';

    $args = array();  
    $key = null;
    foreach($values as $field => $value) {
      if ($field != $this->provider->rowidField()) {
        array_push($args, $value);
      } else {
        $key = $value;
      }
    }    
    array_push($args, $key);

    $this->provider->runQuery($sql, $args);
    
    return $values[$this->provider->rowidField()];

  }

  function insert(&$values) {

    $fields_str = '';
    $values_str = '';

    foreach($values as $field => $value) {
      $fields_str .= ($fields_str?',':'').$field;
      $values_str .= ($values_str?',':'').'?';
    }  
    $sql = 'INSERT INTO '.$this->tableName.' ('.$fields_str.') VALUES ('.$values_str.')';

    $args = array();  
    foreach($values as $field => $value) {
      array_push($args, $value);
    }
    
    $this->provider->runQuery($sql, $args);
    if ($newId = $this->provider->lastId()) {
      $values = $this->findOne(array($this->provider->rowidField() => $newId));
      return $newId;
    }
    
  }

}

class BrMySQLDBProvider extends BrGenericDBProvider {

  var $connection;

  function __construct($cfg) {

    $this->connect(br($cfg, 'hostname'), br($cfg, 'name'), br($cfg, 'username'), br($cfg, 'password'), $cfg);

  }

  function connect($hostName, $dataBaseName, $userName, $password, $cfg) {

    if (function_exists('mysql_pconnect') && defined('USE_PERSISTENT_DB_CONNECTION')) {
      $this->connection = mysql_pconnect($hostName, $userName, $password, true);
    } else {  
      $this->connection = mysql_connect($hostName, $userName, $password, true);
    }
      
    if (!$this->connection)
      if (br()->config()->get('db.connection_error_page'))
        br()->config()->set('db.connection_in_error', true);
      else
        throw new BrDataBaseException("Can't connect to database $dataBaseName");
    if (!mysql_select_db($dataBaseName, $this->connection))
      if (br()->config()->get('db.connection_error_page'))
        set_config('db.connection_in_error', true);
      else
        br()->panic("Can't select database $dataBaseName: ".$this->getLastError());

    if (br($cfg, 'charset')) {
      $this->runQuery("SET NAMES '".$cfg['charset']."'");
    }

    if (function_exists('mysql_get_server_info'))
      $this->version = mysql_get_server_info();

  }

  function table($name) {

    return new BrMySQLProviderTable($this, $name);

  }

  function command($command) {

    mysql_query($command);

  }

  function rowidValue($row, $fieldName = null) {
    
    if (is_array($row)) {
      return br($row, $fieldName?$fieldName:$this->rowidField());
    } else {
      return $row;
    }
    
  }
  
  function rowid($row, $fieldName = null) {
    
    if (is_array($row)) {
      return br($row, $fieldName?$fieldName:$this->rowidField());
    } else {
      return $row;
    }
    
  }
  
  function rowidField() {
    
    return 'id';
    
  }
  
  function regexpCondition($value) {
    
    return new BrMySQLRegExp($value);

  }

  function startTransaction() {
     
    mysql_query("START TRANSACTION", $this->connection);
     
  }

  function commitTransaction() {
     
    mysql_query("COMMIT", $this->connection);
     
  }

  function rollbackTransaction() {
     
    mysql_query("ROLLBACK", $this->connection);
     
  }
  

  function getLastError() {
     
    if (mysql_errno($this->connection)) {
      return mysql_errno($this->connection).": ".mysql_error($this->connection);
    }
     
  }

  function selectNext($query) { 

    $result = mysql_fetch_assoc($query);
    if (is_array($result)) {
      $result = array_change_key_case($result, CASE_LOWER);
    }
    return $result;
    
  }
  
  function runQuery($sql, $args = array(), $unbuffered = false) {

    if (count($args) > 0) {
      $sql = br()->placeholderEx($sql, $args, $error);
      if (!$sql) {
        $error .= '[INFO:SQL]'.$sql.'[/INFO]';
        throw new BrException($error);
      }
    }
    br()->log()->writeln($sql, "QRY");

    $query = mysql_query($sql, $this->connection);
    br()->log()->writeln('Query complete');
    
    if (!$query) {
      $error = $this->getLastError();
      if (!preg_match('/1329: No data/', $error)) {
        $error .= '[INFO:SQL]'.$sql.'[/INFO]';
        throw new BrException($error);
      }
    } else {
        // if ($duration > 1)
        //   $log->writeln("Query duration: ".number_format($duration, 3)." secs (SLOW!)", "LDR");
        // elseif ($duration > 0.01)
        //   $log->writeln("Query duration: ".number_format($duration, 3)." secs", "LDR");
        // else
        //   $log->writeln("Query duration: ".number_format($duration, 3)." secs", "DRN");
      // if ($this->log_mode && $this->debug_mode && $this->extended_debug && $this->support("explain_plan")) {
      //   if ($plan = $this->internal_query("EXPLAIN ".$sql, $args)) {
      //     $log->writeln("Query plan: ");
      //     while ($plan_row = $this->next_row($plan)) {
      //       if (safe($plan_row, "table")) 
      //         $log->writeln("table:".$plan_row["table"].
      //                       "; type:".$plan_row["type"].
      //                       "; keys:".$plan_row["possible_keys"].
      //                       "; key:".$plan_row["key"].
      //                       "; key_len:".$plan_row["key_len"].
      //                       "; ref:".$plan_row["ref"].
      //                       "; rows:".$plan_row["rows"].
      //                       "; extra:".$plan_row["extra"]
      //                     , "QPL");
      //     }
      //   }
      // }
    }

    return $query;

  }

  function getRow($sql, $args = array()) {

    $result = $this->selectNext($this->runQuery($sql, $args));
    if (is_array($result)) {
      return array_change_key_case($result, CASE_LOWER);
    } else {
      return $result;
    }

  }
  
  function getRows($sql, $args = array()) {

    $query = $this->runQuery($sql, $args);
    $result = array();
    if (is_resource($query)) {
      while($row = $this->selectNext($query)) {
        array_push($result, array_change_key_case($row, CASE_LOWER));
      }
    }
    
    return $result;

  }

  function getValues($sql, $args = array()) {

    $query = $this->runQuery($sql, $args);
    $result = array();
    if (is_resource($query)) {
      while($row = $this->selectNext($query)) {
        array_push($result, array_shift($row));  
      }
    }
    return $result;

  }

  function getValue($sql, $args = array()) {

    $result = $this->selectNext($this->runQuery($sql, $args));
    if (is_array($result)) {
      return array_shift($result);
    } else {
      return null;      
    }

  }

  function getCountSQL($sql) {
    
    $offset = 0; 
    if (preg_match('/(^[ \t\n]*|[ (])(SELECT)([ \n\r])/sim', $sql, $token, PREG_OFFSET_CAPTURE)) {
      $select_offset = $token[2][1];
      $offset = $select_offset + 6;
      $work_str = substr($sql, $offset);
      $in_select = 0;
      while (preg_match('/((^[ \t\n]*|[ (])(SELECT)([ \n\r])|([ \t\n])(FROM)([ \n\r]))/sim', $work_str, $token, PREG_OFFSET_CAPTURE)) {
        if (strtolower(@$token[6][0]) == 'from') {
          if ($in_select)
            $in_select--;
          else {
            $from_offset = $offset + $token[6][1];
            break; 
          }
          $inc = $token[6][1] + 4;
          $offset += $inc;
          $work_str = substr($work_str, $inc);
        }
        if (strtolower(@$token[3][0]) == 'select') {
          $in_select++;
          $inc = $token[3][1] + 6;
          $offset += $inc;
          $work_str = substr($work_str, $inc);
        }
      }
    }

    if (isset($select_offset) && isset($from_offset)) {
      $sql_start  = substr($sql, 0, $select_offset);
      $sql_finish = substr($sql, $from_offset + 4);
      $sql = $sql_start."SELECT COUNT(1) FROM".$sql_finish;
      $sql = preg_replace("/ORDER BY.+/sim", "", $sql, 1); 
      return $sql;
    } else
      return null;
      
  }

  function count($sql, $args = array()) { 

    $sql = str_replace("\n", " ", $sql);
    $sql = str_replace("\r", " ", $sql);
    $sql = preg_replace('~USE INDEX[(][^)]+[)]~i', '', $sql);
    $sql = preg_replace('~FORCE INDEX[(][^)]+[)]~i', '', $sql);
    if (!preg_match("/LIMIT/sim", $sql) && !preg_match("/FIRST( |$)/sim", $sql) && !preg_match("/GROUP( |$)/sim", $sql)) {
      if ($count_sql = $this->getCountSQL($sql)) {
        try {
          $query = $this->runQuery($count_sql, $args);
          if ($row = $this->selectNext($query)) {
            return array_shift($row);  
          } else  {
            return mysql_num_rows($this->runQuery($sql, $args)); 
          }
        } catch (Exception $e) {
          return mysql_num_rows($this->runQuery($sql, $args)); 
        }
      } else {
        return mysql_num_rows($this->runQuery($sql, $args)); 
      }
    } 
    return mysql_num_rows($this->runQuery($sql, $args)); 

  }

  function genericDataType($type) {

    switch (strtolower($type)) {
      case "date";
        return "date";
      case "datetime":
      case "timestamp":
        return "date_time";
      case "time";
        return "time";
      case "int":
      case "smallint":
      case "integer":
      case "int64":
      case "long":
      case "long binary":
      case "tinyint":
        return "int";
      case "real":
      case "numeric":
      case "double":
      case "float":
        return "real";
      case "string":
      case "text":
      case "blob":
      case "varchar":
      case "char":
      case "long varchar":
      case "varying":    
        return "text";
      default:
        return 'unknown';
        break;
    }

  }
  

  function fieldDefs($query) {

    $field_defs = array();
    $field_count = mysql_num_fields($query);
    for ($i=0; $i < $field_count; $i++) {
      $field_defs[strtolower(mysql_field_name($query, $i))] = array( "length" => mysql_field_len($query, $i)
                                                                   , "type"   => mysql_field_type($query, $i)
                                                                   , "flags"  => mysql_field_flags($query, $i)
                                                                   );
    }

    $field_defs = array_change_key_case($field_defs, CASE_LOWER);
    foreach($field_defs as $field => $defs) {
      $field_defs[$field]['genericType'] = $this->genericDataType($field_defs[$field]['type']);
    }

    return $field_defs;

  }

  function lastId() {
     
    return mysql_insert_id($this->connection);
     
  }

  function isEmptyDate($date) { 

    return (($date == "0000-00-00") or ($date == "0000-00-00 00:00:00") or !$date);

  }

  function getLimitSQL($sql, $from, $count) {

    if (!is_numeric($from)) {
      $from = 0;
    } else {
      $from = number_format($from, 0, '', '');
    }
    if (!is_numeric($count)) {
      $count = 0;
    } else {
      $count = number_format($count, 0, '', '');
    }
    return $sql.br()->placeholder(' LIMIT ?, ?', $from, $count);

  }

  function toDateTime($date) {

    return date("Y-m-d H:i:s", $date);

  }

  function getAffectedRowsAmount() {

    return mysql_affected_rows($this->connection);
    
  }

}

