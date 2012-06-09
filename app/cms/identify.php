<?php

if (!br()->config()->get('db')) {

  require_once(dirname(__FILE__).'/check-wordress.php');
  require_once(dirname(__FILE__).'/check-joomla.php');
  require_once(dirname(__FILE__).'/check-drupal.php');
  require_once(dirname(__FILE__).'/check-finecms.php');
  require_once(dirname(__FILE__).'/check-breeze.php');
  require_once(dirname(__FILE__).'/check-generic2.php');

}