<?php

require_once(dirname(__DIR__).'/Breeze.php');

$scriptsPath = dirname(dirname(__DIR__)) . '/js/';
$templatesPath = dirname(dirname(__DIR__)) . '/templates/';

if ($tableName = br($argv, 1)) {

  logme('Generating code for ' . $tableName);

  $data = array();
  $data['entityName'] = $tableName;
  $data['fields'] = array();

  $configFile = dirname(dirname(__DIR__)).'/config.php';
  if (file_exists($configFile)) {
    logme('Loading settings from '.$configFile);
    require_once($configFile);
    if (br()->db()) {
      $fields = br()->db()->getTableStructure($tableName);
      foreach($fields as $name => $desc) {
        $desc['fieldName'] = $name;
        $data['fields'][] = $desc;
      }
    }
  }

  // debug($data);

  br()->fs()->saveToFile($scriptsPath.$tableName.'.js', br()->renderer()->fetch(__DIR__.'/template.databrowser.js', $data));
  br()->fs()->saveToFile($templatesPath.$tableName.'.html', br()->renderer()->fetch(__DIR__.'/template.databrowser.html', $data));

} else {

  logme('Table not specified');

}

