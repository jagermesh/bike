<?php

class MySQLDictionary {
  
  static function getRecordName($rowId, $tableName) {

    $nameFields = br()->config()->get('nameFields', array('name', 'description'));
    $fields = br()->db()->getValues("DESC ".$tableName);
    foreach($nameFields as $nameField) {
      $nameField = trim($nameField);
      $names = preg_split('#[, ]#', $nameField);
      $found = 0;
      foreach($names as $name) {
        if (in_array($name, $fields)) {
          $found++;
        }
      }
      if ($found && ($found == count($names))) {
        $sql = 'SELECT CONCAT(';
        foreach($names as $name) {
          $sql .= 'IFNULL(' . $name . ',"")," ",';
        }          
        $sql = rtrim($sql, ',');
        $sql .= ') FROM ' . $tableName . ' WHERE ' . br()->db()->rowidField() . ' = ?';
        $value = br()->db()->getValue($sql, $rowId);
        return substr(trim(br()->html2text($value)), 0, 80);
      }
    }
    return null;

  }

  static function getForeignKeys($tableName) {

    if ($dbSettings = br()->config()->get('db')) {
      if ($schemaName = br($dbSettings,'name')) {
        $foreignKeys = br()->cache()->get('schema:'.$schemaName.':'.$tableName);
        if ($foreignKeys) {

        } else {
          $foreignKeys = array();
          $rows = br()->db()->getRows('SELECT column_name, referenced_table_name, referenced_column_name FROM information_schema.key_column_usage WHERE table_schema = ? AND table_name = ? AND constraint_name <> "PRIMARY" AND referenced_table_name IS NOT NULL', $schemaName, $tableName);
          foreach($rows as $row) {
            $foreignKeys[$row['column_name']] = array( 'table' => $row['referenced_table_name']
                                                     , 'field' => $row['referenced_column_name']
                                                     );
          }
          br()->cache()->set('schema:'.$schemaName.':'.$tableName, $foreignKeys);
        }

        return $foreignKeys;
        
      }
    }

  }

}