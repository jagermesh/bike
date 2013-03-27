<?php

require_once(dirname(__FILE__).'/cms/identify.php');
require_once(dirname(__FILE__).'/api/queriesds.php');

br()->config()->set('configPath', dirname(dirname(__FILE__)) . '/config.db.php');

br()
  ->request()
    ->route('/export.html[?]hash=(.+)', function($matches) use ($queriesDataSource) {
      if ($filter = br()->session()->get($matches[1])) {
        if ($sql = br($filter, 'sql')) {
          $query = br()->db()->getCursor($sql);
          $first = true;
          $idx = 0;
          echo('<html><title>Export</title>');
          echo('<head><style> td { padding-right:10px; } td.header { font-weight: bold; border-bottom: 2px solid black; } </style></head><body>');
          echo('<pre>' . $sql . '</pre>');
          echo('<table cellspacing="0" cellpadding="0">');
          foreach($query as $row) {
            echo('<tr>');
            if ($first) {
              foreach($row as $name => $value) {
                echo('<td class="header"><pre>' . $name . '</pre></th>');
              }
              $first = false;
              echo('</tr><tr>');
            }
            foreach($row as $name => $value) {
              echo('<td><pre>' . htmlspecialchars($value, ENT_COMPAT, 'UTF-8') . '</pre></td>');
            }
            echo('</tr>');
            $idx++;
          }
          echo('</table>');
          echo('<pre><strong>' . $idx . '</strong> record(s) total</pre><br />');
          echo('</body></html>');
        }
      }
    })
    ->routeIndex(function() {
      br()->renderer()->display('query.html');
    })
    ->routeDefault()
;

