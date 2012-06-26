<?php

/**
 * Project:     Breeze framework
 * Author:      Jager Mesh (jagermesh@gmail.com)
 *
 * @version 1.1.0.0
 * @package Breeze Core
 */

require_once(dirname(__FILE__).'/BrObject.php');

class BrRESTBinder extends BrObject {

  private $continueRoute = true;

  function doRouting() {

  }

  function route($path, $dataSource = null, $options = array()) {

    if (!br()->request()->routeComplete()) {

      if (is_object($path)) {

        $path->doRouting();

      } else {

        $method = strtolower(br()->request()->get('crossdomain', br()->request()->method()));

        switch($method) {
          case "put":
            return $this->routeAsPUT($path, $dataSource, $options);
            break;
          case "post":
            return $this->routeAsPOST($path, $dataSource, $options);
            break;
          case "delete":
            return $this->routeAsDELETE($path, $dataSource, $options);
            break;
          default:
            return $this->routeAsGET($path, $dataSource, $options);
            break;
        }

      }

    }

    return $this;
    
  }

  function routeGET($path, $dataSource, $options = array()) {

    $method = strtolower(br()->request()->get('crossdomain', br()->request()->method()));
    if (!$method || ($method == 'get')) {
      $this->routeAsGET($path, $dataSource, $options);
    }

    return $this;

  }

  function routePOST($path, $dataSource, $options = array()) {

    $method = strtolower(br()->request()->get('crossdomain', br()->request()->method()));
    if ($method == 'post') {      
      $this->routeAsPOST($path, $dataSource, $options);
    }

    return $this;
    
  }

  function routePUT($path, $dataSource, $options = array()) {
    
    $method = strtolower(br()->request()->get('crossdomain', br()->request()->method()));
    if ($method == 'put') {      
      $this->routeAsPUT($path, $dataSource, $options);
    }
    
    return $this;

  }

  function routeDELETE($path, $dataSource, $options = array()) {

    $method = strtolower(br()->request()->get('crossdomain', br()->request()->method()));
    if ($method == 'delete') {      
      $this->routeAsDELETE($path, $dataSource, $options);
    }
    
    return $this;

  }

  private function checkPermissions($options, $methods) {

    $permissions   = br()->auth()->findLogin($options);
    $userId        = $permissions['userId'];
    $accessLevel   = $permissions['accessLevel'];
    $securityRules = br($options, 'security');
    $result        = 'login';

    if (is_array($securityRules)) {

      $found = false;

      foreach($methods as $method) {
        if (array_key_exists($method, $securityRules)) {
          $result = $securityRules[$method];
          $found = true;
        }          
        if ($found) {
          break;
        }
      }

      if (!$found) {
        foreach($securityRules as $regexp => $value) {
          if ($regexp == '*') {
            $regexp = '.*';
          }
          foreach($methods as $method) {
            if (@preg_match('~'.$regexp.'~', $method)) {
              $result = $value;
              $found = true;
              break;
            }
          }
          if ($found) {
            break;
          }
        }
      }

    } else {
      $result = $securityRules;
    }

    if ($result && (!$userId || !$accessLevel)) {
      if (br()->request()->get('crossdomain')) {
        br()->response()->sendJSONP('Not Authorized');
      } else {
        br()->response()->sendNotAuthorized();
      }
    }

  }

