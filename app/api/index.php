<?php

require_once(dirname(dirname(__FILE__)).'/cms/identify.php');

// other datasources
br()->importLib('DataSource');

$query = new BrDataSource('query');
$query->on('select', function($dataSource, $filter, $transient, $options) { 

  if (!br()->db()) {
    throw new Exception('Oops, database not configured. Check About section, please.');
  }

  try {
    if ($sql = br($filter, 'sql')) {    

      if (br($options, 'result') == 'count') {
        $result = br()->db()->count($sql);
      } else {
        $header = array();
        $result = array();

        if (!preg_match('~LIMIT.*[0-9]+~ism', $sql) && preg_match('~[ ]*SELECT~ism', $sql)) {
          $sql = br()->db()->getLimitSQL($sql, br($options, 'skip', 0), br($options, 'limit', 20));
        }

        if ($rows = br()->db()->getRows($sql)) {
          $first = true;
          foreach($rows as $row) {
            $resultRow = array();
            foreach($row as $name => $value) {
              if ($first) {
                $header['cells'][] = array('title' => $name);
              }
              if (!$value) {
                $value = '';
              }
              $resultRow['cells'][] = $value;
            }
            if ($first) {
              $result['headers'][] = $header;
              $first = false;
            }
            $result['rows'][] = $resultRow;
          }
        }

      }
      br()->response()->sendJSON($result);

    }

    throw new Exception('Empty SQL');

  } catch (Exception $e) {
    $message = $e->getMessage();
    $message = preg_replace('/\[INFO:([^]]+)\](.+)\[\/INFO\]/ism', '', $message);
    throw new Exception($message);
  }

});

$libraryQueries = new BrDataSource('savedQueries');

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

$savedQueries = new BrDataSource('savedQueries');

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
    return $row;
  } catch (Exception $e) {
    throw new Exception('To be able to save queries folder "' . $path . '" must be writeable. Check About section, please.');
  }

});

$savedQueries->on('remove', function($dataSource, $rowid) { 

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
         , array( 'filterMappings' => array( array( 'get'    => 'sql'
                                                  , 'fields' => 'sql'
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
