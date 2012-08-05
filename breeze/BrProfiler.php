<?php

/**
 * Project:     Breeze framework
 * Author:      Jager Mesh (jagermesh@gmail.com)
 *
 * @version 1.1.0.0
 * @package Breeze Core
 */

require_once(dirname(__FILE__).'/BrSingleton.php');

class BrProfiler extends BrSingleton {

  private $profilingTargets = array();

  function start($name) {

    $this->profilingTargets[$name] = br()->getMicrotime();

    return $this->profilingTargets[$name];

  }

  function logStart($name) {

    $this->start($name);
    // br()->log()->writeLn('[PROFILER:' . $name . ']', 'PRF');

  }

  function finish($name) {

    return (br()->getMicroTime() - $this->profilingTargets[$name]);

  }

  function logFinish($name) {

    br()->log()->writeLn('[PROFILER:' . $name . '] ' . br()->durationToString($this->finish($name)), 'PRF');

  }

}

