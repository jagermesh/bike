<?php

/**
 * Project:     Breeze framework
 * Author:      Jager Mesh (jagermesh@gmail.com)
 *
 * @version 1.1.0.0
 * @package Breeze Core
 */

require_once(dirname(__FILE__).'/BrGenericLogAdapter.php');

class BrFileLogAdapter extends BrGenericLogAdapter {

  private $filePointer = null;

  function __construct($filePath, $fileName = null) {

    if (!$filePath) {
      $filePath = dirname(dirname(__FILE__)).'/_logs/';
    }

    $filePath = rtrim($filePath, '/').'/';

    $date = @strftime('%Y-%m-%d');
    $hour = @strftime('%H');
    $filePath .= $date.'/';

    if (br()->isConsoleMode()) {
      $filePath .= br()->scriptName().'/';
    } else {
      $filePath .= br()->request()->clientIP().'/';
    }

    $filePath = br()->fs()->normalizePath($filePath);

    if (!$fileName) {
      $fileName = $date.'-';
      if (br()->isConsoleMode()) {

      } else {
        $fileName .= br()->request()->clientIP().'-';
      }
      $fileName .= $hour.'.log';
    } 

    if (br()->fs()->makeDir($filePath)) {
      br()->errorHandler()->disable();
      $fileExists = file_exists($filePath.$fileName);
      $this->filePointer = @fopen($filePath.$fileName, 'a+');      
      if ($fileExists) {
        $this->write();
      }
      br()->errorHandler()->enable();
    }

    parent::__construct();

  }

  function write($logText = null, $group = null, $initTime = 0 , $time = 0, $logLevel = 0, $newLine = true) {

    if ($this->filePointer && $this->isEnabled()) {

      if ($group) {
        $message = $group . ' ' . $initTime . '+' . $time . ' ' . str_repeat(' ', $logLevel*2) . $logText;
      } else {
        $message = $logText;        
      }

      if ($newLine) {
        $message .= "\n";
      }

      @fwrite($this->filePointer, $message);

    }
    
  }

}

