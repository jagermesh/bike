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
require_once(dirname(__FILE__).'/BrGenericDataSource.php');

class BrDataSourceNotFound extends BrException {
  
}

class BrDataSource extends BrGenericDataSource {

  private $dbEntity;

  function __construct($dbEntity, $options = array()) {

    $this->dbEntity              = $dbEntity;

    parent::__construct($options);

  }

  function dbEntity() {

    return $this->dbEntity;

  }


  function select($filter = array(), $fields = array(), $order = array(), $options = array()) {

    $countOnly = (br($options, 'result') == 'count');
    $limit = $this->limit = br($options, 'limit');
    $skip = $this->skip = br($options, 'skip');

    $transientData = array();

    $event = ($limit == 1) ? 'selectOne' : 'select';

    $this->callEvent('before:'.$event, $filter, $transientData, $options);

    $this->lastSelectAmount = null;
    $this->priorAdjancedRecord = null;
    $this->nextAdjancedRecord = null;

    $sortOrder = br($options, 'order');
    if (!$sortOrder) {
      $sortOrder = $order;
    }
    if (!$sortOrder) {
      $sortOrder = $this->defaultOrder;
    }
    if ($sortOrder) {
      if (!is_array($sortOrder)) {
        $sortOrder = array($sortOrder => 1);
      }
    }

    $result = $this->callEvent($event, $filter, $transientData, $options);
    if (is_null($result)) {
      $result = array();
      $this->lastSelectAmount = 0;

      $table = br()->db()->table($this->dbEntity());

      if (!strlen($limit) || ($limit > 0)) {
        $cursor = $table->find($filter, $fields)->sort($sortOrder);
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

  function update($rowid, $row, &$transientData = array()) {

    $table = br()->db()->table($this->dbEntity());

    $filter = array();
    $filter[br()->db()->rowidField()] = br()->db()->rowid($rowid);

    if ($crow = $table->findOne($filter)) {

      br()->db()->startTransaction();

      $old = $crow;
      foreach($row as $name => $value) {
        $crow[$name] = $value;
      }

      $this->callEvent('before:update', $crow, $transientData, $old);

      $result = $this->callEvent('update', $crow, $transientData, $old);
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

    $this->callEvent('before:insert', $row, $transientData);

    $result = $this->callEvent('insert', $row, $transientData);
    if (is_null($result)) {

      br()->db()->startTransaction();

      $table = br()->db()->table($this->dbEntity());

      $table->insert($row);
      $row['rowid'] = br()->db()->rowidValue($row);
      $result = $row;      
      $this->callEvent('calcFields', $result, $transientData);
      $this->callEvent('after:insert', $result, $transientData);

      br()->db()->commitTransaction();
    }

    return $result;      
    
  }

  function remove($rowid, &$transientData = array()) {

    $table = br()->db()->table($this->dbEntity());

    $filter = array();
    $filter[br()->db()->rowidField()] = br()->db()->rowid($rowid);

    if ($crow = $table->findOne($filter)) {

      br()->db()->startTransaction();

      $this->callEvent('before:remove', $crow, $transientData);

      $result = $this->callEvent('remove', $crow, $transientData);
      if (is_null($result)) {
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
        $crow['rowid'] = br()->db()->rowidValue($crow);
        $result = $crow;
        $this->callEvent('calcFields', $result, $transientData);
        $this->callEvent('after:remove', $result, $transientData);
      }

      br()->db()->commitTransaction();

      return $result;
    } else {
      throw new BrDataSourceNotFound();
    }
    
  }
  
}