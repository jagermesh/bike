<?php

class BrGenericUploadHandler {

  private $params;

  function __construct($params = array()) {

    $this->params = $params;

  }

  function handle() {

    // list of valid extensions, ex. array("jpeg", "xml", "bmp")
    $allowedExtensions = br($this->params, 'allowedExtensions', array());

    // max file size in bytes
    $sizeLimit = 24 * 1024 * 1024;

    if (br($this->params, 'checkLogin')) {
      $login = br()->auth()->checkLogin();
    }

    if (br($this->params, 'url')) {
      $url = br($this->params, 'url');
    } else
    if (br($this->params, 'path')) {
      $url = br($this->params, 'path');
    } else {
      $url = 'uploads/';
    }

    if (br($this->params, 'path')) {
      $path = br($this->params, 'path');
    } else {
      $path = 'uploads/';
    }

    if (br($this->params, 'userBasedPath')) {
      $url  .= br()->db()->rowidValue($login) . '/';
      $path .= br()->db()->rowidValue($login) . '/';
    }

    $uploader = new qqFileUploader($allowedExtensions, $sizeLimit, $this->params);
    if (!br($this->params, 'externalPath') && !preg_match('~^/~', $path)) {
      $path = br()->atBasePath($path);
    } else {

    }

    br()->fs()->makeDir($path);
    $result = $uploader->handleUpload($path, $url);
    
    // to pass data through iframe you will need to encode all html tags
    echo htmlspecialchars(json_encode($result), ENT_NOQUOTES);

  }

}

