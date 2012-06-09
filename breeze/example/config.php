<?php

br()->defaultConfig();

br()->config()->set( 'db'
                   , array( 'engine'   => '[mysql|mongo]'
                          , 'name'     => '[databaseName]' 
                          , 'username' => '[username]'
                          , 'password' => '[password]'
                          ));

// auth
// br()->config()->set('br/auth/db/table', 'user');
// br()->config()->set('br/auth/db/login-field', 'username');
// br()->config()->set('br/auth/db/password-field', 'paswd');
// br()->config()->set('br/auth/db/login-field-label', 'Username');

// br()->config()->set('br/auth/api/signupDiabled', true);

// br()->config()->set('br/auth/db/api/select-user', 'anyone login');
// br()->config()->set('br/auth/db/api/update-user', 'anyone');
// br()->config()->set('br/auth/db/api/remove-user', 'anyone');

// br()->config()->set('br/Br/sendMail/from', 'noreply@nowhere.com');
// br()->config()->set('br/BrErrorHandler/exceptionHandler/sendErrorsTo', br()->config()->get('br/Br/sendMail/from'));