  function routeAsGET($path, $dataSource, $options = array()) {

    if (br()->request()->at($path)) {

      br()->request()->continueRoute(false);

      $permissions = br()->auth()->findLogin($options);
      $userId      = $permissions['userId'];
      $accessLevel = $permissions['accessLevel'];
      $security    = br($options, 'security');

      $event = 'select';
      if ($matches = br()->request()->at(rtrim($path, '/').'/([0-9a-z]+)')) {
        $event = 'selectOne';
      }

      $this->checkPermissions($options, array($event, 'select'));

      $filter = array();
      if ($filterMappings = br($options, 'filterMappings')) {
        foreach($filterMappings as $mapping) {
          $get = is_array($mapping['get'])?$mapping['get']:array($mapping['get']);
          foreach($get as $getParam) {
            $value = br()->request()->get($getParam);
            if ($value || (is_string($value) && strlen($value))) {
              $fields = br($mapping, 'field', br($mapping, 'fields', $getParam));
              switch(br($mapping, 'type', '=')) {
                case "=":
                  if (is_array($value)) {
                    if (br($value, '$ne')) {
                      $filter[$fields] = array('$ne' => $value['$ne']);
                    } else
                    if (br($value, '$in')) {
                      $filter[$fields] = array('$in' => $value['$in']);
                    } else {
                      $filter[$fields] = array('$in' => $value);
                    }
                  } else
                  if ($value == 'null') {
                    $filter[$fields] = null;//array('$exists' => -1);
                  } else
                  if ($value == '$null') {
                    $filter[$fields] = null;//array('$exists' => -1);
                  } else {
                    $filter[$fields] = $value;
                  }
                  break;
                case "regexp":
                  if (is_array($fields)) {
                    $subFilter = array();
                    foreach($fields as $field) {
                      $subFilter[] = array($field => br()->db()->regexpCondition($value));
                    }
                    $filter['$or'] = $subFilter;
                  } else {
                    $filter[$fields] = br()->db()->regexpCondition($value);
                  }
                  break;
                case "filter":
                  $filters = br($mapping, 'filters', br($mapping, 'filter'));
                  if (!is_array($filters)) {
                    $filters = array($filters);
                  }
                  foreach($filters as $filter) {
                    foreach($filter as $filterField => $filterValue) {
                      $filter[$filterField] = $filterValue;
                    }
                  }
                  break;
                case "date:month":
                  $startMonth = new MongoDate(strtotime($value.'-01'));
                  br()->importLib('DateTime');
                  $dateTime = new BrDateTime($startMonth->sec);
                  $dateTime->incMonth();
                  $endMonth = new MongoDate($dateTime->asDate());
                  $filter[$fields] = array('$gte' => $startMonth, '$lt' => $endMonth);
                  break;
              }
            }
          }
        }
      }

      $selectOne = false;
      if ($matches = br()->request()->at(rtrim($path, '/').'/([0-9a-z]+)')) {
        $filter[br()->db()->rowidField()] = br()->db()->rowid($matches[1]);
        $selectOne = true;
      }

      $dataSourceOptions = array();

      if (br()->request()->get('__limit')) {
        $dataSourceOptions['limit'] = br()->request()->get('__limit');
      }

      if (br()->request()->get('__skip')) {
        $dataSourceOptions['skip'] = br()->request()->get('__skip');
      }

      if (br()->request()->get('__fields')) {
        $dataSourceFields = br()->request()->get('__fields');
      } else {
        $dataSourceFields = array();
      }

      try {
        if (br()->request()->get('__result') == 'count') {
          $result = $dataSource->selectCount($filter);
        } else {
          $allowEmptyFilter = br($options, 'allowEmptyFilter');
          
          if (!$filter && !$allowEmptyFilter) {
            $dataSourceOptions['limit'] = 0;
          }

          if ($selectOne) {
            $result = $dataSource->selectOne($filter, $dataSourceFields, array(), $dataSourceOptions);
          } else {
            $result = $dataSource->select($filter, $dataSourceFields, array(), $dataSourceOptions);
          }
        }
        if (br()->request()->get('crossdomain')) {
          br()->response()->sendJSONP($result);
        } else {
          br()->response()->sendJSON($result);          
        }
      } catch (Exception $e) {
        br()->log()->logException($e);
        if (br()->request()->get('crossdomain')) {
          br()->response()->sendJSONP($e->getMessage());
        } else {
          br()->response()->sendForbidden($e->getMessage());
        }
      }

    }

    return $this;    

  }

