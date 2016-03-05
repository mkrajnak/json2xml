#!/usr/bin/php
<?php

  class Options {
    /* ERROR CHECKING FLAGS*/
    var $generate_header = true;
    var $wrap_root= false;
    var $string_is_attribute = true;
    var $int_is_attribute = true;
    var $index_items = false;
    var $array_size = false;
    var $values_to_elements = false;
    var $change_start_index = false;
    var $write_to_file = false;

    var $index = 0;
    var $wrap_root_text = 'root';
    var $array_text = 'array';
    var $item_text = 'item';

    /*FILE VARIABLEs */
    var $in_filename = 'php://stdin';
    var $out_filename = 'php://stdout';
}

  /* REGEXES FOR PARSING AND MATCHING PARAMS */
  define('INPUTRGX', '/^(--input=.+)$/');
  define('OUTPUTRGX', '/^(--output=.+)$/');
  define('ROOTRGX', '/^(-r=.+)$/');
  define('ARRAYRGX', '/^(--array-name=.+)$/');
  define('ITEMRGX', '/^(--item-name=.+)$/');
  define('STARTINDEXRGX', '/^(--start=.+)$/');

  define('MATCHINFILENAME', '/^--input=(.+)$/');
  define('MATCHOUTFILENAME', '/^--output=(.+)$/');
  define('MATCHROOTRGX', '/^-r=(.+)$/');
  define('MATCHARRAYRGX', '/^--array-name=(.+)$/');
  define('MATCHITEMRGX', '/^--item-name=(.+)$/');
  define('MATCHSTARTINDEXRGX', '/^--start=(.+)$/');

  main($argv,$argc); //do the harlem shake

  /**
  * MAIN FUNCTION OF SCRIPT
  */

  function main($argv,$argc){
    $opt = new Options();

    arg_check($argv,$argc,$opt);
    $json_data = json_read($opt);
    print_r($json_data);                  //DELETE !!!!!!
    write_json_to_xml($json_data,$opt);

    exit(0);
  }

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
  function arg_check($argv,$argc,$opt){
    $parse_error = false;
    foreach ($argv  as $param_count => $value) {

      if ($param_count == 0) continue; //TODO: handle stdin and

      if ($value == "--help" && $param_count === 1) {   //help was called, exiting with 0
        help();
      }
      if ($value == "-n" ){
          $opt->generate_header = false;
          continue;
      }
      if (preg_match(INPUTRGX, $value) === 1 ) {        //--input=
        $opt->in_filename = get_filename(MATCHINFILENAME,$value);
        continue;
      }
      if (preg_match(OUTPUTRGX, $value) === 1 ) {       //--output=
        $opt->out_filename = get_filename(MATCHOUTFILENAME,$value);
        $opt->write_to_file = true;
        continue;
      }
      if (preg_match(ROOTRGX, $value) === 1 ) {
        $opt->wrap_root = true;
        $opt->wrap_root_text = get_filename(MATCHROOTRGX,$value); // actually returns name of root tag not filename
        continue;
      }
      if (preg_match(ARRAYRGX, $value) === 1 ) {  //--array-name
        $opt->array_text = get_filename(MATCHARRAYRGX,$value);
        continue;
      }
      if (preg_match(ITEMRGX, $value) === 1 ) {   //--item-name
        $opt->item_text = get_filename(MATCHITEMRGX,$value);
        continue;
      }
      if ($value === "-s") { //-s
        $opt->string_is_attribute = false;
        continue;
      }
      if ($value === "-i") {    //-i
        $opt->int_is_attribute = false;
        continue;
      }
      if ($value === "-l") {    //-l
        $opt->values_to_elements = true;
        continue;
      }
      if ($value === "-t" || $value === "--index-items" ) {    //-i
        $opt->index_items = true;
        continue;
      }
      if ($value === "-a" || $value === "--array-size" ) {    //-i
        $opt->array_size = true;
        continue;
      }
      if (preg_match(STARTINDEXRGX, $value) === 1 ) {   //--start
        $opt->change_start_index = true;
        $opt->index = get_filename(MATCHSTARTINDEXRGX,$value);
        continue;
      }
      $parse_error = true;
      break;
    }
    if ($parse_error) {
      err("Invalid parameters, try --help for more",1); // TODO: Check value again. should be 1
    }
  }

  /**
  * Will read content of json file
  * return json content in array
  */
  function json_read($opt){

    if(($raw_json = file_get_contents($opt->in_filename)) === false){
      err("Could not read file",2);
    }
    $json_data = json_decode($raw_json,false);
    return $json_data;
  }

  /**
  * Will open a file, and writes converted json to file
  */
  function write_json_to_xml($json_data,$opt){

    $xml = new XMLWriter();
    $xml->openMemory();
    $xml->setIndent(true);
    if ($opt->generate_header) {
      $xml->startDocument('1.0', 'UTF-8');
    }

    if ($opt->wrap_root) {
      $xml->startElement($opt->wrap_root_text);
    }

    writeXML($json_data,$xml,$opt);

    if ($opt->wrap_root) {
      $xml->endElement();
    }
    $xml->endDocument();

    if ($opt->write_to_file) {
      if ($out_file = fopen($opt->out_filename, "w") === false) {
        err("Could not open file",3);
      }
        if (fwrite($out_file,$xml->outputMemory(TRUE)) === false){
          err("Could not write to file",3);
        }
        fclose($out_file);
      }
      else fwrite(STDOUT,$xml->outputMemory(TRUE));
    }


  /**
  * Recursively writes arrays, object, in the end data
  */
  function writeXML($json_data,$xml,$opt){

    foreach ($json_data as $key => $value) {
      if (is_object($value)) {
        $xml->startElement($key);     //<key>
        writeXML($value,$xml,$opt);
        $xml->endElement();             //</key>
      }
      else if (is_array($value)) {
        $xml->startElement($key);     //<item>
        $xml->startElement($opt->array_text);    //<key>
        if ($opt->array_size) {
          $xml->writeAttribute("size",count($value));
        }
        writeArray($value,$xml,$opt);
        $xml->endElement();             //</array>
        $xml->endElement();             //<key>
        }
      else{
        $xml->startElement($key);       //wrap with <key>
        write_value($value,$xml,$opt);
        $xml->endElement();             //<key>
    }
  }
}

  /**
  * Iterating through array
  */
  function writeArray($field,$xml,$opt){
    for ($i = 0; $i < count($field); $i++) {

      check_start_index($opt);
      $xml->startElement($opt->item_text);     //<item>
      if ($opt->index_items) {
        $xml->writeAttribute("index",$i+$opt->index);
      }
      foreach ($field[$i] as $key => $value) {

        $xml->startElement("$key");       //<$key>
        write_value($value,$xml,$opt);
        $xml->endElement();             //</key>
      }
      $xml->endElement();             //</item>
    }
  }

  /**
  * Correctly writes data values to xml
  */
  function write_value($value,$xml,$opt){       //writing values

    if (is_integer($value) ) {
      if ($opt->int_is_attribute) {
        $xml->writeAttribute("value",$value);
      }
      else $xml->text("$value");
    }
    elseif (is_bool($value)) {
      if ($opt->values_to_elements) {
        if($value)  $xml->startElement("true");
        else        $xml->startElement("false");
        $xml->endElement();
      }
      else {
        if($value)  $xml->writeAttribute("value","true");
        else        $xml->writeAttribute("value","false");
      }
    }
    elseif (is_string($value)) {
        if ( $opt->string_is_attribute ) {
          $xml->writeAttribute("value",$value);
        }
        else $xml->text($value);
    }
    elseif (empty($value)) {
      if(!is_array($value)){
        if ($opt->values_to_elements) {
          $xml->startElement("NULL");
          $xml->endElement();
        }
        $xml->writeAttribute("value","NULL");
      }
    }
    else $xml->text($value);
  }

  /**
  * CHECK IF --start AND --index-items was entered
  */
  function check_start_index($opt){
    if ($opt->change_start_index) {
      if ($opt->index_items === false) {
        err("Invalid args, use both --start and --index-items",1);
      }
    }
  }

  /**
  * Will write error message and exit script with proper exit code
  */
  function err($message,$code){
    fwrite(STDERR, "ERR:"."$message\n");
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
