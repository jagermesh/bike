<?php

if (!br()->config()->get('db')) {

  if (file_exists($fileName = dirname(dirname(dirname(dirname(__FILE__)))) . '/def.php')) {

    $file = br()->fs()->loadFromFile($fileName);

    $dbName = '';
    $dbUser = '';
    $dbPassword = '';
    $dbHost = 'localhost';
    $dbCharset = '';

    if (preg_match("~define[^'".'"'."]+?['".'"'."]DB_NAME['".'"'."][^'".'"'."]+?['".'"'."]([^'".'"'."]+)~ism", $file, $matches)) {
      $dbName = $matches[1];
    }

    if (preg_match("~define[^'".'"'."]+?['".'"'."]DB_USER['".'"'."][^'".'"'."]+?['".'"'."]([^'".'"'."]+)~ism", $file, $matches)) {
      $dbUser = $matches[1];
    }

    if (preg_match("~define[^'".'"'."]+?['".'"'."]DB_PASSWORD['".'"'."][^'".'"'."]+?['".'"'."]([^'".'"'."]+)~ism", $file, $matches)) {
      $dbPassword = $matches[1];
    }

    if (preg_match("~define[^'".'"'."]+?['".'"'."]DB_SERVER['".'"'."][^'".'"'."]+?['".'"'."]([^'".'"'."]+)~ism", $file, $matches)) {
      $dbHost = $matches[1];
    }

    if (preg_match("~define[^'".'"'."]+?['".'"'."]DB_CHARSET['".'"'."][^'".'"'."]+?['".'"'."]([^'".'"'."]+)~ism", $file, $matches)) {
      $dbCharset = $matches[1];
    }

    br()
      ->config()
        ->set( 'db'
             , array( 'engine'   => 'mysql'
                    , 'hostname' => $dbHost
                    , 'name'     => $dbName
                    , 'username' => $dbUser
                    , 'password' => $dbPassword
                    , 'charset'  => $dbCharset
                    ));

  }

}