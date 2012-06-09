<?php

/**
 * Project:     Breeze framework
 * Author:      Jager Mesh (jagermesh@gmail.com)
 *
 * @version 1.1.0.0
 * @package Breeze Core
 */

require_once(dirname(__FILE__).'/BrGenericCacheProvider.php');

class BrXCacheCacheProvider extends BrGenericCacheProvider {

  public static function isSupported() {

    return extension_loaded('apc');
    
  }
  
  public function reset() {
  
    xcache_clear_cache(XC_TYPE_VAR, 0);
  
  }
  
  public function get($name, $default, $saveDefault = false) {
             
    $value = xcache_get($this->safeName($name));
    if ($value === false) {
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
    
    if (!xcache_isset($this->safeName($name))) {
      xcache_set($this->safeName($name), $value, $expirationPeriod);
    }

  }

  function remove($name) {

    return xcache_unset($this->safeName($name));

  }
    
}

