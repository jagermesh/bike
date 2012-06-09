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

class BrExceptionDataObject extends BrException {

}

class BrDataObject extends BrObject {

  private $tableName = '';
  
  protected $primaryKey = 'id';
  protected $uniqueKey = array();
   
  function __construct($tableName) {
  
    $this->tableName = $tableName;
    
  }
  
  public function keyValue() {
    
    if ($this->primaryKey) {
      return $this->get($this->primaryKey);
    } else {
      throw new BrExceptionDataObject('Primary key not defined');
    }
    
  }
  
  public function load($row) {
  
    $this->setData($row);
    return $this->keyValue();
    
  }

  public function save($ignoreErrors = false) {
  
    $doUpdate = false;
    $doInsert = false;

    if ($this->keyValue()) { // key exists, so updating
      $doUpdate = true;
    } else
    if ($this->uniqueKey) {
      $filter = array();
      foreach($this->uniqueKey as $fieldName) {
        $filter[$fieldName] = $this->get($fieldName);
      }
      $row = br()->db()->getRow($this->tableName, $filter);
      if ($row) {
        if ($ignoreErrors) {
          $this->load($row); // reload without saving, TODO: check new/modified fiels, modify only modified ones
        } else {
          throw new BrExceptionDataObject('Unique key violation');
        }
      } else {
        $doInsert = true;
      }
    } else {
      $doInsert = true;
    }
    
    if ($doUpdate) {
      $this->doBeforeSave();
      $this->doBeforeUpdate();
      br()->db()->update($this->tableName, $this->getData(), $this->keyValue());
      $this->doAfterUpdate();
      $this->doAfterSave();
    } else
    if ($doInsert) {
      $this->doBeforeSave();
      $this->doBeforeInsert();
      $this->set($this->primaryKey, br()->db()->insert($this->TableName, $this->getData()));
      $this->setData(br()->db()->row($this->TableName, $this->keyValue()));
      $this->doAfterInsert();
      $this->doAfterSave();
    }
    
    return $this->key();
    
  }
  
  public function copy($obj) {
  
    $this->setData($Obj->getData());
    $this->unSet($this->primaryKey);
    
  }
  
  public function delete() {
  
    br()->db()->delete($this->tableName, $this->keyValue());
    $this->setData();
    
  }
  
  protected function doBeforeSave() {
  }
  
  protected function doAfterSave() {
  }
  
  protected function doBeforeInsert() {
  }
  
  protected function doAfterInsert() {
  }
  
  protected function doBeforeUpdate() {
  }
  
  protected function doAfterUpdate() {
  }
  
  // Static
  static function put($values, $ignoreErrors = false) {
  
    $className = get_called_class();
    
    $object = new $className();
    foreach($values as $name => $value) {
      $object->set($name, $value);
    }
    $object->save($ignoreErrors);
    
    return $object;
                      
  }  
  
  static function findByKeyValue($keyValue) {

    $class = get_called_class();
    $object = new $class();
    $filter = array($object->primaryKey => $keyValue);
    unset($object);
    
    return $class::Find($filter);
    
  }
  
  static function find($filter = array()) {
  
    $class = get_called_class();
    $object = new $class();
    
    if ($object->setData(br()->db()->getRow($object->tableName, $filter)) {
      return $object;
    } else {
      unset($object);
      return null;
    }
    
  }
    
}

