<?php

/**
 * Project:     Breeze framework
 * Author:      Jager Mesh (jagermesh@gmail.com)
 *
 * @version 1.1.0.0
 * @package Breeze Core
 */

require_once(dirname(__FILE__).'/BrSingleton.php');
require_once(dirname(__FILE__).'/BrException.php');
require_once(dirname(__FILE__).'/BrFileRenderer.php');

class BrApplication extends BrSingleton {

  private $renderer;

  function __construct() {

    parent::__construct();

    br()->log()->writeLn(str_pad('*', 80, '*'));
    br()->log()->writeLn('Application start', 'STP');
    br()->log()->writeLn('PHP Version: '.phpversion(), 'INF');
    br()->log()->writeLn('Server Name: '.br($_SERVER, 'SERVER_NAME'), 'INF');
    if (function_exists('posix_getpid')) {
      br()->log()->writeLn('PID: '.posix_getpid(), 'INF');
    }
    br()->log()->writeLn(str_pad('*', 80, '*'));

    if (!br()->isConsoleMode()) {
      br()->log()->writeLn('Request: [' . br()->request()->method() . '] ' . br()->request()->url());
      br()->log()->writeLn('Client IP: ' . br()->request()->clientIP());
    }

  }

  function main() {

    br()->log()->writeLn('Main start', 'STP');

    br()
      ->request()
        ->route('breeze-assets.js', function() {
            br()->assetsCache()->send('js');
          })
    ;

    br()->auth()->checkLogin(false);      

    $request = br()->request();
    $scriptName = $request->scriptName();

    $asis = br()->atBasePath($request->relativeUrl().$scriptName);

    if (preg_match('/[.]htm[l]?$/', $asis)) {
      if (file_exists($asis)) {
        br()->renderer()->display($asis);
        return;
      }
    }
  
    if (preg_match('/[.]html$/', $scriptName)) {
      $scriptName = 'index.php';
    } 

    $targetScripts = array();
    // as is     
    $targetScripts[] = br()->atAppPath($request->relativeUrl().$scriptName);
    // if script is html - try to find regarding php
    $path = br()->request()->relativeUrl();
    if ($path) {
      while(($path = dirname($path)) != '.') {
        $targetScripts[] = br()->atAppPath($path.'/'.$scriptName);   
      }
    }
    // try to look for this script at base application path
    $targetScripts[] = br()->atAppPath($scriptName);
    // last chance - look for special 404.php file
    $targetScripts[] = br()->atAppPath('404.php');
    // run default routing file
    $targetScripts[] = br()->atAppPath('index.php');

    $controllerFile = null;
    foreach($targetScripts as $script) {
      if (br()->fs()->fileExists($script)) {
        $controllerFile = $script;
        break;
      }
    }

    br()->log()->writeLn('Main end', 'STP');

    register_shutdown_function(array(&$this, "end"));

    if ($controllerFile) {
      br()->log()->writeLn('Contoller: '.$controllerFile);
      br()->import($controllerFile);      
    }

  }

  function end() {
    br()->log()->writeLn('Application finish', 'STP');    
  }

}

