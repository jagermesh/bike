<?php

if (file_exists(dirname(dirname(dirname(__FILE__))).'/breeze/Breeze.php')) {
  require_once(dirname(dirname(dirname(__FILE__))).'/breeze/Breeze.php'));
} else {
  require_once(dirname(__FILE__).'/breeze/Breeze.php');  
}
