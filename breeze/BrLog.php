<?php

/**
 * Project:     Breeze framework
 * Author:      Jager Mesh (jagermesh@gmail.com)
 *
 * @version 1.1.0.0
 * @package Breeze Core
 */

require_once(dirname(__FILE__).'/Br.php');
require_once(dirname(__FILE__).'/BrSingleton.php');

class BrLog extends BrSingleton {

  private $initTime = null;
  private $initMicroTime = null;
  private $logLevel = 0;
  private $adapters = array();

  function __construct() {

    $this->initMicroTime = Br()->getMicrotime();
    $this->initTime = @strftime('%H:%M:%S');
    
  }

  function addAdapter($adapter) {

    $this->adapters[] = $adapter;

  }

  private function writeToAdapters($message, $group, $newLine) {

    if ($this->isEnabled()) {
      
      if (is_array($message)) {
        if (count($message)) {
          $logText = var_export($message, true);          
        } else {
          $logText = '[Empty Array]';          
        }
      } else
      if (is_object($message)) {
        $logText = var_export($message, true);        
      } else {
        $logText = $message;         
      }

      if ($logText) {

        if (!$group) {
          $group = 'MSG';
        }

        $time = Br()->FormatDuration(Br()->GetMicrotime() - $this->initMicroTime);
        $message = $group.' '.$this->initTime.'+'.$time.' '.str_repeat(' ', $this->logLevel*2).$logText;
        $message .= "\n";
        foreach($this->adapters as $adapter) {
          $adapter->write($message);
        }

      }

    }

  }

  function write($message, $group = null) {
    
    $this->writeToAdapters($message, $group,false);

  }

  function writeLn($message, $group = null) {
    
    $this->writeToAdapters($message, $group, true);

  }

  function writeError($message, $object = null) {
    
    if ($object) {
      $message = get_class($object) . ' :: ' . $message;
    }
    $this->writeToAdapters($message, 'ERR', true);

  }

  function formatCalllParams($params, $level = 0) {
    
    $result = '';
    foreach($params as $arg) {
      if (is_numeric($arg)) {
        $result .= $arg . ', ';
      } else
      if (is_array($arg)) {
        if ($level) {
          $result .= '[array], ';
        } else {
          $result .= '['.$this->formatCalllParams($arg, $level + 1).'], ';
        }
      } else
      if (is_object($arg)) {
        $result .= '[' . get_class($arg) . '], ';
      } else
      if (is_resource($arg)) {
        $result .= '#' . (string)$arg . ', ';
      } else 
      if (!$arg) {
        $result .= 'null, ';
      } else {
        $result .= '"' . substr((string)$arg, 0, 255) . '", ';          
      }
    }
    return rtrim($result, ', ');    

  }

  function formatStackTraceCall($trace) {
    
    $result = '';
    if (br($trace, 'class')) {
      $result .= $trace['class'];
    }
    if (br($trace, 'type')) {
      $result .= $trace['type'];
    }
    $result .= $trace['function'] . '(';
    if (br($trace, 'args')) {
      $result .= $this->formatCalllParams($trace['args']);
    }
    $result = rtrim($result, ', ');
    $result .= ');';
    
    return $result;
    
  }
  
  function formatStackTraceSource($trace) {
    
    $result = '';
    if (br($trace, 'file')) {
      $result .= $trace['file'];
    } else {
      $result .= __FILE__;
    }
    if (br($trace, 'line')) {
      $result .= ', ' . $trace['line'];
    }
    
    return $result;
    
  }
  
  function logException($e) {

    $isFatal = (!($e instanceof BrErrorException) || $e->IsFatal());
    $type = (($e instanceof BrErrorException) ? $e->getType() : 'Error');
    $errorMessage = $e->getMessage();
    $errorInfo = '';
    if (preg_match('/\[INFO:([^]]+)\](.+)\[\/INFO\]/ism', $errorMessage, $matches)) {
      $info_name = $matches[1];
      $errorInfo = $matches[2];
      $errorMessage = str_replace('[INFO:'.$info_name.']'.$errorInfo.'[/INFO]', '', $errorMessage);
    }

    $this->writeLn('Error' . ($isFatal ? ' (fatal)':'') . ': ' . $errorMessage, 'ERR');
    $this->writeLn($e->getFile() . ', line ' . $e->getLine(), 'ERR');
    $this->writeLn($errorInfo, 'ERR');

    if ($e instanceof BrErrorException) {
      $idx = 0;
    } else {
      $idx = 1;
    }
    foreach($e->getTrace() as $index => $statement) {
      if ($idx) {
        $this->writeLn('  ' . $this->formatStackTraceCall($statement), 'ERR');
        $this->writeLn('  ' . $this->formatStackTraceSource($statement), 'ERR');        
      }
      $idx++;
    }

  }

  function callStack() {

    try {
      throw new BrCallStackException();
    } catch (Exception $e) {
      $this->writeLn('Call Stack', 'DBG');
      $idx = 0;
      foreach($e->getTrace() as $index => $statement) {
        if ($idx) {
          $this->writeLn('  ' . $this->formatStackTraceCall($statement), 'DBG');
          $this->writeLn('  ' . $this->formatStackTraceSource($statement), 'DBG');        
        }
        $idx++;
      }
      if (br()->isConsoleMode()) {
        
      } else {
        include(dirname(__FILE__).'/templates/CallStack.html');
      }
    }

  }

  function debug() {

    $args = func_get_args();
    foreach($args as $var) {
      br()->log()->writeLn($var, 'DBG');
      
      $message = print_r($var, true);
      if (br()->isConsoleMode()) {
        // echo($message);      
        // echo("\n");
      } else
      if (br()->request()->domain() == 'localhost') {
        include(dirname(__FILE__).'/templates/DebugMessage.html');      
      }
    }

  }

}

