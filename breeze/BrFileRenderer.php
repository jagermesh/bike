<?php

/**
 * Project:     Breeze framework
 * Author:      Jager Mesh (jagermesh@gmail.com)
 *
 * @version 1.1.0.0
 * @package Breeze Core
 */

require_once(dirname(__FILE__).'/BrGenericRenderer.php');

class BrFileRenderer extends BrGenericRenderer {
  
  function fetch($templateName, $subst = array()) {

    $result = '';
    $templateFile = $templateName;
    if (!file_exists($templateFile)) {
      $templateFile = br()->atTemplatesPath($templateName);
    }
    if (file_exists($templateFile)) {
      ob_start();
      @include($templateFile);
      $result = ob_get_contents();
      ob_end_clean();
    }

    // replace @template-name with template
    while (preg_match('/[{]@([^}]+)[}]/', $result, $matches)) {
      $result = str_replace($matches[0], $this->fetch(dirname($templateFile).'/'.$matches[1], $subst), $result);
    }

    $result = $this->compile($result, $subst, dirname($templateName));

    return $result;
    
  }

}
