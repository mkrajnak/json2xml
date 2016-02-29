#!/usr/bin/php
<?php

  $TESTINFILENAME = "/home/matt/Projects/ipp/json2xml/example.json";
  $TESTOUTFILENAME = "/home/matt/Projects/ipp/json2xml/example.xml";
  $GENERATEHEADER = true;

  define('XMLHEADER', '<?xml version="1.0" encoding="UTF-8" ?>'); //standard xml header
  define('INPUTRGX', '/^(--input=.+)$/');
  define('OUTPUTRGX', '/^(--output=.+)$/');
  define('MATCHINFILENAME', '/^--input=(.+)$/');
  define('MATCHOUTFILENAME', '/^--output=(.+)$/');


  arg_check($argv,$argc);
  echo "$TESTINFILENAME\n";
  echo "$TESTOUTFILENAME\n";
  $json_data = json_read();
  print_r($json_data);
  write_json_to_xml($json_data);

  /**
  * Will parse and return filename from given string
  */
  function get_filename($filter,$string){
    preg_match($filter,$string,$matches);
    return $matches[1];
  }

  /**
  * Check args passed to script, also calling functions handling all the functonality of script
  */
  function arg_check($argv,$argc){
    $parse_error = false;
    foreach ($argv  as $param_count => $value) {

      if ($param_count == 0) continue; //TODO: handle stdin and

      if ($value == "--help" && $param_count === 1) {   //help was called, exiting with 0
        help();
      }

      if ($value == "-n" ){
          $GLOBALS['GENERATEHEADER'] = false;
          continue;
      }
      if (preg_match(INPUTRGX, $value) === 1 ) {
        $GLOBALS['TESTINFILENAME'] = get_filename(MATCHINFILENAME,$value);
        continue;
      }
      if (preg_match(OUTPUTRGX, $value) === 1 ) {
        $GLOBALS['TESTOUTFILENAME'] = get_filename(MATCHOUTFILENAME,$value);
        continue;
      }
      $parse_error = true;
    }
    if ($parse_error) {
      err("Invalid parameters, try --help for more",1); // TODO: Check value again. should be 1
    }
  }

  /**
  * will read content of json file
  * return json content in array
  */
  function json_read(){
    $raw_json = file_get_contents($GLOBALS['TESTINFILENAME']);
    $json_data = json_decode($raw_json,false);
    return $json_data;
  }

  /**
  * Will open a file, and writes converted json to file
  */
  function write_json_to_xml($json_data){

    $xml = new XMLWriter();
    $xml->openMemory();
    $xml->setIndent(true);
    if ($GLOBALS['GENERATEHEADER']) {
      $xml->startDocument('1.0', 'UTF-8');
    }

    writeXML($json_data,$xml);
    $xml->endDocument();

    print_r($xml->outputMemory(TRUE));
  //   $out_file = fopen($GLOBALS['TESTOUTFILENAME'], "w");
  //   fwrite($out_file,$xml->outputMemory(TRUE));
  //   fclose($out_file);
    }

  /**
  * Recursively writes arrays, object, in the end data
  */
  function writeXML($json_data,$xml){

    foreach ($json_data as $key => $value) {
      if (is_object($value)) {
        $xml->startElement($key);     //<key>
        writeXML($value,$xml);
        $xml->endElement();             //</key>
      }
      else if (is_array($value)) {
        $xml->startElement("$key");     //<item>
        $xml->startElement("array");    //<key>
        writeArray($value,$xml);
        $xml->endElement();             //</array>
        $xml->endElement();             //<key>
        }
      else{
        $xml->startElement($key);       //wrap with <key>
        write_value($value,$xml);
        $xml->endElement();             //<key>
    }
  }
}

/**
* Iterating through array
*/
function writeArray($field,$xml){
  for ($i=0; $i < count($field); $i++) {
    $xml->startElement("item");     //<item>
    foreach ($field[$i] as $key => $value) {
      $xml->startElement("$key");       //<$key>
      write_value($value,$xml);
      $xml->endElement();             //</key>
    }
    $xml->endElement();             //</item>
  }
}



  /**
  * Correctly writes data values to xml
  */
  function write_value($value,$xml){       //writing values

    if (is_integer($value)) {
      $xml->writeAttribute("value",$value);
    }
    elseif (is_bool($value)) {
      if($value)  $xml->writeAttribute("value","true");
      else        $xml->writeAttribute("value","false");
    }
    elseif (empty($value)) {
      if(!is_array($value))
        $xml->writeAttribute("value","NULL");
    }
    else
      $xml->text($value);
  }

  /**
  * Will write error message and exit script with proper exit code
  */
  function err($message,$code){
    echo "$message\n";
    exit($code);
  }

  /**
  * HELP
  */
  function help(){
    echo  "PHP Script for converting json to xml\n",
          "created by Martin Krajnak, xkrajn02@fit.vutbr.cz\n";
    exit(0);
  }
?>
