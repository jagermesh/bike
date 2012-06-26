<?php

br()->importLib('DataSource');

class BrDataSourceUsers extends BrDataSource {

  function __construct() {

    $loginField = br()->config()->get('br/auth/db/login-field', 'login');

    parent::__construct(br()->config()->get('br/auth/db/table', 'users'), array('defaultOrder' => $loginField));

    $this->on('signup', function($dataSource, $params) { 

      if (br()->config()->get('br/auth/api/signupDiabled')) {

        throw new Exception('Sorry. Signup is currently disabled.');

      } else {

        $data = array();

        $row = $dataSource->insert($params, $data);

        $passwordField = br()->config()->get('br/auth/db/password-field', 'password');

        br()->log()->writeLn('User registered:');
        br()->log()->writeLn($row);
        br()->log()->writeLn($data);

        if ($mailTemplate = br()->config()->get('br/auth/mail/signup/template')) {
          $emailField = br()->config()->get('br/auth/db/email-field', 'email');
          if ($email = br($row, $emailField)) {
            br()->importAtBasePath('breeze/3rdparty/phpmailer/class.phpmailer.php');
            $mail = new PHPMailer(true);
            try {
              $mail->AddReplyTo(br()->config()->get('br/auth/mail/from', 'noreply@localhost'));
              $mail->AddAddress($email);
              $mail->SetFrom(br()->config()->get('br/auth/mail/from', 'noreply@localhost'));
              $mail->Subject = br()->config()->get('br/auth/mail/signup/subject', 'Registration complete');
              $user = $row;
              $user[$passwordField] = $data['password'];
              $message = br()->renderer()->fetch($mailTemplate, $user);
              $mail->MsgHTML($message, dirname($mailTemplate));
              br()->log()->writeLn('Sending signup mail to ' . $email);
              if ($mail->Send()) {
                br()->log()->writeLn('Sent');
              } else {
                throw new Exception('Mail was not sent because of unknown error');
              }
            } catch (phpmailerException $e) {
              br()->log()->writeLn('Can not send signup mail to ' . $email . '. Error: ' . $e->getMessage());
            } catch (Exception $e) {
              br()->log()->writeLn('Can not send signup mail to ' . $email . '. Error: ' . $e->getMessage());
            }
          } else {
            br()->log()->writeLn('Signup mail was not sent - email field not found or empty');
          }
        } else {
          br()->log()->writeLn('Signup mail was not sent - mail template not found or empty');
        }

        br()->auth()->setLogin($row);

        unset($row[$passwordField]);

        $row['loginToken'] = br()->guid();
        $row['expiresAt'] = time() + 60 * 60;

        return $row;

      }


      //br()->response()->redirect('?from=signup&password='.$data['password']);

    });

    $this->on('insert', function($dataSource, &$row, &$data) { 

      $loginField = br()->config()->get('br/auth/db/login-field', 'login');
      $loginFieldLabel = br()->config()->get('br/auth/db/login-field-label', 'login');
      $passwordField = br()->config()->get('br/auth/db/password-field', 'password');

      if ($login = br()->html2text(br($row, $loginField))) {
        if ($user = $dataSource->selectOne(array($loginField => $login))) {
          throw new Exception('Such user already exists');
        } else {
          if (!br($row, $passwordField)) {
            $data['password'] = substr(br()->guid(), 0, 8);
          } else {
            $data['password'] = $row[$passwordField];
          }
          $row[$passwordField]   = md5($data['password']);
          //$row['token']      = br()->guid();
          //$row['adminToken'] = br()->guid();
        }
      } else {
        throw new Exception('Please enter ' . $loginFieldLabel);
      }

    });

    $this->on('select', function($dataSource, &$filter) {

      $security = br()->config()->get('br/auth/db/api/select-user');

      if (!$security) {
        $security = 'login';
      }

      if (strpos($security, 'login') !== false) {
        $login = br()->auth()->getLogin();
        if (!$login) {
          throw new Exception('You are not allowed to see users');
        }
        if (strpos($security, 'anyone') === false) {
          $filter[br()->db()->rowidField()] = br()->db()->rowid($login);
        }
      } else
      if (strpos($security, 'anyone') === false) {
        throw new Exception('You are not allowed to see users');
      }

    });

    $this->before('update', function($dataSource, $row) { 

      if ($login = br()->auth()->getLogin()) {
        if (br()->config()->get('br/auth/db/api/update-user') != 'anyone') {
          if (br()->db()->rowid($login) != br()->db()->rowid($row)) {
            throw new Exception('You are not allowed to modify this user');
          }
        }
      } else {
        throw new Exception('You are not allowed to modify this user');
      }

    });

    $this->before('remove', function($dataSource, $row) { 

      if ($login = br()->auth()->getLogin()) {
        if (br()->config()->get('br/auth/db/api/remove-user') != 'anyone') {
          if (br()->db()->rowid($login) != br()->db()->rowid($row)) {
            throw new Exception('You are not allowed to remove this user');
          }
        }
      } else {
        throw new Exception('You are not allowed to remove this user');
      }

    });

    $this->before('update', function($dataSource, &$row) { 

      $passwordField = br()->config()->get('br/auth/db/password-field', 'password');

      if (isset($row[$passwordField])) {
        if ($row[$passwordField]) {
          $row[$passwordField] = md5($row[$passwordField]);
        } else {
          unset($row[$passwordField]);
        }
      }

    });

    $this->before('update', function($dataSource, &$row) { 

      $loginField = br()->config()->get('br/auth/db/login-field', 'login');
      $loginFieldLabel = br()->config()->get('br/auth/db/login-field-label', 'login');
      
      if (isset($row[$loginField])) {
        if ($login = br()->html2text($row[$loginField])) {
          if ($user = $dataSource->selectOne(array($loginField => $login, br()->db()->rowidField() => array('$ne' => br()->db()->rowid($row))))) {
            throw new Exception('Such user already exists');
          } else {
          }
        } else {
          throw new Exception('Please enter ' . $loginFieldLabel);
        }
      }

    });

    $this->on('login', function($dataSource, $params) { 

      $loginField = br()->config()->get('br/auth/db/login-field', 'login');
      $passwordField = br()->config()->get('br/auth/db/password-field', 'password');

      if (br($params, $loginField) && br($params, $passwordField)) {
        $filter = array( $loginField    => $params[$loginField]
                       , $passwordField => md5($params[$passwordField]) 
                       );
        $dataSource->callEvent('before:loginSelectUser', $params, $filter);
        if ($row = $dataSource->selectOne($filter)) {
          $denied = false;
          if ($dataSource->invokeMethodExists('isAccessDenied')) {
            $denied = $dataSource->invoke('isAccessDenied', $row);
          }
          if (!$denied) {
            $row[$passwordField] = md5($params[$passwordField]);
            br()->auth()->setLogin($row, br($params, 'remember'));

            unset($row[$passwordField]);

            $row['loginToken'] = br()->guid();
            $row['expiresAt'] = time() + 60 * 60;

            return $row;

          } else {
            throw new Exception('Access denied');
          }
        } else {
          throw new Exception('Invalid login/password or user not found');
        }
      } else {
        throw new Exception('Please enter login/password');
      }

    });

    $this->on('logout', function($dataSource, $params) { 

      br()->auth()->clearLogin();

      return true;

    });

    $this->on('calcFields', function($dataSource, &$row) { 

      $passwordField = br()->config()->get('br/auth/db/password-field', 'password');

      if ($login = br()->auth()->getLogin()) {
        if (br()->config()->get('br/auth/db/api/select-user') != 'anyone') {
          if (br()->db()->rowid($row) != $row['rowid']) {
            unset($row['adminToken']);
            unset($row['token']);
          }
        }
      } else {
        unset($row['adminToken']);
        unset($row['token']);
      }

      unset($row[$passwordField]);

    });

    $this->on('remindPassword', function($dataSource, $params) { 

      $loginField = br()->config()->get('br/auth/db/login-field', 'login');
      $loginFieldLabel = br()->config()->get('br/auth/db/login-field-label', 'login');
      $passwordField = br()->config()->get('br/auth/db/password-field', 'password');

      if ($login = br($params, $loginField)) {
        if ($user = $dataSource->selectOne(array($loginField => $login))) {
          $emailField = br()->config()->get('br/auth/db/email-field', 'email');
          if ($email = br($user, $emailField)) {
            if ($mailTemplate = br()->config()->get('br/auth/mail/forgot-password/template')) {
              br()->importAtBasePath('breeze/3rdparty/phpmailer/class.phpmailer.php');
              $mail = new PHPMailer(true);
              try {
                $mail->AddReplyTo(br()->config()->get('br/auth/mail/from', 'noreply@localhost'));
                $mail->AddAddress($login);
                $mail->SetFrom(br()->config()->get('br/auth/mail/from', 'noreply@localhost'));
                $mail->Subject = br()->config()->get('br/auth/mail/forgot-password/subject', 'Registration complete');
                $data = array();
                $data[$passwordField] = substr(br()->guid(), 0, 8);                
                $user[$passwordField] = $data[$passwordField];
                $data[$passwordField] = md5($data[$passwordField]);
                //$data['token']        = br()->guid();
                //$data['adminToken']   = br()->guid();
                $message = br()->renderer()->fetch($mailTemplate, $user);
                $mail->MsgHTML($message, br()->templatesPath());
                if ($mail->Body) {
                  if ($mail->Send()) {
                    $dataSource->update(br()->db()->rowidValue($user), $data);
                    br()->log()->writeLn('New password sent to ' . $email);
                    br()->log()->writeLn($user);
                    return true;
                  } else {
                    throw new Exception('Mail was not sent because of unknown error');
                  }
                } else {
                  throw new Exception('We can not send you new password because mail template is empty');
                }
              } catch (phpmailerException $e) {
                throw new Exception(br()->html2text($e->getMessage()));
              } catch (Exception $e) {
                throw new Exception(br()->html2text($e->getMessage()));
              }
            } else {
              throw new Exception('We can not reset your password - there is no mail template for this');
            }
          } else {
            throw new Exception('We can not reset your password - email field not found or empty');
          }
        } else {
          throw new Exception('User not found');
        }
      } else {
        throw new Exception('Please enter ' . $loginFieldLabel);
      }

    });

  }

}

//br()->registerDataSource($usersDataSource);

br()->importLib('RESTBinder');

class BrRESTUsersBinder extends BrRESTBinder {

  private $params;
  private $usersDataSource;

  function __construct($usersDataSource, $params = array()) {

    $this->usersDataSource = $usersDataSource;
    $this->params = $params;

    parent::__construct();

  }

  function doRouting() {

    $loginField = br()->config()->get('br/auth/db/login-field', 'login');

    parent::route( '/api/users'
                 , $this->usersDataSource
                 , array( 'security' => array( 'invoke' => ''
                                             , '.*'     => 'login'
                                             )
                        , 'filterMappings' => array( array( 'get'    => 'keyword'
                                                          , 'type'   => 'regexp'
                                                          , 'fields' => array($loginField)
                                                          )
                                                   , array( 'get'    => 'status'
                                                          , 'field'  => 'status'
                                                          )
                                                   )
                        , 'allowEmptyFilter' => true
                        )
                 );

  }

}
