<?php

br()->defaultConfig();

// Folder for saved queries
br()->config()->set('savedQueriesPath', dirname(__FILE__) . '/user/');
br()->config()->set('libraryQueriesPath', dirname(__FILE__) . '/library/');

br()->importAtBasePath('config.db.php');

require_once(dirname(__FILE__) . '/app/cms/identify.php');
