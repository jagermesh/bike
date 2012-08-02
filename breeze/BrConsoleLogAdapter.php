<?php

/**
 * Project:     Breeze framework
 * Author:      Jager Mesh (jagermesh@gmail.com)
 *
 * @version 1.1.0.0
 * @package Breeze Core
 */

require_once(dirname(__FILE__).'/BrGenericLogAdapter.php');

class BrConsoleLogAdapter extends BrGenericLogAdapter {

  function __construct() {

    parent::__construct();

  }

  function write($logText = null, $group = null, $initTime = 0 , $time = 0, $logLevel = 0, $newLine = true) {

    if (($group != 'QRY') && ($group != 'SEP')) {
      $message = str_repeat(' ', $logLevel*2).$logText;
      $message .= "\n";

      echo($message);
    }
    
  }

}

