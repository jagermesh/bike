<?php

/**
 * Project:     Breeze framework
 * Author:      Jager Mesh (jagermesh@gmail.com)
 *
 * @version 1.1.0.0
 * @package Breeze Core
 */

require_once(dirname(__FILE__).'/BrSingleton.php');

class BrResponse extends BrSingleton {

  function sendJSON($response, $alreadyPacked = false) {

    if (!$alreadyPacked) {
      $response = json_encode($response);      
    }

    header('Cache-Control: no-cache, must-revalidate');
    header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
    header('Content-type: application/json');      

    echo($response);
    exit();
    
  }

  function sendJSONP($response, $callback = null) {

    $callback = $callback?$callback:br()->request()->get('callback');
    $response = json_encode($response);
    $response = $callback . '(' . $response . ')';

    header('Cache-Control: no-cache, must-revalidate');
    header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
    header('Content-type: application/jsonp');

    echo($response);
    exit();
    
  }

  private function internalRedirect($url, $permanent, $saveCaller = false) {
    
    if (!preg_match('~^/~', $url) && !preg_match('~^http[s]?://~', $url)) {
      $url = br()->request()->baseUrl().$url;
    }
    if ($saveCaller) {
      $url .= ((strpos('?', $url) === false)?'?':'&').'caller='.urlencode(br()->request()->url());
    }

    br()->log()->writeLn('Redirecting to ' . $url);

    if (headers_sent()) {
      //echo('<script> document.location="' . $url . '"; </script>');
    } else { 
      if ($permanent) {
        header("HTTP/1.1 301 Moved Permanently"); 
      }  
      header("Location: $url");
    }
    exit(); 

  }

  function redirect($url, $saveCaller = false) {

    $this->internalRedirect($url, false, $saveCaller);

  }

  function redirectPermanent($url) {

    $this->internalRedirect($url, true);
    
  }

  function send404() {

    if (!headers_sent()) { 
      header('HTTP/1.0 404 Not Found');    
      echo "<h1>404 Not Found</h1>";
      echo "The page that you have requested could not be found.";
      exit();    
    }
    
  }
 
  function sendNotAuthorized($error = null) {

    if (!headers_sent()) { 
      header('HTTP/1.0 401 Not Authorized');
      if ($error) {
        echo($error);
      } else {
        echo('<h1>401 Not Authorized</h1>');
      }
      exit();    
    }
    
  }

  function sendNoContent($error = null) {

    if (!headers_sent()) { 
      header('HTTP/1.0 204 No Content');
      echo $error;
      exit();    
    }
    
  }
 
  function sendForbidden($error = null) {

    if (!headers_sent()) { 
      header('HTTP/1.0 403 Forbidden');
      if ($error) {
        echo($error);
      }
      exit();    
    }
    
  }
 
  function sendMethodNotAllowed($error = null) {

    if (!headers_sent()) { 
      header('HTTP/1.0 405 Method Not Allowed');
      if ($error) {
        echo($error);
      }
      exit();    
    }
    
  }
 
  function sendCreated() {

    if (!headers_sent()) { 
      header('HTTP/1.0 201 Created');
    }
    
  }
 
  function sendNotModified() {

    if (!headers_sent()) { 
      header('HTTP/1.0 304 Not Modified');
    }
    
  }
 
  function sendConflict($error) {

    if (!headers_sent()) { 
      header('HTTP/1.0 409 Conflict');
      if ($error) {
        echo($error);
      }
      exit();
    }
    
  }
 
  function sendSuccess() {

    if (!headers_sent()) { 
      header('HTTP/1.0 200 OK');
      exit();
    }
    
  }
 
}
