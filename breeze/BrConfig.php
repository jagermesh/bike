<?php

/**
 * Project:     Breeze framework
 * Author:      Jager Mesh (jagermesh@gmail.com)
 *
 * @version 1.1.0.0
 * @package Breeze Core
 */

require_once(dirname(__FILE__).'/BrSingleton.php');

class BrConfig extends BrSingleton {

  public function set($name, $value) {

    $this->setAttr($name, $value);

  }  
  
  public function get($name = null, $default = null) {

    return $this->getAttr($name, $default);

  }  

}

