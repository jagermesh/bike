<?php

/**
 * Project:     Breeze framework
 * Author:      Jager Mesh (jagermesh@gmail.com)
 *
 * @version 1.1.0.0
 * @package Breeze Core
 */

require_once(dirname(__FILE__).'/BrGenericCacheProvider.php');

class BrAPCCacheProvider extends BrGenericCacheProvider {
 
  public static function isSupported() {

    return extension_loaded('apc');
    
  }
  
  public function reset() {
  
    return apc_clear_cache('user');
  
  }
  
  public function get($name, $default, $saveDefault = false) {
             
    $value = apc_fetch($this->safeName($name));
    if ($value === FALSE) { 
      if ($saveDefault) {
        $this->set($name, $default);
      }
      return $default; 
    } 
    return $value;

  }
  
  public function set($name, $value, $expirationPeriod = null) {

    if (!$expirationPeriod) {
      $expirationPeriod = self::DefaultExpirationPeriod;
    }
    
    return apc_store($this->safeName($name), $value, $expirationPeriod);
     
  }

  function remove($name) {

    return apc_delete($this->safeName($name));

  }
    
}

