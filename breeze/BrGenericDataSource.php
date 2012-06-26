<?php

/**
 * Project:     Breeze framework
 * Author:      Jager Mesh (jagermesh@gmail.com)
 *
 * @version 1.1.0.0
 * @package Breeze Core
 */

require_once(dirname(__FILE__).'/BrObject.php');
require_once(dirname(__FILE__).'/BrException.php');

class BrGenericDataSource extends BrObject {

  protected $defaultOrder;
  protected $events = array();
  protected $canTraverseBack = null;
  protected $checkTraversing = false;
  protected $selectAdjancedRecords = false;
  protected $priorAdjancedRecord = null;
  protected $nextAdjancedRecord = null;
  protected $rowidFieldName = null;

  function __construct($options = array()) {

    $this->defaultOrder          = br($options, 'defaultOrder');
    $this->skip                  = br($options, 'skip');
    $this->limit                 = br($options, 'limit');
    $this->checkTraversing       = br($options, 'checkTraversing');
    $this->selectAdjancedRecords = br($options, 'selectAdjancedRecords');
    $this->rowidFieldName        = br($options, 'rowidFieldName');
    $this->lastSelectAmount      = 0;

  }

  function selectOne($filter = array(), $fields = array(), $order = array()) {

    if ($result = $this->select($filter, $fields, $order, array('limit' => 1))) {
      $result = $result[0];  
    }
    return $result; 

  }

  function selectCount($filter = array()) {

    return $this->select($filter, array(), array(), array('result' => 'count'));

  }

  function select($filter = array(), $fields = array(), $order = array(), $options = array()) {

    $countOnly = (br($options, 'result') == 'count');
    $limit = $this->limit = br($options, 'limit');
    $skip = $this->skip = br($options, 'skip');

    $transientData = array();

    $this->lastSelectAmount = null;
    $this->priorAdjancedRecord = null;
    $this->nextAdjancedRecord = null;

    if (!$order) {
      if ($this->defaultOrder) {
        if (is_array($this->defaultOrder)) {
          $order = $this->defaultOrder;
        } else {
          $order[$this->defaultOrder] = 1;
        }
      }
    }
    $event = ($limit == 1) ? 'selectOne' : 'select';

    $result = $this->callEvent($event, $filter, $transientData, $options);

    return $result;      
    
  }

  function update($rowid, $row, &$transientData = array()) {

    $row['rowid'] = $rowid;

    return $this->callEvent('update', $row, $transientData);

  }

  function insert($row = array(), &$transientData = array()) {

    return $this->callEvent('insert', $row, $transientData);
    
  }

  function remove($rowid, &$transientData = array()) {

    $row = array('rowid' => $rowid);

    return $this->callEvent('remove', $row, $transientData);
    
  }

  function invokeMethodExists($method) {

    return br($this->events, $method);

  }
  
  function invoke($method, $params, &$transientData = array()) {

    $method = trim($method);

    switch($method) {
      case 'select':
      case 'selectOne':
      case 'insert':
      case 'update':
      case 'remove':
      case 'calcFields':
        throw new Exception('Method [' . $method . '] not supported');
        break;
      default:
        if (!$this->invokeMethodExists($method)) {
          throw new Exception('Method [' . $method . '] not supported');
        } else {
          $this->callEvent('before:' . $method, $params, $transientData);
          $result = $this->callEvent($method, $params, $transientData);
          if ($result) {
            $this->callEvent('after:' . $method, $result, $params, $transientData);
          }
          return $result;
        }    
        break;
    }
    
  }
 
  public function canTraverseBack() {

    return $this->lastSelectAmount > $this->limit;

  }

  public function canTraverseForward() {

    return $this->skip > 0;

  }

  public function priorAdjancedRecord() {

    return $this->priorAdjancedRecord;

  }

  public function nextAdjancedRecord() {

    return $this->nextAdjancedRecord;

  }

  function before($event, $func) {

    $events = preg_split('~[,]~', $event);
    foreach($events as $event) {
      $this->events['before:'.$event][] = $func;
    }

  }

  function on($event, $func) {
    
    $events = preg_split('~[,]~', $event);
    foreach($events as $event) {
      $this->events[$event][] = $func;
    }

  }

  function after($event, $func) {
    
    $events = preg_split('~[,]~', $event);
    foreach($events as $event) {
      $this->events['after:'.$event][] = $func;
    }

  }

  public function callEvent($event, &$context1, &$context2 = null, &$context3 = null, &$context4 = null) {

    $result = null;
    if ($events = br($this->events, $event)) {
      foreach($events as $func) {
        $result = $func($this, $context1, $context2, $context3);
      }
    }
    return $result;

  }

}