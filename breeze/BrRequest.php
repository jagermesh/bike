<?php

/**
 * Project:     Breeze framework
 * Author:      Jager Mesh (jagermesh@gmail.com)
 *
 * @version 1.1.0.0
 * @package Breeze Core
 */

require_once(dirname(__FILE__).'/BrSingleton.php');

class BrRequest extends BrSingleton {

  private $host = null;
  private $url = null;
  private $path = null;
  private $relativeUrl = null;
  private $baseUrl = null;
  private $frameworkUrl = null;
  private $clientIP = null;
  private $scriptName = null;
  private $continueRoute = true;
  private $putVars= array();

  function __construct() {

    if (!br()->isConsoleMode()) {
      $domain = br($_SERVER, 'HTTP_HOST');
      $host = 'http'.((br($_SERVER, 'HTTPS') == "on")?'s':'').'://'.$domain;
      $request = br($_SERVER, 'REQUEST_URI');
      $query = preg_replace('~^[^?]*~', '', $request);
      $request = preg_replace('~[?].*$~', '', $request);

      $pathInfo = pathinfo($request);
      $pathInfo['dirname'] = str_replace('\\', '', $pathInfo['dirname']);
      if (preg_match('~[.](html|php|htm)~i', $pathInfo['basename'])) {
        $scriptName = $pathInfo['basename'];
        $request = str_replace($scriptName, '', $request);
      } else {
        $pathInfo['dirname'] = $pathInfo['dirname'].'/'.$pathInfo['basename'].'/';
        $scriptName = '';
      }
      $path = $host.rtrim($request, '/').'/'.$scriptName;
      $url = $path.$query;
      if (!$scriptName) {
        $scriptName = 'index.php';
      }

      $scriptPathinfo = pathinfo(br($_SERVER, 'SCRIPT_NAME'));
      $scriptPathinfo['dirname'] = str_replace('\\', '', $scriptPathinfo['dirname']);
      $s = rtrim(ltrim($scriptPathinfo['dirname'], '/'), '/');
      $baseUrl = '/'.$s.($s?'/':'');
      $s = rtrim(ltrim($pathInfo['dirname'], '/'), '/');
      $relativeUrl = '/'.$s.($s?'/':'');

      if (strpos($relativeUrl, $baseUrl) === 0) {
        $relativeUrl = substr($relativeUrl, strlen($baseUrl));
      }

      $this->url = $url;
      $this->path = $path;
      $this->domain = $domain;
      $this->host = $host;
      $this->relativeUrl = $relativeUrl;
      $this->baseUrl = $baseUrl;
      $this->frameworkUrl = $this->baseUrl() . 'breeze/';
      $this->scriptName = $scriptName;

      if ($this->isPUT()) {
        parse_str(file_get_contents("php://input"), $this->putVars);
        if (get_magic_quotes_gpc()) { 
          br()->stripSlashes($this->putVars);
        }
      }

      $this->clientIP = br($_SERVER, 'HTTP_CLIENT_IP');

      if (!$this->clientIP || ($this->clientIP == 'unknown') || ($this->clientIP == '::1')) {
        $this->clientIP = br($_SERVER, 'HTTP_X_FORWARDED_FOR');
      }

      if (!$this->clientIP || ($this->clientIP == 'unknown') || ($this->clientIP == '::1')) {
        $this->clientIP = br($_SERVER, 'REMOTE_ADDR');
      }

      if ($this->clientIP == '::1') {
        $this->clientIP = '127.0.0.1';
      }

      if (!$this->clientIP) {
        $this->clientIP = 'unknown';
      }

    }
            
  }

  function referer() {

    return br($_SERVER, 'HTTP_REFERER');

  }

  function isSelfReferer() {

    return strpos($this->referer(), $this->host.$this->baseUrl) !== false;
    
  }

  function at($path) {

    if (@preg_match('~'.$path.'~', $this->path, $matches)) {
      return $matches;
    } else {
      return null;
    }

  }

  function atBaseUrl($path) {
  
    return $this->at($this->baseUrl.'$');
    
  }  

  function path() {

    return $this->path;

  }

  function clientIP() {

    return $this->clientIP;

  }

  function url() {

    return $this->url;
    
  }

  function relativeUrl() {

    return $this->relativeUrl;

  }

  function baseUrl() {

    return $this->baseUrl;

  }

  function frameworkUrl() {

    return $this->frameworkUrl;

  }

  function setFrameworkUrl($url) {

    return ($this->frameworkUrl = $url);

  }

  function domain() {

    return $this->domain;

  }

  function host() {

    return $this->host;

  }

  function scriptName() {

    return $this->scriptName;

  }

  function method() {

    return br($_SERVER, 'REQUEST_METHOD');

  }

