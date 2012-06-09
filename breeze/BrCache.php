<?php

/**
 * Project:     Breeze framework
 * Author:      Jager Mesh (jagermesh@gmail.com)
 *
 * @version 1.1.0.0
 * @package Breeze Core
 */

require_once(dirname(__FILE__).'/BrObject.php');

class BrCache extends BrObject {

  private $cacheProvider = null;
  
  public static function GetInstance($name = null) {
  
    static $instances = array();
    
    $cacheConfig = array();

    if (!$name) {
      $name = 'memory';
      $cacheConfig = array('engine' => $name);
    } else {
      $cacheList = br()->config()->get('cache');
      if ($cacheList) {
        $cacheConfig = br($cacheList, $name);
      }
      if (!$cacheConfig) {
        $cacheConfig = json_decode($name);
        if ($cacheConfig) {
          $cacheConfig = get_object_vars($cacheConfig);
        }
      }
    }

    $instance = null;
    
    if ($cacheConfig) {
      if (!isset($instances[$name])) { 
        $instance = new self;
        switch($cacheConfig['engine']) {
          case "memcache":
            require_once(dirname(__FILE__).'/BrMemCacheCacheProvider.php');
            $instance->cacheProvider = new BrMemCacheCacheProvider($cacheConfig);
            break;
          case "memory":
            require_once(dirname(__FILE__).'/BrMemoryCacheProvider.php');
            $instance->cacheProvider = new BrMemoryCacheProvider($cacheConfig);
            break;
          case "apc":
            require_once(dirname(__FILE__).'/BrAPCCacheProvider.php');
            $instance->cacheProvider = new BrAPCCacheProvider($cacheConfig);
            break;
          case "xcache":
            require_once(dirname(__FILE__).'/BrXCacheCacheProvider.php');
            $instance->cacheProvider = new BrXCacheCacheProvider($cacheConfig);
            break;
          default:
            throw new BrException('Unknown cache requested: ' . $name);
            break;
        }
        $instances[$name] = $instance;
      } else {
        $instance = $instances[$name];
      }
    } else {
      throw new BrException('Unknown cache requested');
    }
    
    return $instance;
  
  }  

  public static function isSupported($engine) {
  
    switch ($engine) {
      case "memcache":
        require_once(dirname(__FILE__).'/BrMemCacheCacheProvider.php');
        return BrMemCacheCacheProvider::isSupported();
      case "apc":
        require_once(dirname(__FILE__).'/BrAPCCacheProvider.php');
        return BrAPCCacheProvider::isSupported();
      case "xcache":
        require_once(dirname(__FILE__).'/BrXCacheCacheProvider.php');
        return BrXCacheCacheProvider::isSupported();
      default:
        return true;
        break;
    }
    
  }
  
  public function reset() {
  
    $this->cacheProvider->reset();
  
  }
  
  public function get($name, $default = null, $saveDefault = false) {

    return $this->cacheProvider->get($name, $default, $saveDefault);
        
  }
  
  public function set($name, $value) {

    return $this->cacheProvider->set($name, $value);
        
  }
  
  public function remove($name) {

    return $this->cacheProvider->remove($name);

  }

}

