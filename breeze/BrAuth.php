<?php

/**
 * Project:     Breeze framework
 * Author:      Jager Mesh (jagermesh@gmail.com)
 *
 * @version 1.1.0.0
 * @package Breeze Core
 */

require_once(dirname(__FILE__).'/BrSingleton.php');

class BrAuth extends BrSingleton {

  var $isDbSynced = false;

  function isLoggedIn() {
    
    return br()->session()->get('login');
    
  }

  function checkLogin($returnNotAuthorized = true) {
 
    $loginField = br()->config()->get('br/auth/db/login-field', 'login');
    $passwordField = br()->config()->get('br/auth/db/password-field', 'password');

    $login = $this->getLogin();
    if (!$login) {
      if ($cookie = json_decode(br($_COOKIE, 'BrAuth'))) {
        if (br()->db() && @$cookie->{'login'} && @$cookie->{'token'}) {
          $users = br()->db()->table(br()->config()->get('br/auth/db/table', 'users'));
          if ($login = $users->findOne( array( $loginField    => $cookie->{'login'} ))) {
            if (($password = br($login, $passwordField)) && ($rowid = br()->db()->rowidValue($login))) {
              $token = sha1(md5(sha1($password) . sha1($rowid)));
              if ($token == $cookie->{'token'}) {
                $this->isDbSynced = true;
                br()->auth()->setLogin($login);
                return $login;
              }
            }
          }
        }
      }
      if ($returnNotAuthorized) {
        br()->response()->sendNotAuthorized();       
      }
    }
    return $login;
  
  }

  function setLogin($login, $remember = false) {

    $loginField = br()->config()->get('br/auth/db/login-field', 'login');
    $passwordField = br()->config()->get('br/auth/db/password-field', 'password');
 
    if ($remember) {
      $password = br($login, $passwordField);
      $rowid = br()->db()->rowidValue($login);
      $token = sha1(md5(sha1($password) . sha1($rowid)));
      $cookie = array( 'login'    => br($login, $loginField)
                     , 'token'    => $token
                     );
      setcookie( 'BrAuth'
               , json_encode($cookie)
               , time() + 60*60*24*30
               , br()->request()->baseUrl()
               , br()->request()->domain() == 'localhost' ? false : br()->request()->domain()
               );
    }
    $loginObj = $login;
    $loginObj['rowid'] = br()->db()->rowidValue($login);
    if (!$loginObj['rowid']) {
      throw new BrException('setLogin: login object must contain ID field');
    }

    return br()->session()->set('login', $login);

  }

  function getLogin($param = null, $default = null) {
    
    if ($login = br()->session()->get('login')) {
      if (!$this->isDbSynced && br()->db()) {
        $users = br()->db()->table(br()->config()->get('br/auth/db/table', 'users'));
        if ($login = $users->findOne(array(br()->db()->rowidField() => br()->db()->rowid($login)))) {
          br()->auth()->setLogin($login);
        }
        $this->isDbSynced = true;
      }
    }
    if ($login = br()->session()->get('login')) {
      $loginObj = $login;
      if (br()->db()) {
        $loginObj['rowid'] = br()->db()->rowidValue($login);
      }
      if ($param) {
        return br($loginObj, $param, $default);
      } else {
        return $loginObj;
      }
    } else {
      if ($param) {
        return $default;
      }
    }
    return null;

  }

  function clearLogin() {
    
    setcookie( 'BrAuth'
             , ''
             , time() - 60*60*24*30
             , br()->request()->baseUrl() 
             , br()->request()->domain() == 'localhost' ? false : br()->request()->domain()
             );

    return br()->session()->clear('login');

  }

  function findLogin($options = array()) {

    // Check permissions
    $userId      = br($options, 'userId');
    $accessLevel = br($options, 'accessLevel');

    if (!$userId) {
      if ($login = br()->auth()->getLogin()) {
        $userId = $login['rowid'];
        $accessLevel = 'full-access';  // TODO: different access levels
      }
    }

    if (!$userId) {
      if ($token = br()->request()->get('token')) {
        $table = br()->db()->table(br()->config()->get('br/auth/db/table', 'users'));
        if ($login = $table->findOne(array('adminToken' => $token))) {
          $userId = (string)$login['_id'];
          $accessLevel = 'full-access';
        } else
        if ($login = $table->findOne(array('token' => $token))) {
          $userId = (string)$login['_id'];
          $accessLevel = 'read-only';
        }
      }
    }

    return array('userId' => $userId, 'accessLevel' => $accessLevel);
    
  }

}
