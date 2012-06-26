<?php

require_once(dirname(dirname(__FILE__)).'/cms/identify.php');

// other datasources
br()->importLib('GenericDataSource');

$query = new BrGenericDataSource('query');

$query->on('insert', function($dataSource, $row) { 

  if ($sql = br($row, 'sql')) {

    $row['isSelect'] = (preg_match('~^[ ]*?SELECT~ism', $row['sql']) > 0);
    $row['isLimited'] = (preg_match('~LIMIT[ ]*?[0-9]+~ism', $row['sql']) > 0);

    $hash = md5(json_encode($row));

    br()->session()->set($hash, $row);

    return array('hash' => $hash, 'isSelect' => $row['isSelect'], 'isLimited' => $row['isLimited']);

  } 

  throw new Exception('Empty SQL');

});

$query->on('select', function($dataSource, $filter, $transient, $options) { 

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

              $result = br()->db()->count($sql);

            } else {

              if (!preg_match('~LIMIT.*[0-9]+~ism', $sql)) {
                $sql = br()->db()->getLimitSQL($sql, br($filter, '__skip', 0), br($filter, '__limit', 20));
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
                    if (!strlen($value)) { $value = ''; }
                    $resultRow['cells'][] = $value;
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
                    $resultRow['cells'][] = $value;
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
                $result['rows'][] = array('row' => array('cells' => array($s)));
              }
            }

          }

          br()->response()->sendJSON($result);
        }
      }
    }

    throw new Exception('Empty SQL');

  } catch (Exception $e) {
    $message = $e->getMessage();
    $message = preg_replace('/\[INFO:([^]]+)\](.+)\[\/INFO\]/ism', '', $message);
//    $message = preg_replace('/\[INFO:([^]]+)\](.+)\[\/INFO\]/ism', '', $message);
    throw new Exception($message);
  }

});

$libraryQueries = new BrGenericDataSource('savedQueries');

$libraryQueries->on('select', function($dataSource, $row) { 

  $result = array();

  $path = br()->config()->get('libraryQueriesPath');

  try {
    $fileName = $path . 'library.json';
    $queries = array();
    if (file_exists($fileName)) {
      if ($data = br()->fs()->loadFromFile($fileName)) {
        if ($resultsArray = json_decode($data, true)) {
          foreach($resultsArray as $row) {
            $row['rowid'] = md5($row['name']);
            $result[] = $row;
          }
        }
      }
    }
  } catch (Exception $e) {
    //throw new Exception('To be able to save queries folder "' . $path . '" must be writeable');
  }

  if (!$result) {
    $result = array();
  }

  return $result;

});

$savedQueries = new BrGenericDataSource('savedQueries');

$savedQueries->on('select', function($dataSource, $row) { 

  $result = array();

  $path = br()->config()->get('savedQueriesPath');

  try {
    br()->fs()->createDir($path)->checkWriteable($path);
    $fileName = $path . 'saved.json';
    $queries = array();
    if (file_exists($fileName)) {
      if ($data = br()->fs()->loadFromFile($fileName)) {
        if ($resultsArray = json_decode($data, true)) {
          foreach($resultsArray as $row) {
            $row['rowid'] = md5($row['name']);
            $result[] = $row;
          }
        }
      }
    }
  } catch (Exception $e) {
    //throw new Exception('To be able to save queries folder "' . $path . '" must be writeable');
  }

  if (!$result) {
    $result = array();
  }

  return $result;

});

$savedQueries->on('insert', function($dataSource, $row) { 

  $path = br()->config()->get('savedQueriesPath');

  try {
    br()->fs()->createDir($path)->checkWriteable($path);
    $fileName = $path . 'saved.json';
    $queries = array();
    if (file_exists($fileName)) {
      if ($data = br()->fs()->loadFromFile($fileName)) {
        $queries = json_decode($data, true);
      }
    }
    $queries[$row['name']] = $row;
    $data = json_encode($queries);
    br()->fs()->saveToFile($fileName, $data);
    $row['rowid'] = md5($row['name']);
    return $row;
  } catch (Exception $e) {
    throw new Exception('To be able to save queries folder "' . $path . '" must be writeable. Check About section, please.');
  }

});

$savedQueries->on('remove', function($dataSource, $row) { 

  $path = br()->config()->get('savedQueriesPath');
  try {
    br()->fs()->createDir($path)->checkWriteable($path);
    $rowid = $row['rowid'];
    $fileName = $path . 'saved.json';
    $queries = array();
    if (file_exists($fileName)) {
      if ($data = br()->fs()->loadFromFile($fileName)) {
        $queries = json_decode($data, true);
      }
    }
    $result = array();
    $deleted = array();
    foreach($queries as $name => $row) {
      if (md5($name) != $rowid) {
        $result[$name] = $row;
      } else {
        $deleted = $row;
      }
    }
    $data = json_encode($result);
    br()->fs()->saveToFile($fileName, $data);
    return $deleted;
  } catch (Exception $e) {
    throw new Exception('To be able to remove queries folder "' . $path . '" must be writeable. Check About section, please.');
  }

});



br()->importLib('RESTBinder');

$rest = new BrRESTBinder();
$rest
  ->route( '/api/query'
         , $query
         , array( 'filterMappings' => array( array( 'get'    => 'hash'
                                                  , 'fields' => 'hash'
                                                  )
                                           )
                , 'allowEmptyFilter' => true
                )
         )
  ->route( '/api/libraryQueries'
         , $libraryQueries
         , array( 'filterMappings' => array( array( 'get'    => 'keyword'
                                                  , 'fields' => 'keyword'
                                                  )
                                           )
                , 'allowEmptyFilter' => true
                )
         )
  ->route( '/api/savedQueries'
         , $savedQueries
         , array( 'filterMappings' => array( array( 'get'    => 'keyword'
                                                  , 'fields' => 'keyword'
                                                  )
                                           )
                , 'allowEmptyFilter' => true
                )
         )
;
