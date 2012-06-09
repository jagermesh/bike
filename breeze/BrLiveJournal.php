<?php

/**
 * Project:     Breeze framework
 * Author:      Jager Mesh (jagermesh@gmail.com)
 *
 * @version 1.1.0.0
 * @package Breeze Core
 */

require_once(dirname(__FILE__).'/BrObject.php');

class BrLiveJournal extends BrObject {
  
  private $login;
  private $password;

  function __construct($login, $password) {

    $this->login = $login;
    $this->password = $password;
    
  }

  function post($title, $preface, $cutText, $body, $tags, $fields = array()) {
    
    $params = array( 'username'    => $this->login
                   , 'password'    => $this->password
                   , "subject"     => 'ТЕСТ2' //$title
                   , "event"       => $body
                   , "lineendings" => "unix"
                   , "ver"         => 1
                   , "year"        => date("Y", br($fields, 'date'))
                   , "mon"         => date("n", br($fields, 'date'))
                   , "day"         => date("j", br($fields, 'date'))
                   , "hour"        => date("G", br($fields, 'date'))
                   , "min"         => date("i", br($fields, 'date'))
                   //, 'security'    => 'private'
                   , 'props'       => array('taglist' => $tags)
                   );
    $request = '<?xml version="1.0"?> 
<methodCall> 
  <methodName>LJ.XMLRPC.postevent</methodName> 
  <params> 
    <param> 
      <value> 
        <struct> 
          <member> 
            <name>username</name> 
            <value> 
              <string>'.$this->login.'</string> 
            </value> 
          </member> 
          <member> 
            <name>password</name> 
            <value> 
              <string>'.$this->password.'</string> 
            </value> 
          </member> 
          <member> 
            <name>event</name> 
            <value> 
              <string><![CDATA['.$preface.'<br /><br /><lj-cut text="'.$cutText.'">'.$body.'</lj-cut>]]></string> 
            </value> 
          </member> 
          <member> 
            <name>subject</name> 
            <value> 
              <string>'.$title.'</string> 
            </value> 
          </member> 
          <member> 
            <name>lineendings</name> 
            <value> 
              <string>pc</string> 
            </value> 
          </member> 
          <member> 
            <name>year</name> 
            <value> 
              <int>'.date("Y", br($fields, 'date', time())).'</int> 
            </value> 
          </member> 
          <member> 
            <name>mon</name> 
            <value> 
              <int>'.date("n", br($fields, 'date', time())).'</int> 
            </value> 
          </member> 
          <member> 
            <name>day</name> 
            <value> 
              <int>'.date("j", br($fields, 'date', time())).'</int> 
            </value> 
          </member> 
          <member> 
            <name>hour</name> 
            <value> 
              <int>'.date("G", br($fields, 'date', time())).'</int> 
            </value> 
          </member> 
          <member> 
            <name>min</name> 
            <value> 
              <int>'.date("i", br($fields, 'date', time())).'</int> 
            </value> 
          </member> 
          <member>
               <name>props</name>
               <value>
                <struct>
                 <member>
                  <name>taglist</name>
                  <value>
                   <string>'.$tags.'</string>
                  </value>
                 </member>
                </struct>
               </value>
              </member>          
        </struct> 
      </value> 
    </param> 
  </params> 
</methodCall>';
    br()->log()->writeln('BrLiveJournal.post');
    br()->log()->writeln($request);
    $curl = curl_init("http://www.livejournal.com/interface/xmlrpc");
    curl_setopt($curl, CURLOPT_POSTFIELDS, $request);
    curl_setopt($curl, CURLOPT_HTTPHEADER, array("Content-Type: text/xml"));
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, TRUE);
    curl_setopt($curl, CURLOPT_TIMEOUT, 30);
    $response = curl_exec($curl);//debug($response);exit();
    curl_close($curl);
    if (preg_match('~<name>url</name><value><string>([^<]+?)</string>~ism', $response, $matches)) {
      $result = array();
      $result['url'] = $matches[1];
      return $result;
    } else
    if (preg_match('~<name>faultString</name><value><string>([^<]+?)</string>~ism', $response, $matches)) {
      throw new Exception($matches[1]);
    }

  }

}