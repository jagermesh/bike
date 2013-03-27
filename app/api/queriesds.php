<?php

br()->importLib('GenericDataSource');

$queriesDataSource = new BrGenericDataSource('query');

$queriesDataSource->on('insert', function($dataSource, $row) { 

  if ($sql = br($row, 'sql')) {

    $normalizedQuery = trim(preg_replace('#/[*].*?[*]/#', '', preg_replace("#[\n\r]]#", '', $sql)));
    $row['isSelect'] = (preg_match('#^[ ]*?SELECT#i', $normalizedQuery) > 0);
    $row['isLimited'] = (!$row['isSelect']) || (preg_match('#(^[ ]*?SELECT.*?COUNT.*?[(].*?FROM|LIMIT[ ]*?[0-9]+)#ism', $normalizedQuery) > 0);

    $hash = md5(json_encode($row));

    br()->session()->set($hash, $row);

    return array('hash' => $hash, 'isSelect' => $row['isSelect'], 'isLimited' => $row['isLimited']);

  } 

  throw new Exception('Empty SQL');

});

$queriesDataSource->on('select', function($dataSource, $filter, $transient, $options) { 

  if (!br()->db()) {
    throw new Exception('Oops, database not configured. Check About section, please.');
  }

  try {

    if ($hash = br($filter, 'hash')) {

      if ($filter = br()->session()->get($hash)) {

        if ($sql = br($filter, 'sql')) {

          $sql = rtrim(trim($sql), ';');

          $isSelect = $filter['isSelect'];

          $header = array();
          $result = array();

          if ($isSelect) {

            if (br($options, 'result') == 'count') {

              $result = br()->db()->getRowsAmount($sql);

            } else {

              if (!$filter['isLimited'] && br($filter, '__limit')) {
                $sql = br()->db()->getLimitSQL($sql, br($filter, '__skip', 0), br($filter, '__limit', 20));
              }

              require_once(dirname(__DIR__).'/MySQLDictionary.php');

              $foreignKeys = array();
              if (preg_match('#FROM[ ]*([_a-z0-9]+)#i', $sql, $matches)) {
                $tableName = $matches[1];
                $foreignKeys = MySQLDictionary::getForeignKeys($tableName);
              }

              $first = true;
              if ($rows = br()->db()->getRows($sql)) {
                foreach($rows as $row) {
                  if ($first) {
                    $resultRow = array();
                    foreach($row as $name => $value) {
                      $resultRow['cells'][] = $name;
                    }
                    $result['rows'][] = array('header' => $resultRow);
                    $first = false;
                  }
                  $resultRow = array();
                  foreach($row as $name => $value) {
                    $hint = '';
                    if (!strlen($value)) { 
                      $value = ''; 
                    } else
                    if ($foreignKey = br($foreignKeys, $name)) {
                      if ($recordName = MySQLDictionary::getRecordName($value, $foreignKey['table'])) {
                        $hint = $recordName;
                      }
                    }
                    $resultRow['cells'][] = array('value' => $value, 'hint' => $hint);
                  }
                  $result['rows'][] = array('row' => $resultRow);
                }
              }

            }

          } else {

            $queries = preg_split('#[;]#', $sql);
            $multiple = count($queries);
            foreach($queries as $sql) {
              $first = true;
              if ($rows = br()->db()->getRows($sql)) {
                foreach($rows as $row) {
                  if ($first) {
                    $resultRow = array();
                    foreach($row as $name => $value) {
                      $resultRow['cells'][] = $name;
                    }
                    $result['rows'][] = array('header' => $resultRow);
                    $first = false;
                  }
                  $resultRow = array();
                  foreach($row as $name => $value) {
                    if (!strlen($value)) { $value = ''; }
                    $resultRow['cells'][] = array('value' => $value);
                  }
                  $result['rows'][] = array('row' => $resultRow);
                }
              } else {
                if ($first) {
                  // $result['headers'][] = array('cells' => array('title' => 'Result(s)'));
                  $first = false;
                }
                $s = 'Query executed successfully.';
                $s .= ' ' . br()->db()->getAffectedRowsAmount() . ' row(s) affected.';
                $result['rows'][] = array('row' => array('cells' => array(array('value' => $s))));
              }
            }

          }

          return $result;
        }
      }
    }

    throw new Exception('Empty SQL');

  } catch (Exception $e) {
    $message = $e->getMessage();
    $message = preg_replace('/\[INFO:([^]]+)\](.+)\[\/INFO\]/ism', '', $message);
    throw new Exception($message);
  }

});
