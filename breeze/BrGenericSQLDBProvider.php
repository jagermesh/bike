<?php

/**
 * Project:     Breeze framework
 * Author:      Jager Mesh (jagermesh@gmail.com)
 *
 * @version 1.1.0.0
 * @package Breeze Core
 */

require_once(dirname(__FILE__).'/BrGenericDBProvider.php');

class BrGenericSQLDBProvider extends BrGenericDBProvider {

  function getCountSQL($sql) {
    
    $offset = 0; 
    if (preg_match('/(^[ \t\n]*|[ (])(SELECT)([ \n\r])/sim', $sql, $token, PREG_OFFSET_CAPTURE)) {
      $select_offset = $token[2][1];
      $offset = $select_offset + 6;
      $work_str = substr($sql, $offset);
      $in_select = 0;
      while (preg_match('/((^[ \t\n]*|[ (])(SELECT)([ \n\r])|([ \t\n])(FROM)([ \n\r]))/sim', $work_str, $token, PREG_OFFSET_CAPTURE)) {
        if (strtolower(@$token[6][0]) == 'from') {
          if ($in_select)
            $in_select--;
          else {
            $from_offset = $offset + $token[6][1];
            break; 
          }
          $inc = $token[6][1] + 4;
          $offset += $inc;
          $work_str = substr($work_str, $inc);
        }
        if (strtolower(@$token[3][0]) == 'select') {
          $in_select++;
          $inc = $token[3][1] + 6;
          $offset += $inc;
          $work_str = substr($work_str, $inc);
        }
      }
    }

    if (isset($select_offset) && isset($from_offset)) {
      $sql_start  = substr($sql, 0, $select_offset);
      $sql_finish = substr($sql, $from_offset + 4);
      $sql = $sql_start."SELECT COUNT(1) FROM".$sql_finish;
      $sql = preg_replace("/ORDER BY.+/sim", "", $sql, 1); 
      return $sql;
    } else
      return null;
      
  }

}
