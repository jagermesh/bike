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

    return br($_SESSION, $name, $default);
  
  }

  public function set($name, $value) {
   
    $name = $this->tag.':'.$name;

    $_SESSION[$name] = $value;
  
  }
  
  public function clear($name) {
   
    $name = $this->tag.':'.$name;

    unset($_SESSION[$name]);
  
  }
  
}

