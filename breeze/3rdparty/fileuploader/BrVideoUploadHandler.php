<?php

require_once(dirname(__FILE__).'/BrFileUploadHandler.php.php');

class BrVideoUploadHandler extends BrFileUploadHandler {

  function __construct($params = array()) {

    $params['allowedExtensions'] = array('flv', 'avi', 'mp4');

    parent::__construct($params);

  }

}
