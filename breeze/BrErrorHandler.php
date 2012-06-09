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
require_once(dirname(__FILE__).'/BrException.php');

class BrErrorHandler extends BrSingleton {


  private $notErrors = array(
//      E_NOTICE          => true
//      E_DEPRECATED      => true
  );

  function __construct() {

    error_reporting(E_ALL); // & ~E_COMPILE_WARNING & ~E_DEPRECATED);
    
    set_error_handler(array(&$this, "errorHandler"));
    set_exception_handler(array(&$this, "exceptionHandler"));
    
  }

  function exceptionHandler($e) {

    if ($this->isEnabled()) {

      br()->log()->logException($e);

      if (br()->isConsoleMode()) {

      } else {

        if ($e instanceof BrErrorException) {
          $isFatal = $e->IsFatal();
        } else {
          $isFatal = true;
        }
        $type = (($e instanceof BrErrorException) ? $e->getType() : 'Error');
        $errorMessage = $e->getMessage();
        $errorInfo = '';
        if (preg_match('/\[INFO:([^]]+)\](.+)\[\/INFO\]/ism', $errorMessage, $matches)) {
          $info_name = $matches[1];
          $errorInfo = $matches[2];
          $errorMessage = str_replace('[INFO:'.$info_name.']'.$errorInfo.'[/INFO]', '', $errorMessage);
        }

        if (br()->request()->domain() == 'localhost') {
          include(dirname(__FILE__).'/templates/ErrorReport.html');
        } else {
          if ($email = br()->config()->get('br/BrErrorHandler/exceptionHandler/sendErrorsTo')) {
            ob_start();
            @include(dirname(__FILE__).'/templates/ErrorReport.html');
            $result = ob_get_contents();
            ob_end_clean();
            br()->sendMail($email, 'Error at ' . br()->request()->url(), $result);
          }
        }

      }
    	 
    }

  }
  
  function errorHandler($errno, $errmsg, $errfile, $errline, $vars) {

    if ($this->isEnabled()) {
      if ((error_reporting() & $errno) == $errno) {
        //try {
          throw new BrErrorException($errmsg, 0, $errno, $errfile, $errline);
        // } catch (Exception $e) {
        //   $this->exceptionHandler($e);
        //   if ($e->isFatal()) {
        //     die($e->getMessage());
        //   }
        // }
      }
    }

  }

}

