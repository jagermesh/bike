<?php

require_once(dirname(__DIR__).'/Breeze.php');
require_once(__DIR__.'/BrJSCompressor.php');

$scriptsPath = dirname(__DIR__) . '/js/';
$resultScriptFile = dirname(__DIR__) . '/js/breeze.js';
$packedScriptFile = dirname(__DIR__) . '/js/breeze.min.js';

br()->log('Working in ' . $scriptsPath);

$result = '';
$result .= br()->fs()->loadFromFile($scriptsPath . 'br.typecheck.js');
$result .= br()->fs()->loadFromFile($scriptsPath . 'br.storage.js');
$result .= br()->fs()->loadFromFile($scriptsPath . 'br.request.js');
$result .= br()->fs()->loadFromFile($scriptsPath . 'br.datasource.js');
$result .= br()->fs()->loadFromFile($scriptsPath . 'br.datagrid.js');
$result .= br()->fs()->loadFromFile($scriptsPath . 'br.datacombo.js');
$result .= br()->fs()->loadFromFile($scriptsPath . 'br.editable.js');
$result .= br()->fs()->loadFromFile($scriptsPath . 'br.js');
$result .= br()->fs()->loadFromFile($scriptsPath . 'br.ui.js');
$result .= br()->fs()->loadFromFile($scriptsPath . 'br.user.js');

br()->log('Saving to ' . $resultScriptFile);
br()->fs()->saveToFile($resultScriptFile, $result);

br()->log('Saving to ' . $packedScriptFile);
$compressor = new BrJSCompressor();
br()->fs()->saveToFile($packedScriptFile, $compressor->compress($result));