  function routeAsPOST($path, $dataSource, $options = array()) {

    if (br()->request()->at(rtrim($path, '/'))) {

      $method = $method = br()->request()->get('__method');
      if (!$method) {
        if ($matches = br()->request()->at(rtrim($path, '/').'/([a-zA-Z]+)/$')) {
          $method = $matches[1];
        }
      }

      br()->request()->continueRoute(false);

      if ($method) {

        $this->checkPermissions($options, array($method, 'invoke'));

        $row = array();
        if (br()->request()->isPOST()) {
          $data = br()->request()->post();
        } else {
          $data = br()->request()->get();
        }
        foreach($data as $name => $value) {
          if (!is_array($value)) {
            $value = trim($value);
          }
          $row[$name] = $value;
        }
        try {
          $result = $dataSource->invoke($method, $row);
          if (br()->request()->get('crossdomain')) {
            br()->response()->sendJSONP($result);
          } else {
            br()->response()->sendJSON($result);          
          }
        } catch (Exception $e) {
          br()->log()->logException($e);
          if (br()->request()->get('crossdomain')) {
            br()->response()->sendJSONP($e->getMessage());
          } else {
            br()->response()->sendForbidden($e->getMessage());
          }
        }
      } else
      if ($matches = br()->request()->at(rtrim($path, '/').'/([0-9a-z]+)')) {

        // br()->request()->continueRoute(false);

        $this->checkPermissions($options, array('update'));

        $row = array();
        if (br()->request()->isPOST()) {
          $data = br()->request()->post();
        } else {
          $data = br()->request()->get();
        }
        if (br($data, '__values')) {
          $data = $data['__values'];
        }
        foreach($data as $name => $value) {
          if (!is_array($value)) {
            $value = trim($value);
          }
          $row[$name] = $value;
        }
        try {
          $result = $dataSource->update($matches[1], $row);
          if (br()->request()->get('crossdomain')) {
            br()->response()->sendJSONP($result);
          } else {
            br()->response()->sendJSON($result);          
          }
        } catch (BrDataSourceNotFound $e) {
          br()->log()->logException($e);
          if (br()->request()->get('crossdomain')) {
            br()->response()->sendJSONP('Record not found');
          } else {
            br()->response()->send404();            
          }
        } catch (Exception $e) {
          br()->log()->logException($e);
          if (br()->request()->get('crossdomain')) {
            br()->response()->sendJSONP($e->getMessage());
          } else {
            br()->response()->sendForbidden($e->getMessage());
          }
        }

      } else {

        if (br()->request()->get('crossdomain')) {
          br()->response()->sendJSONP('Method not allowed');
        } else {
          br()->response()->sendMethodNotAllowed();
        }

      }

    }

    return $this;

  }

  function routeAsPUT($path, $dataSource, $options = array()) {

    if ($matches = br()->request()->at($path)) {

      br()->request()->continueRoute(false);

      $this->checkPermissions($options, array('insert'));

      $row = array();
      if (br()->request()->isPUT()) {
        $data = br()->request()->put();
      } else {
        $data = br()->request()->get();
      }
      if (br($data, '__values')) {
        $data = $data['__values'];
      }
      foreach($data as $name => $value) {
        if (!is_array($value)) {
          $value = trim($value);
        }
        $row[$name] = $value;
      }
      try {
        $result = $dataSource->insert($row);
        if (br()->request()->get('crossdomain')) {
          br()->response()->sendJSONP($result);
        } else {
          br()->response()->sendJSON($result);          
        }
      } catch (Exception $e) {
        br()->log()->logException($e);
        if (br()->request()->get('crossdomain')) {
          br()->response()->sendJSONP($e->getMessage());
        } else {
          br()->response()->sendForbidden($e->getMessage());
        }
      }
    }

    return $this;
    
  }

  function routeAsDELETE($path, $dataSource, $options = array()) {

    if ($matches = br()->request()->at($path)) {

      br()->request()->continueRoute(false);

      if ($matches = br()->request()->at(rtrim($path, '/').'/([0-9a-z]+)')) {


        $this->checkPermissions($options, array('remove', 'delete'));

        try {
          $result = $dataSource->remove($matches[1]);
          if (br()->request()->get('crossdomain')) {
            br()->response()->sendJSONP($result);
          } else {
            br()->response()->sendJSON($result);          
          }
        } catch (BrDataSourceNotFound $e) {
          br()->log()->logException($e);
          if (br()->request()->get('crossdomain')) {
            br()->response()->sendJSONP('Record not found');
          } else {
            br()->response()->send404();            
          }
        } catch (Exception $e) {
          br()->log()->logException($e);
          if (br()->request()->get('crossdomain')) {
            br()->response()->sendJSONP($e->getMessage());
          } else {
            br()->response()->sendForbidden($e->getMessage());
          }
        }

      } else {

        if (br()->request()->get('crossdomain')) {
          br()->response()->sendJSONP('Method not allowed');
        } else {
          br()->response()->sendMethodNotAllowed();
        }

      }

    }

    return $this;
    
  }

}