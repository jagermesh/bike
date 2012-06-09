<?php

/**
 * Project:     Breeze framework
 * Author:      Jager Mesh (jagermesh@gmail.com)
 *
 * @version 1.1.0.0
 * @package Breeze Core
 */

require_once(dirname(__FILE__).'/BrRSSArticle.php');
require_once(dirname(__FILE__).'/BrObject.php');

class BrRSSFeed extends BrObject {

  public $Articles = array();
  public $XMLParserError = null;
  
  private $XMLParser = null;
  private $Article = null;
  private $CurrentTag = null;
  private $InsideData = false;
  private $InsideArticle = false;
  
  function parseUrl($Url) {
  
    if ($Content = @get_file_contents($Url)) {
    
      return $this->Parse($Conent);  
      
    }
    
  }
  
  function parse($Content) {
  
    $this->Articles = array();
    
    $this->XMLParser = xml_parser_create();

    xml_parser_set_option($this->XMLParser, XML_OPTION_TARGET_ENCODING, "UTF-8").
    
    xml_set_object($this->XMLParser, $this);
    xml_set_element_handler($this->XMLParser, 'start_element', 'end_element');
    xml_set_character_data_handler($this->XMLParser, 'content');
   
    try {
      if (!xml_parse($this->XMLParser, $Content)) {
        $this->XMLParserError = 'Line '.xml_get_current_line_number($this->XMLParser).': '.(xml_get_error_code($this->XMLParser)?xml_error_string(xml_get_error_code($this->XMLParser)):'Unknown error');
      } 
    } catch (Exception $e) {
      $this->XMLParserError = $e->getMessage();
    }    
    
    xml_parser_free($this->XMLParser);
    
    return (!$this->XMLParserError && count($this->Articles));
    
  }
  
  function start_element($parser, $name, $attrs = array()) {

    if ($this->InsideArticle) {
      $this->CurrentTag = $name;
    }

    switch ($name) {
      case 'ITEM':
        $this->Article = new BrRSSArticle();
        $this->InsideArticle = true;
        break;
    }    
    
  }

  function content($parser, $data) {

    if ($this->InsideArticle) {
      switch ($this->CurrentTag) {
        case 'TITLE':
          $this->Article->Title .= $data;
          break;
        case 'LINK':
          $this->Article->Link = $data;
          break;
        case 'GUID':
          $this->Article->GUID = $data;
          break;
        case 'DESCRIPTION':
        case 'FULLTEXT':
          $this->Article->Description .= $data;
          break;
        case 'PUBDATE':
          $this->Article->PubDate = strtotime($data);
          break;
        case 'COMMENTS':
          $this->Article->Comments = $data;
          break;
        case 'AUTHOR':
          $this->Article->Author = $data;
          break;
        case 'CATEGORY':
          $this->Article->Categories[] = $data;
          break;
      }
      $this->InsideData = true;
    }
    
  }
  
  function end_element($parser, $name) {
  
    switch ($name) {
      case 'ITEM':
        $this->Articles[] = $this->Article;
        $this->InsideArticle = false;
        break;
    }    
    $this->InsideData = false;
    $this->CurrentTag = null;
    
  }
  
}
