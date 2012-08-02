<?php

require_once(dirname(dirname(__FILE__)).'/cms/identify.php');
require_once(__DIR__.'/queriesds.php');

// other datasources
br()->importLib('GenericDataSource');

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
         , $queriesDataSource
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
