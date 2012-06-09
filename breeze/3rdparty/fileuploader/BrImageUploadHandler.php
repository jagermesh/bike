<?php

require_once(dirname(__FILE__).'/BrGenericUploadHandler.php');

/**
 * Handle file uploads via XMLHttpRequest
 */
class qqUploadedFileXhr {

  private $params;

  function __construct($params = array()) {        

    $this->params = $params;

  }

  /**
   * Save the file to the specified path
   * @return boolean TRUE on success
   */
  function save($path) {    

    $input = fopen("php://input", "r");
    $temp = tmpfile();
    $realSize = stream_copy_to_stream($input, $temp);
    fclose($input);
    
    if ($realSize != $this->getSize()){            
      return false;
    }
    
    $ext = br()->fs()->fileExt($this->getName());
    if (!$ext) {
      $ext = 'dat';
    }
    $srcFilePath = $path . 'tmpUploadFile.' . $ext;
    while(file_exists($srcFilePath)) {
      $srcFilePath = $path . 'tmpUploadFile.' . $ext . rand(1, 10000);
    }
    $target = fopen($srcFilePath, "w");        
    fseek($temp, 0, SEEK_SET);
    stream_copy_to_stream($temp, $target);
    fclose($target);

    $dstFilePath = '';
    $dstFileName = '';
    
    br()->importLib('Image');

    $image = new BrImage($srcFilePath);

    if (br($this->params, 'generateFileName')) {
      $md = md5_file($srcFilePath);
      $dstFileName = $md . '.' . $image->format();        
    } else {
      $dstFileName = br()->fs()->fileName($this->getName());
    }
    $dstFilePath = $path . $dstFileName;

    if (!br($this->params, 'generateFileName') && br($this->params, 'checkExistance')) {
      $idx = 1;
      while(file_exists($dstFilePath)) {
        $dstFileName = br()->fs()->fileName($this->getName, $idx);
        $dstFilePath = $path . $dstFileName;
        $idx++;
      }
    }

    if (file_exists($dstFilePath)) {
      unlink($dstFilePath);
    }
    rename($srcFilePath, $dstFilePath);

    return $dstFileName;

  }

  function getName() {

    return $_GET['qqfile'];

  }

  function getSize() {

    if (isset($_SERVER["CONTENT_LENGTH"])){
      return (int)$_SERVER["CONTENT_LENGTH"];            
    } else {
      throw new Exception('Getting content length is not supported.');
    }      

  }   

}

/**
 * Handle file uploads via regular form post (uses the $_FILES array)
 */
class qqUploadedFileForm {  

  private $params;

  function __construct($params = array()) {        

    $this->params = $params;

  }

  /**
   * Save the file to the specified path
   * @return boolean TRUE on success
   */
  function save($path) {
 
    br()->importLib('Image');

    $dstFileName = '';
    $dstFilePath = '';

    $image = new BrImage($_FILES['qqfile']['tmp_name']);

    if (br($this->params, 'generateFileName')) {
      $md = md5_file($_FILES['qqfile']['tmp_name']);
      $dstFileName = $md . '.' . $image->format();
    } else {
      $dstFileName = br()->fs()->fileName($this->getName());
    }
    $dstFilePath = $path . $dstFileName;

    if (!br($this->params, 'generateFileName') && br($this->params, 'checkExistance')) {
      $idx = 1;
      while(file_exists($dstFilePath)) {
        $dstFileName = br()->fs()->fileName($this->getName, $idx);
        $dstFilePath = $path . $dstFileName;
        $idx++;
      }
    }

    if (file_exists($dstFilePath)) {
      unlink($dstFilePath);
    }
    if (move_uploaded_file($_FILES['qqfile']['tmp_name'], $dstFilePath)) {
      return $dstFileName;
    }      

    return $dstFileName;

  }

  function getName() {

    return $_FILES['qqfile']['name'];

  }

  function getSize() {

    return $_FILES['qqfile']['size'];

  }

}

class qqFileUploader {

  private $allowedExtensions = array();
  private $sizeLimit = 10485760;
  private $file;
  private $params;

  function __construct(array $allowedExtensions = array(), $sizeLimit = 10485760, $params = array()){        

    $allowedExtensions = array_map("strtolower", $allowedExtensions);
        
    $this->allowedExtensions = $allowedExtensions;        
    $this->sizeLimit = $sizeLimit;
    $this->params = $params;
    
    $this->checkServerSettings();       

    if (isset($_GET['qqfile'])) {
      $this->file = new qqUploadedFileXhr($this->params);
    } elseif (isset($_FILES['qqfile'])) {
      $this->file = new qqUploadedFileForm($this->params);
    } else {
      $this->file = false; 
    }

  }
  
  private function checkServerSettings(){        

    $postSize = $this->toBytes(ini_get('post_max_size'));
    $uploadSize = $this->toBytes(ini_get('upload_max_filesize'));        
    
    if ($postSize < $this->sizeLimit || $uploadSize < $this->sizeLimit){
      $size = max(1, $this->sizeLimit / 1024 / 1024) . 'M';             
      //die("{'error':'increase post_max_size and upload_max_filesize to $size'}");    
    }        

  }
  
  private function toBytes($str){

    $val = trim($str);
    $last = strtolower($str[strlen($str)-1]);
    switch($last) {
      case 'g': $val *= 1024;
      case 'm': $val *= 1024;
      case 'k': $val *= 1024;        
    }
    return $val;

  }
  
  /**
   * Returns array('success'=>true) or array('error'=>'error message')
   */
  function handleUpload($uploadDirectory, $url){

    if (!is_writable($uploadDirectory)){
      return array('error' => "Server error. Upload directory isn't writable.");
    }
    
    if (!$this->file){
      return array('error' => 'No files were uploaded.');
    }
    
    $size = $this->file->getSize();
    
    if ($size == 0) {
      return array('error' => 'File is empty');
    }
    
    if ($size > $this->sizeLimit) {
      return array('error' => 'File is too large');
    }
    
    $pathinfo = pathinfo($this->file->getName());
    $filename = $pathinfo['filename'];
    //$filename = md5(uniqid());
    $ext = $pathinfo['extension'];

    if($this->allowedExtensions && !in_array(strtolower($ext), $this->allowedExtensions)){
      $these = implode(', ', $this->allowedExtensions);
      return array('error' => 'File has an invalid extension, it should be one of '. $these . '.');
    }
    
    try {
      if ($fileName = $this->file->save($uploadDirectory)) {
        if (br()->request()->get('tw') && br()->request()->get('th')) {
          $thumbnail = br()->images()->thumbnail($url . $fileName, br()->request()->get('tw'), br()->request()->get('th'));
        } else {
          $thumbnail = '';
        }
        return array( 'success'   => true
                    , 'url'       => $url . $fileName
                    , 'fileName'  => $fileName
                    , 'fileSize'  => filesize($uploadDirectory . $fileName)
                    , 'thumbnail' => $thumbnail
                    );
      } else {
        return array('error'=> 'Could not save uploaded file. The upload was cancelled, or server error encountered');
      }
    } catch (Exception $e) {
      return array('error' => $e->getMessage());
    }
      
  }   
     
}

class BrImageUploadHandler extends BrGenericUploadHandler {

  function __construct($params = array()) {

    $params['allowedExtensions'] = array('jpeg', 'jpg', 'gif', 'png');

    parent::__construct($params);

  }

}

