<?php

/**
 * Project:     Breeze framework
 * Author:      Jager Mesh (jagermesh@gmail.com)
 *
 * @version 1.1.0.0
 * @package Breeze Core
 */

require_once(dirname(__FILE__).'/Br.php');

class BrObject {
  
  private $attributes = array();
  private $enabled = 0;
  
  function __construct() {
  }

  public function getAttr($name, $default = null, $saveDefault = false) {
  
    if ($this->isAttrExists($name)) {
      return $this->attributes[$name];    
    } else {
      if ($saveDefault) {
        $this->setAttr($name, $default);
        return $this->getAttr($name); 
      } else {
        return $default;
      }
    }
  
  }  
  
  public function setAttr($name, $value) {
  
    return $this->attributes[$name] = $value;
  
  }  
  
  public function clearAttr($name) {
  
    unset($this->attributes[$name]);
  	  
  }
  
  public function isAttrExists($name) {
  
    return array_key_exists($name, $this->attributes);
  
  }  
  
  public function getAttributes() {

    return $this->attributes;
    
  }

  public function setAttributes($attributes) {

    $this->attributes = $attributes;  
    
  }

  public function clearAttributes() {

    $this->attributes = array();  
    
  }

  public function enable($force = false) {

    if ($force) {
      $this->enabled = 0;      
    } else {
      $this->enabled--;      
    }
    
  }
  
  public function disable() {

    $this->enabled++;      
    
  }
  
  public function isEnabled() {

    return ($this->enabled == 0);

  }

  public static function GetInstance() {
  
    static $instances;
    
    $className = get_called_class();
    
    if (!isset($instances[$className])) { 
      $instances[$className] = new $className();
    }
    
    return $instances[$className];
  
  }
  
}

