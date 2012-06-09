<?php

/**
 * Project:     Breeze framework
 * Author:      Jager Mesh (jagermesh@gmail.com)
 *
 * @version 1.1.0.0
 * @package Breeze Core
 */

require_once(dirname(__FILE__).'/BrObject.php');
  
class BrDateTime extends BrObject {
  
  var $weekday;
  var $day;
  var $month;
  var $year;
  var $hour;
  var $minute;
  var $second;
  
  function __construct($date = null) {
    
    $this->set($date);
    
  }
  
  function set($date = null) {
    
    if (!$date)
      $date = mktime();

    $date_parts = explode('-', date('d-m-Y-N-H-i-s-D', $date));

    $this->day          = $date_parts[0];
    $this->month        = $date_parts[1];
    $this->year         = $date_parts[2];
    $this->weekday      = $date_parts[3];
    $this->hour         = $date_parts[4];
    $this->minute       = $date_parts[5];
    $this->second       = $date_parts[6];
    $this->weekday_name = $date_parts[7];
    
  }
  
  function setDay($day) {

    $this->day = $day;
    $this->set($this->asDateTime());
    
  }
  
  function setMonth($month) {

    $this->month = $month;
    $this->set($this->asDateTime());
    
  }

  function asDateTime() {
  
    return mktime($this->hour, $this->minute, $this->second, $this->month, $this->day, $this->year);
    
  }
  
  function asDate() {
    
    return mktime(0, 0, 0, $this->month, $this->day, $this->year);
    
  }
  
  function incDay($increment = 1) {
    
    $this->day += $increment;
    $this->set($this->asDateTime());
    return $this;

  }
  
  function incHour($increment = 1) {
    
    $this->hour += $increment;
    $this->set($this->asDateTime());
    return $this;

  }

  function incMinute($increment = 1) {
    
    $this->minute += $increment;
    $this->set($this->asDateTime());
    return $this;

  }

  function incSec($increment = 1) {
    
    $this->second += $increment;
    $this->set($this->asDateTime());
    return $this;

  }

  function incMonth($increment = 1) {
    
    $this->month += $increment;
    $this->set($this->asDateTime());
    return $this;

  }
  
  function incYear($increment = 1) {
    
    $this->year += $increment;
    $this->set($this->asDateTime());
    return $this;

  }
  
  function decDay($decrement = 1) {
    
    $this->day -= $decrement;
    $this->set($this->asDateTime());
    return $this;

  }

  function decMonth($decrement = 1) {
    
    $this->month -= $decrement;
    $this->set($this->asDateTime());
    return $this;

  }

  function daysBetween($date = null, $with_sign = false) {
    
    if (!$date)
      $date = mktime();
    $date = new date_time($date);
    $diff = ($this->as_date() - $date->as_date())/60/60/24;
    if (!$with_sign) {
      $diff = abs($diff);
    }  
    return $diff;

  }

  function minutesBetween($date = null) {
    
    if (!$date)
      $date = mktime();
    $date = new BrDateTime($date);
    $diff = abs($this->asDateTime() - $date->asDateTime())/60;
    return $diff;

  }
  
  function secondsBetween($date = null) {
    
    if (!$date)
      $date = mktime();
    $date = new BrDateTime($date);
    $diff = abs($this->asDateTime() - $date->asDateTime());
    return $diff;

  }

  function differenceToString($date = null) {
    
    return $this->secondsToString($this->secondsBetween($date));
    
  }
  
  function secondsToString($diff = null) {
    
    $result = '';
    
    if ($diff >= 60*60*24) {
      $days = round($diff/60/60/24);
      if ($days == 1)
        $result = $days.' day';
      else  
        $result = $days.' days';
    }
    
    if ($hours = ltrim(date("H", mktime(0, 0, $diff)), '0')) {
      if (($hours == 1) ||  ($hours == 21))
        $result .= ' '.$hours.' hour';
      else  
        $result .= ' '.$hours.' hours';
    }

    if ($minutes = ltrim(date("i", mktime(0, 0, $diff)), '0')) {
      if (($minutes == 1) ||  ($minutes == 21) || ($minutes == 31) || ($minutes == 41) || ($minutes == 51))
        $result .= ' '.$minutes.' minute';
      else
        $result .= ' '.$minutes.' minutes';
    }    
    
    //.' часов';
    //$custom['expiration_term'] .= ' '.date("i", mktime(0, $date_time->minutes_between(), 0)).' минут';
    return trim($result);
    
  }

  function hoursBetween($date = null) {
    
    if (!$date)
      $date = mktime();
    $date = new BrDateTime($date);
    $diff = abs($this->asDateTime() - $date->asDateTime())/60/60;
    return $diff;

  }

