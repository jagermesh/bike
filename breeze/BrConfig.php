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

    return $this;

  }  
  
  public function get($name = null, $default = null) {

    if ($name) {
      if ($this->isAttrExists($name)) {
        return $this->getAttr($name, $default);
      } else {
        $result = $this->getAttributes();
        $names = preg_split('~[.]~', $name);
        foreach($names as $name) {
          if (is_array($result)) {
            if (array_key_exists($name, $result)) {
              $result = $result[$name];
            } else {
              $result = $default;
              break;
            }
          } else {
            $result = $default;
            break;
          }
        }
        return $result;
      }
    } else {
      return $this->getAttributes();
    }

  }  

}

