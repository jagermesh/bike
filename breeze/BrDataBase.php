<?php

/**
 * Project:     Breeze framework
 * Author:      Jager Mesh (jagermesh@gmail.com)
 *
 * @version 1.1.0.0
 * @package Breeze Core
 */

require_once(dirname(__FILE__).'/BrObject.php');
require_once(dirname(__FILE__).'/BrException.php');

class BrDataBaseException extends BrException {
}

class BrDataBase extends BrObject {

	private $dbProvider = null;
	
  public static function GetInstance($name = 'default') {
  
    static $instances = array();

    $instance = null;
    
    if (!isset($instances[$name])) { 

      if ($dbList = br()->config()->get('db')) {

        $dbConfig = br($dbList, $name, $dbList);

        br()->assert($dbConfig, 'Database [' . $name . '] not configured');

        $instance = new self;
        switch($dbConfig['engine']) {
          case "mysql":
            require_once(dirname(__FILE__).'/BrMySQLDBProvider.php');
            $instance->dbProvider = new BrMySQLDBProvider($dbConfig);
            break;
          case "mongodb":
            require_once(dirname(__FILE__).'/BrMongoDBProvider.php');
            $instance->dbProvider = new BrMongoDBProvider($dbConfig);
            break;
        }
        $instances[$name] = $instance;
      }

    } else {

      $instance = $instances[$name];

    }
        
    return $instance;
  
  }  
  
  function __construct() {
    
    parent::__construct();
        
  }

  public function table($name) {

    return $this->dbProvider->table($name);
    
  }

  public function command($command) {

    return $this->dbProvider->command($command);
    
  }

  public function getRow() {

    $args = func_get_args();
    $sql = array_shift($args);

    return $this->dbProvider->getRow($sql, $args);
    
  }

  public function getRows() {
  
    $args = func_get_args();
    $sql = array_shift($args);

    return $this->dbProvider->getRows($sql, $args);
    
  }  

  public function runQuery() {

    $args = func_get_args();
    $sql = array_shift($args);

    return $this->dbProvider->runQuery($sql, $args);

  }

  public function select() {

    $args = func_get_args();
    $sql = array_shift($args);

    return $this->dbProvider->runQuery($sql, $args);

  }

  public function selectNext($query) {

    return $this->dbProvider->selectNext($query);

  }

  
  public function getValues() {
  
    $args = func_get_args();
    $sql = array_shift($args);

    return $this->dbProvider->getValues($sql, $args);
    
  }

  public function getValue() {
  
    $args = func_get_args();
    $sql = array_shift($args);

    return $this->dbProvider->getValue($sql, $args);
    
  }

  public function getCachedValue() {
  
    $args = func_get_args();
    $sql = array_shift($args);
    $cacheTag = 'sql:' . $sql . serialize($args);
    $result = br()->cache()->get($cacheTag);
    if (!$result) {
      $result = $this->dbProvider->getValue($sql, $args);
      br()->cache()->set($cacheTag, $result);
    }
    return $result;
    
  }

  public function startTransaction() {
  
    return $this->dbProvider->startTransaction();
    
  }
  
  public function commitTransaction() {
  
    return $this->dbProvider->commitTransaction();
    
  }
  
  public function rollbackTransaction() {
  
    return $this->dbProvider->rollbackTransaction();
    
  }
  
  public function ignoreErrors() {
  
    $this->disable();
    
  }
  
  public function restoreErrors() {
  
    $this->enable();
    
  }
    
  function rowidValue($row, $fieldName = null) {
    
    return $this->dbProvider->rowidValue($row, $fieldName);
    
  }
  
  function rowid($row, $fieldName = null) {
    
    return $this->dbProvider->rowid($row, $fieldName);
    
  }
  
  function rowidField() {
    
    return $this->dbProvider->rowidField();
    
  }

  function regexpCondition($value) {

    return $this->dbProvider->regexpCondition($value);

  }

  public function now() {
  
    return $this->dbProvider->now();
    
  }
  
  public function getLimitSQL($sql, $skip, $limit) {
  
    return $this->dbProvider->getLimitSQL($sql, $skip, $limit);
    
  }
  
  public function count($sql) {
  
    return $this->dbProvider->count($sql);
    
  }

  public function getAffectedRowsAmount() {

    return $this->dbProvider->getAffectedRowsAmount();
    
  }

  
}