  function daysTill($date = null) {
    
    if (!$date)
      $date = mktime();
    $date = new BrDateTime($date);
    $diff = ($this->as_date() - $date->as_date())/60/60/24;
    return $diff;

  }

  function weeksBetween($date = null, $with_sign = false) {

    $days_beetween = $this->daysBetween($date, $with_sign);
    if ($with_sign) {
      if ($days_beetween >= 0) {
        $days_beetween -= $this->weekday;
      } else {
        $days_beetween += $this->weekday;
      }
    } else {
      $days_beetween -= $this->weekday;
    }
    
    return round($days_beetween / 7);

  }

  function monthsBetween($date = null, $with_sign = false) {
    
    if (!$date) {
      $date = mktime();
    }
    $date = new BrDateTime($date);
    $diff = (($this->year * 12 + $this->month) - ($date->year * 12 + $date->month));
    if (!$with_sign) {
      $diff = abs($diff);
    }
    return $diff;

  }
  
  function isSameDate($date) {

    return ($date->as_date() == $this->as_date());
    
  }
  
  function equalTo($dateTime) {

    return ($dateTime->asDateTime() == $this->asDateTime());
    
  }

  function isToday() {
    
    $today = new BrDateTime();
    return $this->isSameDate($today);
    
  }

  function isYesterday() {

    $yesterday = new BrDateTime();
    $yesterday->decDay(1);
    return $this->isSameDate($yesterday);

  }
  
  function isTomorrow() {

    $yesterday = new BrDateTime();
    $yesterday->incDay(1);
    return $this->isSameDate($yesterday);

  }

  function isThisWeek() {

    $today = new BrDateTime();
    $days_between = $this->daysBetween(null, true);
    
    if ($days_between < 0) {
      return (abs($days_between) < $today->weekday);
    } else {
      return ($today->weekday + $days_between <= 7);
    }

  }

  function isWeekend() {

    return $this->weekday > 5;  
    
  }

  function isThisMonth() {

    $today = new BrDateTime();
    return ($today->year == $this->year) && ($today->month == $this->month);

  }

  function isPastWeek() {

    $today = new BrDateTime();
    $days_between = $this->daysBetween(null, true);
    
    return ($days_between < 0) && ((abs($days_between) - $today->weekday) >= 0) && ((abs($days_between) - $today->weekday) < 7);

  }

  function isNextWeek() {

    $today = new BrDateTime();
    $days_between = $this->daysBetween(null, true);
    
    return ($days_between > 0) && ($today->weekday + $days_between > 7) && ($today->weekday + $days_between < 14);

  }

  function isThisYear() {

    $today = new BrDateTime();
    return ($today->year == $this->year);

  }
  
  function daysInCurrentMonth() {
    
    $date = new BrDateTime($this->asDateTime());
    $date->day = 1;
    $date->incMonth();
    $date->decDay();
    return $date->day;
    
  }
  
  function toString() { 
     
    return strftime('%H:%M, %d %B, %Y', $this->asDateTime());
    
  }

  function toDateString() { 
     
    return strftime('%d %B, %Y', $this->asDateTime());
    
  }
  
  function toMySQLDateString() {
     
    return date('Y-m-d', $this->asDateTime());
    
  }

  function toMySQLDString() {
     
    return date('Y-m-d H:i:s', $this->asDateTime());
    
  }

  function toTimeMarker() {
    
    if ($this->isToday())
      $result = trn('Today');
    else  
    if ($this->isYesterday())
      $result = trn('Yesterday');
    else  
    if ($this->isTomorrow())
      $result = trn('Tomorrow');
    else  
    if ($this->isThisWeek())
      $result = trn('This week');
    else  
    if ($this->isPastWeek())
      $result = trn('Past week');
    else  
    if ($this->isNextWeek())
      $result = trn('Next week');
    else  
    if ($this->weeksBetween() < 5) {
      if ($this->weeksBetween(null, true) < 0) {
        $result = sprintf(trn('%d weeks ago'), $this->weeksBetween());
      } else {
        $result = sprintf(trn('In next %d weeks'), $this->weeksBetween());
      }        
    } else  
    if ($this->monthsBetween() < 13) {
      if ($this->monthsBetween(null, true) < 0) {
        if ($this->monthsBetween(null, true) == -1) {
          $result = trn('In past month');
        } else {
          $result = sprintf(trn('%d months ago'), $this->monthsBetween());
        }
      } else {
        if ($this->monthsBetween(null, true) == 1) {
          $result = trn('In next month');
        } else {
          $result = sprintf(trn('In next %d months'), $this->monthsBetween());
        }        
      }
    } else  
      $result = trn("Year ago");
      
    return $result;
      
  }
  
}

?>
