<?php

/**
 * Project:     Breeze framework
 * Author:      Jager Mesh (jagermesh@gmail.com)
 *
 * @version 1.1.0.0
 * @package Breeze Core
 */

require_once(dirname(__FILE__).'/BrGenericDBProvider.php');

class BrMongoDBProvider extends BrGenericDBProvider {

  function __construct($cfg) {

    $this->connection = new Mongo();
    $this->database = $this->connection->{$cfg['name']};

  }

  function table($name) {

    return $this->database->{$name};

  }
  
  function command($command) {

    return $this->database->command($command);

  }
  
  function rowidValue($row) {
    
    if (is_array($row)) {
      return (string)$row['_id'];
    } else {
      return (string)$row;
    }
    
  }
  
  function rowid($row) {
    
    if (is_array($row)) {
      return $row['_id'];
    } else {
      if (!is_object($row)) {
        return new MongoId($row);
      } else {
        return $row;
      }
    }
    
  }
  
  function rowidField() {
    
    return '_id';
    
  }
  
  function regexpCondition($value) {
    
    return new MongoRegex("/.*".$value.".*/i");

  }

  function toDateTime($date) {

    return new MongoDate($date);

  }
  
}

