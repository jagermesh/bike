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

class BrDataSourceNotFound extends BrException {
  
}

class BrDataSource extends BrObject {

  private $dbEntity;
  private $defaultOrder;
  private $events = array();
  private $canTraverseBack = null;
  private $checkTraversing = false;
  private $selectAdjancedRecords = false;
  private $priorAdjancedRecord = null;
  private $nextAdjancedRecord = null;
  private $rowidFieldName = null;

  function __construct($dbEntity, $options = array()) {

    $this->dbEntity              = $dbEntity;
    $this->defaultOrder          = br($options, 'defaultOrder');
    $this->skip                  = br($options, 'skip');
    $this->limit                 = br($options, 'limit');
    $this->checkTraversing       = br($options, 'checkTraversing');
    $this->selectAdjancedRecords = br($options, 'selectAdjancedRecords');
    $this->rowidFieldName        = br($options, 'rowidFieldName');
    $this->lastSelectAmount      = 0;

  }

  function dbEntity() {

    return $this->dbEntity;

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

  // private function callBefore($event, $operation, &$context1 = null, &$context2 = null) {

  //   $result = null;
  //   if ($events = br($this->events, $event)) {
  //     foreach($events as $func) {
  //       $result = $func($this, $operation, $context1, $context2);
  //     }
  //   }
  //   return $result;

  // }

  // function count($filter = array(), $fields = array(), $order = array()) {

  //   return $this->select($filter, $fields, $order, array('result' => 'count'));

  // }

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

    $this->callEvent('before:'.$event, $filter, $transientData, $options);
    $result = $this->callEvent($event, $filter, $transientData, $options);
    if (is_null($result)) {
      $result = array();
      $this->lastSelectAmount = 0;

      $table = br()->db()->table($this->dbEntity());

      if (!strlen($limit) || ($limit > 0)) {
        $cursor = $table->find($filter, $fields)->sort($order);
        if ($skip) {
          if ($this->selectAdjancedRecords) {
            $cursor = $cursor->skip($skip - 1);
          } else {
            $cursor = $cursor->skip($skip);          
          }
        }
        if (strlen($limit)) {
          if ($this->selectAdjancedRecords) {
            if ($skip) {
              $cursor = $cursor->limit($limit + 2);
            } else {
              $cursor = $cursor->limit($limit + 1);
            }
          } else 
          if ($this->checkTraversing) {
            $cursor = $cursor->limit($limit + 1);
          } else {
            $cursor = $cursor->limit($limit);
          }
        }

        if ($countOnly) {
          $result = $cursor->count();
        } else {
          //$this->lastSelectAmount = $cursor->count(true);
          $result = array();
          $idx = 1;
          $this->lastSelectAmount = 0;
          foreach($cursor as $row) {
            $row['rowid'] = br()->db()->rowidValue($row, $this->rowidFieldName);
            if ($this->selectAdjancedRecords && $skip && ($idx == 1)) {
              $this->nextAdjancedRecord = $row;
            } else
            if ($this->selectAdjancedRecords && (count($result) == $limit)) {
              $this->priorAdjancedRecord = $row;
              $this->lastSelectAmount++;
            } else
            if (!$limit || (count($result) < $limit)) {
              $this->callEvent('calcFields', $row, $transientData);
              $result[] = $row;
              $this->lastSelectAmount++;
            } else {
              $this->lastSelectAmount++;
            }
            $idx++;
          }
        }
      } else {

      }
    }
    return $result;      
    
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

  function update($rowid, $row, $filter = array()) {

    $table = br()->db()->table($this->dbEntity());

    $transientData = array();

    $filter[br()->db()->rowidField()] = br()->db()->rowid($rowid);

    if ($crow = $table->findOne($filter)) {

      br()->db()->startTransaction();

      $old = $crow;
      foreach($row as $name => $value) {
        $crow[$name] = $value;
      }

      $this->callEvent('before:update', $crow, $transientData, $filter);

      $result = $this->callEvent('update', $rowid, $crow, $old, $transientData);
      if (is_null($result)) {
        $table->save($crow);
        $crow['rowid'] = br()->db()->rowidValue($crow);
        $result = $crow;
        $this->callEvent('calcFields', $result, $transientData);
        $this->callEvent('after:update', $result, $transientData, $old);
      }

      br()->db()->commitTransaction();

      return $result;      
    } else {
      throw new BrDataSourceNotFound();
    }

  }

  function insert($row = array(), &$transientData = array()) {

    br()->db()->startTransaction();

    $this->callEvent('before:insert', $row, $transientData);
    $result = $this->callEvent('insert', $row, $transientData);
    if (is_null($result)) {
      $table = br()->db()->table($this->dbEntity());

      $table->insert($row);
      if (!br($row, 'rowid')) {
        $row['rowid'] = br()->db()->rowidValue($row);
      }
      $result = $row;
      $this->callEvent('calcFields', $result, $transientData);
      $this->callEvent('after:insert', $result, $transientData);
    }

    br()->db()->commitTransaction();

    return $result;      
    
  }

  function remove($rowid) {

    $table = br()->db()->table($this->dbEntity());

    $transientData = array();

    $filter = array();
    $filter[br()->db()->rowidField()] = br()->db()->rowid($rowid);

    $this->callEvent('before:remove', $rowid, $transientData);
    $result = $this->callEvent('remove', $rowid, $row, $transientData);
    if (is_null($result)) {
      if ($result = $table->findOne($filter)) {
        try {
          $table->remove($filter);
        } catch (Exception $e) {
          // TODO: Move to the DB layer
          if (preg_match('/1451: Cannot delete or update a parent row/', $e->getMessage())) {
            throw new Exception('Cannot delete this record - there are references to it in the system');
          } else {
            throw new Exception($e->getMessage());            
          }
        }
        $result['rowid'] = br()->db()->rowidValue($result);
        $this->callEvent('calcFields', $result, $transientData);
        $this->callEvent('after:remove', $result, $transientData);
      } else {
        throw new BrDataSourceNotFound();
      }
    }
    return $result;
    
  }

  function invokeMethodExists($method) {

    return /*method_exists($this, $method) || */br($this->events, $method);

  }
  
  function invoke($method, $params) {

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
          $transientData = array();

          $this->callEvent('before:' . $method, $params, $transientData);
          $result = $this->callEvent($method, $params, $transientData);
          if (!$result) {
            if (method_exists($this, $method)) {
              $result = $this->$method($params);
              if ($result) {
                $this->callEvent('after:' . $method, $result, $params, $transientData);
              }
            }
          }
          return $result;
        }    
        break;
    }

    
  }
  
}