  function ifModifidSince() {

    if ($d = br($_SERVER, 'HTTP_IF_MODIFIED_SINCE')) {
      return strtotime($d);
    }
    return null;

  }

  function isMethod($method) {

    return (br($_SERVER, 'REQUEST_METHOD') == $method);

  }

  function isGET() {

    return (br($_SERVER, 'REQUEST_METHOD') == 'GET');

  }

  function isPOST() {

    return (br($_SERVER, 'REQUEST_METHOD') == 'POST');

  }

  function isDELETE() {

    return (br($_SERVER, 'REQUEST_METHOD') == 'DELETE');

  }

  function isPUT() {

    return (br($_SERVER, 'REQUEST_METHOD') == 'PUT');

  }

  function isRedirect() {

    return (br($_SERVER, 'REDIRECT_STATUS') != 200);

  }

  function isTemporaryRedirect() {

    return (br($_SERVER, 'REDIRECT_STATUS') == 302);

  }

  function isPermanentRedirect() {

    return (br($_SERVER, 'REDIRECT_STATUS') == 301);

  }

  function userAgent() {

    return br($_SERVER, 'HTTP_USER_AGENT');

  }

  function isMobile() {

    return preg_match('/iPad|iPhone|iOS|Android/i', br($_SERVER, 'HTTP_USER_AGENT'));

  }

  function get($name = null, $default = null) {

    if ($name) {
      return br($_GET, $name, $default);
    } else {
      return $_GET;
    }

  }

  function post($name = null, $default = null) {

    if ($name) {
      return br($_POST, $name, $default);
    } else {
      return $_POST;
    }

  }

  function put($name = null, $default = null) {

    if ($name) {
      return br($this->putVars, $name, $default);      
    } else {
      return $this->putVars;      
    }

  }

  function cookies($name, $default = null) {

    return br($_COOKIE, $name, $default);

  }

  function param($name, $default = null) {

    return $this->get($name, $this->post($name, $default));

  }

  function isFilesUploaded() {

    return count($_FILES);
    
  }

  function file($name) {

    $result = br($_FILES, $name);
    return $result;
    
  }

  function fileTmp($name) {

    if ($this->isFileUploaded($name)) {
      return br($this->file($name), 'tmp_name');
    }
    
  }

  function fileName($name) {

    if ($this->isFileUploaded($name)) {
      return br($this->file($name), 'name');
    }
    
  }

  function fileSize($name) {

    if ($this->isFileUploaded($name)) {
      return br($this->file($name), 'size');
    }
    
  }

  function fileError($name) {

    if ($_FILES) {
      if ($result = br($_FILES, $name)) {
        return br($this->file($name), 'error');
      }
    }
    
  }

  function isFileUploaded($name) {
    
    if ($this->isFilesUploaded()) {
      if ($result = $this->file($name)) {
        return br($result, 'tmp_name') &&
               file_exists(br($result, 'tmp_name')) &&
               (br($result, 'error') == UPLOAD_ERR_OK) &&
               (br($result, 'size') > 0);
      }
    }
    return false;

  }

  function moveUploadedFile($name, $destFolder) {
  
    if ($this->isFileUploaded($name)) {
      $destFolder = br()->fs()->normalizePath($destFolder);
      if (br()->fs()->makeDir($destFolder)) {
        return move_uploaded_file($this->fileTmp($name), $destFolder.$this->fileName($name));
      } else {
        throw new BrException('Cannot create folder '.$destFolder);
      }
    } else {
      throw new BrException("Cannot move file - it's not uploaded");
    }
    
  }

  function continueRoute($value) {

    $this->continueRoute = $value;

  }

  function routeComplete() {

    return !$this->continueRoute;

  }

  function route($path, $func, $method = 'GET') {
    
    if (!$this->routeComplete()) {
      if ($this->isMethod($method)) {
        if ($match = $this->at($path)) {
          $this->continueRoute(false);
          $func($match);
        }
      }
    }

    return $this;

  }

  function check($condition, $func) {
    
    if (!$this->routeComplete()) {
      if ($condition) {
        $this->continueRoute(false);
        $func();
      }
    }

    return $this;

  }

  function routeGET($path, $func) {
    
    return $this->route($path, $func);

  }

  function routePOST($path, $func) {
    
    return $this->route($path, $func, 'POST');

  }

  function routePUT($path, $func) {

    return $this->route($path, $func, 'PUT');

  }

  function routeDELETE($path, $func) {
    
    return $this->route($path, $func, 'DELETE');

  }

  function routeIndex($func) {

    return $this->route(br()->request()->host().br()->request()->baseUrl().'$', $func);

  }

  function routeDefault() {

    if (!$this->routeComplete()) {
      br()->response()->send404();
    }
    
  }

}
