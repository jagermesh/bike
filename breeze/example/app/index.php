<?php

br()
  ->request()
    ->routeIndex(function() {
      echo('Hello World');
    })
    ->routeDefault()
;

