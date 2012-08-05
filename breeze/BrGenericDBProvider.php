<?php

/**
 * Project:     Breeze framework
 * Author:      Jager Mesh (jagermesh@gmail.com)
 *
 * @version 1.1.0.0
 * @package Breeze Core
 */

require_once(dirname(__FILE__).'/BrObject.php');

class BrGenericDBProvider extends BrObject {

  function now() {

    return $this->toDateTime(mktime());

  }

  function startTransaction() {
          
  }

  function commitTransaction() {     
     
  }

  function rollbackTransaction() {
          
  }
      
}
