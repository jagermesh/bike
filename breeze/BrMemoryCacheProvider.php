<?php

/**
 * Project:     Breeze framework
 * Author:      Jager Mesh (jagermesh@gmail.com)
 *
 * @version 1.1.0.0
 * @package Breeze Core
 */

require_once(dirname(__FILE__).'/BrGenericCacheProvider.php');

class BrMemoryCacheProvider extends BrGenericCacheProvider {

  public function reset() {
  
    $this->clearAttributes();
  
  }
  
  public function get($name, $default, $saveDefault = false) {
             
    return $this->getAttr($name, $default, $saveDefault);

  }
  
  public function set($name, $value, $expirationSeconds = null) {

    return $this->setAttr($name, $value);
      
  }

  function remove($name) {

    $this->remove($name);

  }
  
}

