<?php

/**
 * Project:     Breeze framework
 * Author:      Jager Mesh (jagermesh@gmail.com)
 *
 * @version 1.1.0.0
 * @package Breeze Core
 */

require_once(dirname(__FILE__).'/BrObject.php');

class BrImage extends BrObject {

  private $filePath;
  private $image;
  private $format;
  private $dpi;

  function imageLibSupported() {

    return ((function_exists("ImageCreateFromGIF")) and
            (function_exists("ImageCreateFromJPEG")) and
            (function_exists("ImageCreateFromPNG")));

  }

  function __construct($path) {

    $this->image = null;

    $oldErrorReporting = error_reporting();
    error_reporting(0);

    if ($this->imageLibSupported()) {
      switch(br()->fs()->fileExt($path)) {
        case 'png':
          br()->log('Trying open ' . $path . ' as PNG');
          if ($this->image = @ImageCreateFromPNG($path)) {
            $this->format = "png";
          }
          break;
        case 'jpg':
        case 'jpeg':
          br()->log('Trying open ' . $path . ' as JPG');
          if ($this->image = @ImageCreateFromJPEG($path)) {
            $this->format = "jpg";
          }
          break;
        case 'gif':
          br()->log('Trying open ' . $path . ' as GIF');
          if ($this->image = @ImageCreateFromGIF($path)) {
            $this->format = "gif";
          }
          break;
        default:
          br()->log('Trying open ' . $path . ' as PNG');
          $this->image = ImageCreateFromPNG($path);
          if ($this->image) {
            $this->format = "png";
          } else {
            br()->log('Trying open ' . $path . ' as JPEG');
            $this->image = ImageCreateFromJPEG($path);
            if ($this->image) {
              $this->format = "jpg";
            } else {
              br()->log('Trying open ' . $path . ' as GIF');
              $this->image = ImageCreateFromGIF($path);
              if ($this->image) {
                $this->format = "gif";
              }
            }            
          }
          break;
      }
    } else {
      throw new Exception('It seems GD is not installed.');
    }

    if ($this->image) {
      $this->width = imagesx($this->image);
      $this->height = imagesy($this->image);
    } else {
      throw new Exception($path . ' is invalid image file.');
    }

    $this->filePath = $path;

    error_reporting($oldErrorReporting);

  }

  function image() {

    return $this->image;

  }

  function format() {
    
    return $this->format;

  } 

  function width() { 
    
    return $this->width; 
      
  }
  
  function height() { 
    
    return $this->height; 
      
  }

  function thumbnail($w, $h, $dstPath) {
    
    $cw = $this->width();
    $ch = $this->height();

    $format = $this->format();
    $image = $this->image();

    if ($cw > $w) {
      $new_width = $w;
      $new_height = round($ch * ($new_width * 100 / $cw) / 100);

      if ($new_height > $h) {
        $new_height_before = $new_height;
        $new_height = $h;
        $new_width = round($new_width * ($new_height * 100 / $new_height_before) / 100);
      } 
    } else 
    if ($ch > $h) {
      $new_height = $h;
      $new_width = round($cw * ($new_height * 100 / $ch) / 100);

      if ($new_width > $w) {
        $new_width_before = $new_width;
        $new_width = $w;
        $new_height = round($new_height * ($new_width * 100 / $new_width_before) / 100);
      }
    } else {
      $new_width = $w;
      $new_height = round($ch * ($new_width * 100 / $cw) / 100);

      if ($new_height > $h) {
        $new_height_before = $new_height;
        $new_height = $h;
        $new_width = round($new_width * ($new_height * 100 / $new_height_before) / 100);
      } 
    }

    if (function_exists("ImageCreateTrueColor"))
      $new_image = ImageCreateTrueColor($new_width, $new_height);
    else
      $new_image = ImageCreate($new_width, $new_height);
      
    if (function_exists("imagecopyresampled")) {
      if ($format == "png" || $format == "gif") {
        imagecolortransparent($new_image, imagecolorallocatealpha($new_image, 0, 0, 0, 127));
      } else {
        imagecolortransparent($new_image, imagecolorallocate($new_image, 0, 0, 0));
      }
      imagealphablending($new_image, false);
      imagesavealpha($new_image, true);
      @imagecopyresampled ( $new_image
                          , $image
                          , 0
                          , 0
                          , 0
                          , 0
                          , $new_width
                          , $new_height
                          , $cw
                          , $ch
                          );
    } else {
      @imagecopyresized ( $new_image
                        , $image
                        , 0
                        , 0
                        , 0
                        , 0
                        , $new_width
                        , $new_height
                        , $cw
                        , $ch
                        );
    }

    switch ($format) {
      case "jpg":
        imageJPEG($new_image, $dstPath, 750);
        break;
      case "png":
        imagePNG($new_image, $dstPath);
        break;
      case "gif":
        imageGIF($new_image, $dstPath);
        break;
      default:
        throw new Exception('Unknown image format');
        break;
    }

  }
   
}

