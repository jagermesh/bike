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

  function startTransaction() {
          
  }

  function commitTransaction() {     
     
  }

  function rollbackTransaction() {
          
  }
  
  function now() {

    return $this->toDateTime(mktime());

  }

  // function to_date($date, $format = "dmy") {

  //   if (is_string($date)) 
  //     if (is_numeric($date)) 
  //       return date("Y-m-d", $date);
  //     else  
  //       return date("Y-m-d", str_to_date($date, array("mode" => "m", "date_format" => $format)));
  //   else
  //     return date("Y-m-d", $date);

  // }

  // function to_datetime($date, $format = "dmy") {

  //   if (is_string($date)) 
  //     if (is_numeric($date)) 
  //       return date("Y-m-d H:i:s", $date);
  //     else
  //       return date("Y-m-d H:i:s", str_to_date($date, array("mode" => "m", "date_format" => $format)));
  //   else
  //     return date("Y-m-d H:i:s", $date);

  // }
  
  // function to_time($time) {

  //   if (is_string($time)) 
  //     if (is_numeric($time))
  //       return date("H:i:s", $time);
  //     else
  //       return date("H:i:s", str_to_date($time, array("mode" => "t")));
  //   else
  //     return date("H:i:s", $time);

  // }

  // function from_date($date) {

  //   $date_arr = preg_split("~[-: ]~i", $date);
  //   for ($i = min(count($date_arr), 6); $i < 6; $i++)
  //     if ($i < 3)
  //       $date_arr[$i] = 1;
  //     else
  //       $date_arr[$i] = 0;
  //   return mktime(0, 0, 0, $date_arr[1], $date_arr[2], $date_arr[0]);

  // }

  // function from_datetime($date) {

  //   $date_arr = preg_split("~[-: ]~", $date);
  //   for ($i = min(count($date_arr), 6); $i < 6; $i++)
  //     if ($i < 3)
  //       $date_arr[$i] = 1;
  //     else
  //       $date_arr[$i] = 0;
  //   return mktime($date_arr[3], $date_arr[4], $date_arr[5], $date_arr[1], $date_arr[2], $date_arr[0]);

  // }

  // function from_time($time) {

  //   $array = preg_split("~:~", $time);
  //   for ($i = min(count($array), 3); $i < 3; $i++)
  //     $array[$i] = 0;
  //   return mktime($array[0], $array[1], $array[2], 1, 1, 1);

  // }

  // function now() {

  //   return $this->to_datetime(mktime());

  // }

  // function now_date() {

  //   return $this->to_date(mktime());

  // }

  // function now_time() {

  //   return $this->to_time(mktime());

  // }
  
  // function encrypt_key($key) {

  //   if ($key)
  //     return encrypt_num($key);
  //   else
  //     return null;    

  // }

  // function decrypt_key($key) {

  //   if ($key)
  //     return decrypt_num($key);
  //   else
  //     return null;
      
  // }
    
}
