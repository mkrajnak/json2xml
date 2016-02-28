#!/usr/bin/php
<?php

  $TESTINFILENAME = "/home/matt/Projects/ipp/json2xml/example.json";
  $TESTOUTFILENAME = "/home/matt/Projects/ipp/json2xml/example.xml";

  define('XMLHEADER', '<?xml version="1.0" encoding="UTF-8" ?>\n'); //standard xml header
  define('INPUTRGX', '/^(--input=.+)$/');
  define('OUTPUTRGX', '/^(--output=.+)$/');
  define('MATCHINFILENAME', '/^--input=(.+)$/');
  define('MATCHOUTFILENAME', '/^--output=(.+)$/');


  arg_check($argv,$argc);
  // $json = json_read();
  // print_r($json);


  /**
  * Will parse and return filename from given string
  */
  function get_filename($filter,$string){
    $file_name = preg_match($filter,$string,$matches);
    return $matches[1];
  }

  /**
  * Will open a file, and writes converted json to file
  */
  function write_json_to_xml(){
  $out_file = fopen(TESTOUTFILENAME, "w");
  fwrite($out_file,TESTOUTFILENAME);
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

      if (preg_match(INPUTRGX, $value) === 1 ) {
        $TESTINFILENAME = get_filename(MATCHINFILENAME,$value);
        //echo "$TESTINFILENAME\n";
        continue;
      }
      if (preg_match(OUTPUTRGX, $value) === 1 ) {
        $TESTOUTFILENAME = get_filename(MATCHOUTFILENAME,$value);
        //echo "$TESTOUTFILENAME\n";
        continue;
      }
      $parse_error = true;
    }
    if ($parse_error) {
      err("Invalid parameters, try --help for more",1); // TODO: Check value again. should be 1
    }
  }


  function json_read(){
    $raw_json = file_get_contents(TESTINFILENAME);
    $json = json_decode($raw_json,true);
    return $json;
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
