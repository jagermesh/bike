<?php

/**
 * Project:     Breeze framework
 * Author:      Jager Mesh (jagermesh@gmail.com)
 *
 * @version 1.1.0.0
 * @package Breeze Core
 */

require_once(dirname(__FILE__).'/BrObject.php');

class BrAssetsCache extends BrObject {
  
  function send($requestedAssests = null) {

    $files = array();
    $files[] = dirname(__FILE__).'/3rdparty/jquery/jquery-1.7.1.min.js';
    if (strpos(br()->request()->get('extra'), 'jquery-ui') !== false) {
      $files[] = dirname(__FILE__).'/3rdparty/jquery.ui/jquery-ui.js';
    }
    $files[] = dirname(__FILE__).'/3rdparty/bootstrap/js/bootstrap.min.js';
    $files[] = dirname(__FILE__).'/3rdparty/humane-js/humane.min.js';
    $files[] = dirname(__FILE__).'/3rdparty/mustache/mustache.min.js';
    if (strpos(br()->request()->get('extra'), 'gritter') !== false) {
      $files[] = dirname(__FILE__).'/3rdparty/gritter/js/jquery.gritter.min.js';
    }
    $files[] = dirname(__FILE__).'/js/breeze.js';

    $lastModified = 0;
    foreach($files as $file) {
      if (($n = filemtime($file)) > $lastModified) {
        $lastModified = $n;
      }
    }

    if ($d = br()->request()->ifModifidSince()) {
      if ($d >= $lastModified) {
        br()->response()->sendNotModified();
      }
    }

    header('Content-type: application/x-javascript');  
    header('Last-Modified: ' . date('r', $lastModified));
    //header('Cache-Control:max-age=5, proxy-revalidate, must-revalidate');

    foreach($files as $file) {
      readfile($file);
    }

    exit();

  }

}
