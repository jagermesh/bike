<?php

class BikeInstaller {

  static function unpack($archive, $out_folder, $file_name = '') {

    $zip = zip_open($archive);
    while($zip_entry = zip_read($zip)) {
      $zip_entry_name = zip_entry_name($zip_entry);
      if ($zip_entry_name != '.DS_Store') {
        $zip_entry_size = zip_entry_filesize($zip_entry);
        if ($zip_entry_size == 0) {
          if (!file_exists($out_folder.$zip_entry_name) && !@mkdir($out_folder.$zip_entry_name)) {
            throw new Exception('Can not create folder <strong>'.$out_folder.$zip_entry_name.'</strong>. Permissions denied.');
          }
        } else {
          if (!$file_name || ($out_folder.$zip_entry_name == $file_name)) {
            if (!@file_put_contents($out_folder.$zip_entry_name, zip_entry_read($zip_entry, $zip_entry_size))) {
              throw new Exception('Can not unpack file <strong>'.$out_folder.$zip_entry_name.'</strong>. Permissions denied.');
            }
            if ($file_name) {
              break;
            }
          }
        }
      }
    }
    zip_close($zip);

  }

  static function redirect($url) {
    
    header("Location: $url");
    exit(); 

  }

  static function showError($error) {

    echo('
    <html>
    <title>Bike installer</title>
    <head>
    <style>
    div.error_panel { font-family: Arial; font-size: 12pt; margin: 10px; padding: 10px; border: #FC6 1px solid; background-color: #FFC; }
    div.error_panel div.title { font-family: Arial; font-size: 10pt; font-weight: bold; padding-bottom: 10px; margin: 0px; }
    div.error_panel div.fatal { font-family: Arial; float: right; padding: 5px; font-weight: bold; background-color: red; color: yellow; }
    div.error_panel div.trace { font-family: Courier; font-size: 10pt; padding: 10px; margin: 0px; background-color: #FEFEFE; border: 1px solid #BFBFBF; }
    div.error_panel div.error { font-family: Courier; font-size: 10pt; padding: 10px; margin-bottom: 10px; background-color: #EFEFEF; border: 1px solid #BFBFBF;}
    </style>
    </head>
    <body>
    ');

    echo('
    <div class="error_panel">
    There was an error during Bike installation:<br /><br />
    ');

    echo($error);

    echo('
    </div></body></html>
    ');


  }

  static function install() {

    try {

      if (version_compare(PHP_VERSION, '5.3.0', '<')) {
        throw new Exception('Sorry, but this package will run only on PHP 5.3.0+, your have PHP ' . PHP_VERSION);
      }

      $outFolder = __DIR__ . '/';

      if (!is_writeable($outFolder)) {
        throw new Exception('Please make sure folder <strong>' . $outFolder . '</strong> is writeable. We are going to unpack Bike there.');
      }

      if (!is_writeable($outFolder . '.htaccess')) {
        throw new Exception('Please make sure file <strong>' . $outFolder . '.htaccess</strong> is writeable. We are going to overwrite it during installation.');
      }

      if (!is_writeable($outFolder . 'index.php')) {
        throw new Exception('Please make sure file <strong>' . $outFolder . 'index.php</strong> is writeable. We are going to overwrite it during installation.');
      }

      self::unpack(__DIR__ . '/install.zip', $outFolder);

      $request = str_replace('/install.php', '/', @$_SERVER['REQUEST_URI']);

      self::redirect($request);
    } catch (Exception $e) {
      self::showError($e->getMessage());
    }

  }

}

BikeInstaller::install();
