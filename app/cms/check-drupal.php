<?php

if (!br()->config()->get('db')) {

  if (file_exists($fileName = dirname(dirname(dirname(dirname(__FILE__)))) . '/sites/default/settings.php')) {

    $file = br()->fs()->loadFromFile($fileName);

    $dbName = '';
    $dbUser = '';
    $dbPassword = '';
    $dbHost = 'localhost';
//    $dbCharset = '';

    if (preg_match("~array[^(]*?[(][^*]*?database['".'"'."][^=]*?=>[^'".'"'."]*?['".'"'."]([^'".'"'."]*)~ism", $file, $matches)) {
      $dbName = $matches[1];
    }

    if (preg_match("~array[^(]*?[(][^*]*?username['".'"'."][^=]*?=>[^'".'"'."]*?['".'"'."]([^'".'"'."]*)~ism", $file, $matches)) {
      $dbUser = $matches[1];
    }

    if (preg_match("~array[^(]*?[(][^*]*?password['".'"'."][^=]*?=>[^'".'"'."]*?['".'"'."]([^'".'"'."]*)~ism", $file, $matches)) {
      $dbPassword = $matches[1];
    }

    if (preg_match("~array[^(]*?[(][^*]*?host['".'"'."][^=]*?=>[^'".'"'."]*?['".'"'."]([^'".'"'."]*)~ism", $file, $matches)) {
      $dbHost = $matches[1];
    }

    br()
      ->config()
        ->set( 'db'
             , array( 'engine'   => 'mysql'
                    , 'hostname' => $dbHost
                    , 'name'     => $dbName
                    , 'username' => $dbUser
                    , 'password' => $dbPassword
                    //, 'charset'  => $dbCharset
                    ));

  }

}