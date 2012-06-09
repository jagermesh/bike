<?php

/**
 * Project:     Breeze framework
 * Author:      Jager Mesh (jagermesh@gmail.com)
 *
 * @version 1.1.0.0
 * @package Breeze Core
 */

require_once(dirname(__FILE__).'/BrGenericCacheProvider.php');

class BrMemCacheCacheProvider extends BrGenericCacheProvider {

  const DefaultHostName = "localhost";
  const DefaultPort = 11211;
  const DefaultExpirationPeriod = 300; // 5 minutes
  
  private $memCache = null;
  
  function __construct($cfg) {
    
    parent::__construct($cfg);

    $this->memCache = new Memcache();
    $hostname = br()->value($cfg, 'hostname', self::DefaultHostName);
    $posrt = br()->value($cfg, 'port', self::DefaultPort);
    if (!@$this->memCache->connect($hostname, $port)) {
      throw new BrException('Can not connect to MemCache server ' . $hostname . ':' . $port);
    }
    
  }
  
  public static function isSupported() {

    return class_exists('Memcache');
    
  }
  
  public function reset() {
  
    return $this->MemCache->flush();
  
  }
  
  public function get($name, $default, $saveDefault = false) {
             
    return $this->memCache->get($this->safeName($name));

  }
  
  public function set($name, $value, $expirationPeriod = null) {

    if (!$expirationPeriod) {
      $expirationPeriod = self::DefaultExpirationPeriod;
    }

    return $this->memCache->set($this->safeName($name), $value, false, $expirationPeriod);
      
  }

  function remove($name) {

    return $this->memCache->delete($this->safeName($name));

  }
    
}

