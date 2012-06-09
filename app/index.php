<?php

require_once(dirname(__FILE__).'/cms/identify.php');

br()->config()->set('configPath', dirname(dirname(__FILE__)) . '/config.db.php');

br()
  ->request()
    ->route('/saved', function() {
      br()->renderer()->display('saved.html');
    })
    ->routeIndex(function() {
      br()->renderer()->display('query.html');
    })
    ->routeDefault()
;

