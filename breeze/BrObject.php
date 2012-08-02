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
  
  protected $events = array();

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

  public function before($event, $func) {

    $events = preg_split('~[,]~', $event);
    foreach($events as $event) {
      $this->events['before:'.$event][] = $func;
    }

  }

  public function on($event, $func) {
    
    $events = preg_split('~[,]~', $event);
    foreach($events as $event) {
      $this->events[$event][] = $func;
    }

  }

  public function after($event, $func) {
    
    $events = preg_split('~[,]~', $event);
    foreach($events as $event) {
      $this->events['after:'.$event][] = $func;
    }

  }

  public function callEvent($event, &$context1, &$context2 = null, &$context3 = null, &$context4 = null, &$context5 = null) {

    $result = null;
    if ($events = br($this->events, $event)) {
      foreach($events as $func) {
        $result = $func($this, $context1, $context2, $context3, $context4, $context5);
      }
    }
    return $result;

  }

  
}

