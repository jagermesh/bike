<?php

if (!br()->config()->get('db')) {

  if (file_exists($fileName = dirname(dirname(dirname(dirname(__FILE__)))) . '/config.php') &&
      file_exists(dirname(dirname(dirname(dirname(__FILE__)))) . '/breeze/Breeze.php')) {

    require_once($fileName);

  }

}