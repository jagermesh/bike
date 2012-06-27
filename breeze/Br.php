<?php

/**
 * Project:     Breeze framework
 * Author:      Jager Mesh (jagermesh@gmail.com)
 *
 * @version 1.1.0.0
 * @package Breeze Core
 */

require_once(dirname(__FILE__).'/BrSingleton.php');
require_once(dirname(__FILE__).'/BrException.php');

function br($array = null, $name = null, $default = null) {

  if (func_num_args() > 0) {
    if (is_array($array) && is_array($name)) {
      $result = null;
      foreach($name as $oneName) {
        $result = br($array, $oneName);
        if (strlen($result)) {
          return $result;
        }
      }
      if (!strlen($result)) {
        return $default;
      }
    } else {
      return ( is_array($array) && 
               strlen($name) && 
               array_key_exists($name, $array) && 
               ($array[$name] || (is_scalar($array[$name]) && strlen($array[$name])))
             )
             ? $array[$name] 
             : $default;
    }
  } else {
    return Br::GetInstance();      
  }

}

if (!function_exists('debug')) {

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

if (!function_exists('callstack')) {

  function callStack() {

    br()->log()->callStack(); 
    
  }
  
}

if (!function_exists('logme')) {

  function logme() {

    $args = func_get_args();
    foreach($args as $var) {
      br()->log()->writeLn($var);      
    }
    
  }
  
}

class Br extends BrSingleton {

  function __construct() {

    $this->frameWorkPath = str_replace('\\', '/', rtrim(dirname(__FILE__), '/').'/');
    parent::__construct();
    
  }

  public function __call($name, $arguments) {

    $className = 'Br' . ucwords($name);
    $classFile = dirname(__FILE__) . '/' . $className . '.php';
    if (file_exists($classFile)) {
      require_once($classFile);
      // ARGH HOW UGLY!!!
      if (!count($arguments)) {
        return $className::GetInstance();
      } else
      if (count($arguments) == 1) {
        return $className::GetInstance($arguments[0]);
      } else
      if (count($arguments) == 2) {
        return $className::GetInstance($arguments[0], $arguments[1]);
      } else
      if (count($arguments) == 3) {
        return $className::GetInstance($arguments[0], $arguments[1], $arguments[2]);
      } else
      if (count($arguments) == 4) {
        return $className::GetInstance($arguments[0], $arguments[1], $arguments[2], $arguments[3]);
      }
    } else {
      throw new Exception('Call to unknown method - ' . $name);
    }

  }

  function log() {

    require_once(dirname(__FILE__).'/BrLog.php');
    $log = BrLog::GetInstance();

    $args = func_get_args();
    foreach($args as $var) {
      $log->writeLn($var);
    }

    return $log;

  }

  function fs() {

    return $this->FileSystem();

  }

  function db($name = null) {

    return $this->DataBase($name);
    
  }

  private $scriptName = null;
  private $basePath = null;
  private $appPath = null;
  private $frameWorkPath = null;
  private $application = null;
  
  function saveCallerScript($scriptPath) {
  
    $this->basePath = $this->fs()->filePath($scriptPath);
    $this->scriptName = $this->fs()->fileName($scriptPath);
    $this->appPath = $this->basePath.'app/';
    $this->templatesPath = $this->basePath.'templates/';
    
  }
  
  function scriptName() {
  
    return $this->scriptName;
    
  }
  
  function atBasePath($path) {
  
    return $this->basePath.ltrim($path, '/');
    
  }
  
  function atAppPath($path) {
  
    return $this->appPath.ltrim($path, '/');
    
  }
  
  function templatesPath() {
  
    return $this->templatesPath;
    
  }
  
  function atTemplatesPath($path) {
  
    return $this->templatesPath.ltrim($path, '/');
    
  }
  
  function atFrameWorkPath($path) {
  
    return $this->frameWorkPath.$path;
    
  }

  function removeEmptyKeys($array) {
  
    $result = array();
    foreach($array as $key => $value) {
      $go = false;
      if (is_array($value)) {
        $value = BrArray::RemoveEmptyKeys($value);
        $go = $value; 
      } else {
        $go = strlen($value);
      }
      if ($go) {
        $result[$key] = $value;
      }
    }
    return $result;
    
  }

  function loadFile($fileName) {

    $result = null;

    if (file_exists($fileName)) {
      if ($f = @fopen($fileName, 'r')) {
        while (!feof($f)) {
          $result .= fread($f, 4096);
        }
        fclose($f);
      }
    }

    return $result;
    
  }

  function isConsoleMode() {

    return (!array_key_exists('REQUEST_METHOD', $_SERVER));
    
  }

  function getMicrotime(){
    
    list($usec, $sec) = explode(" ",microtime());
    return ((float)$usec + (float)$sec);
    
  }

  function placeholder() {

    $args = func_get_args();
    $tmpl = array_shift($args);
    $result = $this->placeholderEx($tmpl, $args, $error);
    if ($result === false)
      return 'ERROR:'.$error;
    else
      return $result;
    
  }

  function assert($value, $error = null) {
    
    if (!$value) {
      throw new BrAssertException($error);
    }
    
  }

  function importAtBasePath($fileName) {

    $this->import($this->atBasePath($fileName));

  }

  function importLib($FileName) {

    $FileName = 'Br'.$FileName.'.php';

    require_once(dirname(__FILE__) . '/' . $FileName);
    
  }

  private $dataSources = array();

  function importDataSource($name) {

    require_once(dirname(__FILE__) . '/datasources/' . $name. '.php');

    return $this->getDataSource($name);
    
  }

  function getDataSource($name) {

    return br($this, $name);
    
  }

  function import($FileName) {

    if (!preg_match('/[.]php$/', $FileName)) {
      $FileName = $FileName . '.php';
    }

    if (file_exists($FileName)) {
      require_once($FileName);
      return true;
    } else {
      return false;
    }
    
  }

  function formatDuration($duration) {

    $secs = $mins = $hrs = 0;
    if ($duration < 60) {
      $secs = $duration;
    } else
    if ($duration < 60*60) {
      $mins = floor($duration/60);
      $secs = $duration - $mins*60;
    } else {
      $hrs  = floor($duration/60/60);
      $mins = ($duration - $hrs*60*60)/60;
      $secs = $duration - $hrs*60*60 - $mins*60;
    }

    $result = '';
    
    if ($secs) {
      $result = number_format($secs, 3);
    }
    if ($mins) {
      $result = $mins.($result?':'.$result:'');
    }
    if ($hrs) {
      $result = $hrs.($result?':'.$result:'');
    }

    return trim($result);

  }

  function durationToTime($duration) {

    $secs = $mins = $hrs = 0;
    if ($duration < 60) {
      $secs = $duration;
    } else
    if ($duration < 60*60) {
      $mins = floor($duration/60);
      $secs = $duration - $mins*60;
    } else {
      $hrs  = floor($duration/60/60);
      $mins = ($duration - $hrs*60*60)/60;
      $secs = $duration - $hrs*60*60 - $mins*60;
    }

    $result = '';
    
    if ($secs) {
      $result = number_format($secs, 3).' '.'secs';
    }
    if ($mins) {
      $result = $mins.' '.'mins'.' '.$result;
    }
    if ($hrs) {
      $result = $hrs.' '.'hrs'.' '.$result;
    }

    return trim($result);

  }

  private function placeholderCompile($tmpl) {

    $compiled  = array();
    $p         = 0;
    $i         = 0;
    $has_named = false;

    while (false !== ($start = $p = strpos($tmpl, "?", $p))) {

      switch ($c = substr($tmpl, ++$p, 1)) {
        case '&': 
        case '%': 
        case '@': 
        case '#':
          $type = $c; 
          ++$p; 
          break;
        default:
          $type = ''; 
          break;
      }

      if (preg_match('/^((?:[^\s[:punct:]]|_)+)/', substr($tmpl, $p), $pock)) {

        $key = $pock[1];
        if ($type != '#') 
          $has_named = true;
        $p += strlen($key);

      } else {

        $key = $i;
        if ($type != '#') 
          $i++;

      }

      $compiled[] = array($key, $type, $start, $p - $start);
    }

    return array($compiled, $tmpl, $has_named);

  }

  function placeholderEx($tmpl, $args, &$errormsg) {

    if (is_array($tmpl)) {
      $compiled = $tmpl;
    } else {
      $compiled = $this->placeholderCompile($tmpl);
    }

    list ($compiled, $tmpl, $has_named) = $compiled;

    if ($has_named) 
      $args = @$args[0];

    $p   = 0;
    $out = '';
    $error = false;

    foreach ($compiled as $num=>$e) {

      list ($key, $type, $start, $length) = $e;

      $out .= substr($tmpl, $p, $start - $p);
      $p = $start + $length;

      $repl = '';
      $errmsg = '';

      do {
        
        if (!isset($args[$key]))
          $args[$key] = "";

        if ($type === '#') {
          $repl = @constant($key);
          if (NULL === $repl)
            $error = $errmsg = "UNKNOWN_CONSTANT_$key";
          break;
        }

        if (!isset($args[$key])) {
          $error = $errmsg = "UNKNOWN_PLACEHOLDER_$key";
          break;
        }

        $a = $args[$key];
        if ($type === '&') {
          if ($a === "") {
            $repl = "null";
          } else {
            $repl = "'".addslashes($a)."'";
          }
          break;
        } else
        if ($type === '') {
          if (is_array($a)) {
            $error = $errmsg = "NOT_A_SCALAR_PLACEHOLDER_$key";
            break;
          }
          if ($a === "")
            $repl = "null";
          else {
            $repl = (preg_match('#^[-]?([1-9][0-9]*|[0-9])($|[.,][0-9]+$)#', $a)) ? str_replace(',', '.', $a) : "'".addslashes($a)."'";
          }
          break;
        }

        if (!is_array($a)) {
          $error = $errmsg = "NOT_AN_ARRAY_PLACEHOLDER_$key";
          break;
        }

        if ($type === '@') {
          foreach ($a as $v) {
            $repl .= ($repl===''? "" : ",").(preg_match('#^[-]?([1-9][0-9]*|[0-9])($|[.,][0-9]+$)#', $v) ? str_replace(',', '.', $v):"'".$v."'");
          }
        } else
        if ($type === '%') {
          $lerror = array();
          foreach ($a as $k=>$v) {
            if (!is_string($k)) {
              $lerror[$k] = "NOT_A_STRING_KEY_{$k}_FOR_PLACEHOLDER_$key";
            } else {
              $k = preg_replace('/[^a-zA-Z0-9_]/', '_', $k);
            }
            $repl .= ($repl===''? "" : ", ").$k."='".@addslashes($v)."'";
          }
          if (count($lerror)) {
            $repl = '';
            foreach ($a as $k=>$v) {
              if (isset($lerror[$k])) {
                $repl .= ($repl===''? "" : ", ").$lerror[$k];
              } else {
                $k = preg_replace('/[^a-zA-Z0-9_-]/', '_', $k);
                $repl .= ($repl===''? "" : ", ").$k."=?";
              }
            }
            $error = $errmsg = $repl;
          }
        }

      } while (false);

      if ($errmsg) 
        $compiled[$num]['error'] = $errmsg;

      if (!$error) 
        $out .= $repl;

    }
    $out .= substr($tmpl, $p);

    if ($error) {
      $out = '';
      $p   = 0;
      foreach ($compiled as $num=>$e) {
        list ($key, $type, $start, $length) = $e;
        $out .= substr($tmpl, $p, $start - $p);
        $p = $start + $length;
        if (isset($e['error'])) {
          $out .= $e['error'];
        } else {
          $out .= substr($tmpl, $start, $length);
        }
      }
      $out .= substr($tmpl, $p);
      $errormsg = $out;
      return false;
    } else {
      $errormsg = false;
      return $out;
    }

  }

  function panic($error = null) {

    throw new BrException($error ? $error : "Ciritcal error");

  }
  
  function fromJSON($json, $default = null) {
  
    $result = json_decode($json, true);
    if (!$result) {
      $result = $default;
    }
    return $result;
    
  }
   
  function toJSON($data) {
  
    return json_encode($data);
    
  }

  public function defaultConfig() {

    br()->importLib('FileLogAdapter');
    br()->log()->addAdapter(new BrFileLogAdapter(br()->atBasePath('_logs')));
    if (br()->isConsoleMode()) {
      br()->importLib('ConsoleLogAdapter');
      br()->log()->addAdapter(new BrConsoleLogAdapter());
    }
    
  }
    
  function html2text($html) { 

    $html = preg_replace("/&nbsp;/ism", ' ', $html);
    $html = html_entity_decode($html);
    $html = preg_replace("/(\n\n|\r\n\r\n|\r\r)/ism", '', $html);
    $html = preg_replace('/<br[^>]*>/ism', "\n", $html);
    $html = preg_replace('/<[^>]+>/ism', '', $html);
    $html = preg_replace('/<\/[^>]+>/ism', '', $html);
    return $html;

  }

  function guid() {
     
    // The field names refer to RFC 4122 section 4.1.2
    return sprintf('%04x%04x-%04x-%03x4-%04x-%04x%04x%04x',
      mt_rand(0, 65535), mt_rand(0, 65535), // 32 bits for "time_low"
      mt_rand(0, 65535), // 16 bits for "time_mid"
      mt_rand(0, 4095),  // 12 bits before the 0100 of (version) 4 for "time_hi_and_version"
      bindec(substr_replace(sprintf('%016b', mt_rand(0, 65535)), '01', 6, 2)),
         // 8 bits, the last two of which (positions 6 and 7) are 01, for "clk_seq_hi_res"
         // (hence, the 2nd hex digit after the 3rd hyphen can only be 1, 5, 9 or d)
         // 8 bits for "clk_seq_low"
      mt_rand(0, 65535), mt_rand(0, 65535), mt_rand(0, 65535) // 48 bits for "node"  
    );

  }

  function encryptInt($num) {

    $rand1 = rand(100, 999);
    $rand2 = rand(100, 999);
    $key1 = ($num + $rand1) * $rand2;
    $key2 = ($num + $rand2) * $rand1;
    $result = $rand1.$rand2.$key1.$key2;
    $rand1_len = chr(ord('A') + strlen($rand1));
    $rand2_len = chr(ord('D') + strlen($rand2));
    $key1_len  = chr(ord('G') + strlen($key1));
    $rand1_pos = rand(0, floor(strlen($result)/3));
    $result1 = substr_replace($result, $rand1_len, $rand1_pos, 0);
    $rand2_pos = rand($rand1_pos + 1, floor(2*strlen($result1)/3));
    $result2 = substr_replace($result1, $rand2_len, $rand2_pos, 0);
    $key1_pos  = rand($rand2_pos + 1, strlen($result2)-1);
    $result3 = substr_replace($result2, $key1_len, $key1_pos, 0);
    return $result3;
  
  } 

  function decryptInt($num) {

    if (preg_match('/([A-Z]).*([A-Z]).*([A-Z])/', $num, $matches)) {
      $rand1_len = ord($matches[1]) - ord('A');
      $rand2_len = ord($matches[2]) - ord('D');
      $key1_len  = ord($matches[3]) - ord('G');
      $num = str_replace($matches[1], '', $num);
      $num = str_replace($matches[2], '', $num);
      $num = str_replace($matches[3], '', $num);
      $rand1 = substr($num, 0, $rand1_len);
      $rand2 = substr($num, $rand1_len, $rand2_len);
      $key1 = substr($num, $rand1_len + $rand2_len, $key1_len);
      $key2 = substr($num, $rand1_len + $rand2_len + $key1_len);
      if (($rand1 > 0) && ($rand2 > 0)) {
        $num1 = $key1 / $rand2 - $rand1;
        $num2 = $key2 / $rand1 - $rand2;
        if ($num1 == $num2) {
          return $num1;
        } else {
          return null;
        }
      } else {
        return null;
      }    
    } else {
      return null;
    }  
    
  } 

  function sendMail($email, $subject, $body) {

    br()->importAtBasePath('breeze/3rdparty/phpmailer/class.phpmailer.php');
    $mail = new PHPMailer(true);
    try {
      $from = br()->config()->get('br/Br/sendMail/from', 'noreply@localhost');
      $mail->AddReplyTo($from);
      $mail->AddAddress($email);
      $mail->SetFrom($from);
      $mail->Subject = $subject;
      $mail->MsgHTML($body);
      br()->log()->writeLn('Sending mail to ' . $email);
      if ($mail->Send()) {
        br()->log()->writeLn('Sent');
      } else {
        throw new Exception('Mail was not sent because of unknown error');
      }
    } catch (phpmailerException $e) {
      br()->log()->writeLn('Can not send mail to ' . $email . '. Error: ' . $e->getMessage());
    } catch (Exception $e) {
      br()->log()->writeLn('Can not send mail to ' . $email . '. Error: ' . $e->getMessage());
    }

  }

  function inc(&$var, $secondVar, $glue = ', ') {
    
    if (is_integer($var)) {
      $var = $var + $secondVar;
    } else {
      $var = $var . ($var ? $glue : '') . $secondVar;
    }

  }

  function stripSlashes(&$element) { 
    if (is_array($element)) { 
      foreach($element as $key => $value) {
        $this->stripSlashes($element[$key]); 
      }
    } else {
      $element = stripslashes($element); 
    }
  }
  
}

