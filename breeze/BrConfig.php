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

    if ($name) {
      $names = preg_split('~[.]~', $name);
      $result = null;
      $first = true;
      foreach($names as $name) {
        if ($first) {
          $result = $this->getAttr($name, $default);
          $first = false;
        } else {
          $result = br($result, $name);
        }
      }
      return $result;
    } else {
      return $this->getAttributes();
    }

  }  

}

