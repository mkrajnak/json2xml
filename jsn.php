#!/usr/bin/php
<?php

  define('XMLHEADER', '<?xml version="1.0" encoding="UTF-8" ?>\n'); //standard xml header
  define('INPUTRGX', '/^(--input=.+)$/');
  define('OUTPUTRGX', '/^(--output=.+)$/');

  //
  //json_read();
  /**
  * Check args passed to script, also calling functions handling all the functonality of script
  */
  function arg_check()
  {
    foreach ($argv  as $param_count => $value) {

      if ($param_count == 0) continue;

      if ($value == "--help" && $param_count === 1) {
        help();
      }

      if (preg_match(INPUTRGX, $value) === 1 ) {
        echo "input defined properly\n";
      }

      if (preg_match(OUTPUTRGX, $value) === 1 ) {
        echo "output defined properly\n";
      }
      err("Invalid parameters, try --help for more",1); // TODO: handle proper value
    }
  }


  function json_read(){
    $raw_json = file_get_contents('/home/matt/Projects/ipp/json2xml/example.json');
    $json = json_decode($raw_json,true);
    print_r($json);
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
    echo "script for converting json to xml\n";
    exit(0);
  }
?>
