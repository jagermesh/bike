<?php

/**
 * Project:     Breeze framework
 * Author:      Jager Mesh (jagermesh@gmail.com)
 *
 * @version 1.1.0.0
 * @package Breeze Core
 */

require_once(dirname(__FILE__).'/BrSingleton.php');

class BrSession extends BrSingleton {

  private $tag = '';

  function __construct() {  

    $this->tag = md5(__FILE__);
      
    parent::__construct();
        
  }

  public function get($name, $default = null) {
   
    $name = $this->tag.':'.$name;

    if (isset($_SESSION)) {
      return br($_SESSION, $name, $default);
    } else {
      return null;
    }
  
  }

  public function set($name, $value) {
   
    $name = $this->tag.':'.$name;

    if (isset($_SESSION)) {
      $_SESSION[$name] = $value;
    }
  
  }
  
  public function clear($name) {
   
    $name = $this->tag.':'.$name;

    if (isset($_SESSION)) {
      unset($_SESSION[$name]);
    }
  
  }
  
}

