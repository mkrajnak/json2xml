#!/usr/bin/php
<?php
#JSN:xkrajn02
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
    var $substitute_element = false;
    var $substitute_value = false;

    var $index = 1;                 //default values
    var $wrap_root_text = 'root';
    var $array_text = 'array';
    var $item_text = 'item';
    var $substitute_string = '-';

    /*FILE VARIABLEs */
    var $in_filename = 'php://stdin';
    var $out_filename = 'php://stdout';
}

  /* REGEXES FOR PARSING AND MATCHING PARAMS */
  define('INPUTRGX', '/^(--input=.+)$/');
  define('OUTPUTRGX', '/^(--output=.+)$/');
  define('ROOTRGX', '/^(-r=.+)$/');
  define('REPLACEELEMENTRGX', '/^(-h=.+)$/');
  define('ARRAYRGX', '/^(--array-name=.+)$/');
  define('ITEMRGX', '/^(--item-name=.+)$/');
  define('STARTINDEXRGX', '/^(--start=.+)$/');
  define('INVALIDCHARSRGX', '/<|>|"|\'|\/|\\|&|&/');  //ANY CHARACTER
  define('STARTCHARRGX', '/^[\P{L}]/');               //FIRST CHARACTER

  define('MATCHINFILENAME', '/^--input=(.+)$/');    //regexes parsing values entered after param
  define('MATCHOUTFILENAME', '/^--output=(.+)$/');
  define('MATCHROOTRGX', '/^-r=(.+)$/');
  define('MATCHARRAYRGX', '/^--array-name=(.+)$/');
  define('MATCHITEMRGX', '/^--item-name=(.+)$/');
  define('MATCHSTARTINDEXRGX', '/^--start=(.+)$/');
  define('MATCHELEMENTREPLACEMENTTGX', '/^-h=(.+)$/');

  main($argv,$argc);   //main function in which are handled all script functions

  /**
  * MAIN FUNCTION OF SCRIPT
  */
  function main($argv,$argc){

    $opt = new Options();     //initialize options

    arg_check($argv,$argc,$opt);          //check args
    $json_data = json_read($opt);         // read jsn
    write_json_to_xml($json_data,$opt);   // write xml

    exit(0);                  //sucess
  }

  /**
  * Will parse and return filename or parameter from given string
  */
  function get_filename($filter,$string){
    preg_match($filter,$string,$matches);
    return $matches[1];
  }

  /**
  * Check args passed to script, also calling functions handling all the
  * functonality of script
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
      if (preg_match(ROOTRGX, $value) === 1 ) {       //-r=
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
      if ($value === "-t" || $value === "--index-items" ) {    //-t
        $opt->index_items = true;
        continue;
      }
      if ($value === "-a" || $value === "--array-size" ) {    //-a
        $opt->array_size = true;
        continue;
      }
      if (preg_match(REPLACEELEMENTRGX, $value) === 1 ) {     //--h
        $opt->substitute_element = true;
        $opt->substitute_string = get_filename(MATCHELEMENTREPLACEMENTTGX,$value);
        continue;
      }
      if ($value === "-c" ) {                                   //-c
        $opt->substitute_value = true;
        continue;
      }
      if (preg_match(STARTINDEXRGX, $value) === 1 ) {         //--start
        $opt->change_start_index = true;
        $opt->index = get_filename(MATCHSTARTINDEXRGX,$value);
        continue;
      }
      $parse_error = true;          //ending with error
      break;
    }
    check_start_index($opt);        //check value entered after --start=

    if (check_element_validity($opt->wrap_root_text,$opt) ||  //check validity of
        check_element_validity($opt->array_text,$opt) ||      //entered params
        check_element_validity($opt->item_text,$opt) ) {
          err("Invalid element",50);
    }

    if ($parse_error ) {
      err("Invalid parameters, try --help for more",1);       //ERR
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
    if (($json_data = json_decode($raw_json,false)) === NULL) {
      err("While reading input data",4);
    }
    if (is_object($json_data) && empty($json_data)) {
      err("Could not read file",2);
    }
    return $json_data;
  }

  /**
  * Will open a file, and writes converted json to file
  */
  function write_json_to_xml($json_data,$opt){

    $xml = new XMLWriter();         //new xml handler
    $xml->openMemory();
    $xml->setIndent(true);          //toggle whitespaces adn tabs

    if ($opt->generate_header) {    //header is generated by default
      $xml->startDocument('1.0', 'UTF-8');  //-n
    }

    if ($opt->wrap_root) {          //add root element
      $xml->startElement($opt->wrap_root_text);
    }

    write_xml($json_data,$xml,$opt); //handle json

    if ($opt->wrap_root) {          //end root element
      $xml->endElement();
    }
    $xml->endDocument();

    write_file($opt,$xml);
  }

  /**
  * Recursively writes arrays, object, in the end data
  */
  function write_xml($json_data,$xml,$opt){

    if (is_array($json_data)) {             // received data as array handle as array
      write_array($json_data,$xml,$opt);    // like this [1,2,3]
      return;
    }
    foreach ($json_data as $key => $value) {//  iterate through data

      if ($opt->substitute_element) {       //substitute element -c
        $key = replace_invalid_keys($key,$opt);
      }
      if (check_element_validity($key,$opt)) {
        err("Invalid element",51);
      }
      $xml->startElement($key);           //<key>

      if (is_object($value)){

        write_xml($value,$xml,$opt);
      }
      else if (is_array($value)) {
        write_array($value,$xml,$opt);
      }
      else{
        write_value($value,$xml,$opt);
      }
      $xml->endElement();                  //<key>
  }
}

  /**
  * Iterating through array
  */
  function write_array($field,$xml,$opt){

    $xml->startElement($opt->array_text);   //<array>
    if ($opt->array_size) {                 //displaying array size
      $xml->writeAttribute("size",count($field));
    }
    for ($i = 0; $i < count($field); $i++) {

      $xml->startElement($opt->item_text);  //<item>
      if ($opt->index_items) {              // add index to item tag
        $xml->writeAttribute("index",$i+$opt->index);
      }

      if(is_object($field[$i])){  //write item which contains object(another items)
          write_object($field[$i],$xml,$opt);
      }
      else if (is_array($field[$i])) {    //array inside array handle recursively
        write_array($field[$i],$xml,$opt);
      }
      else {                              //write [][][][] field
        write_value($field[$i],$xml,$opt);
      }
      $xml->endElement();                 //</item>
    }
    $xml->endElement();                   //</array>
  }

  /**
  * Writing values inside object
  */
  function write_object($obj,$xml,$opt){

    foreach ($obj as $key => $value) {

      if (is_array($value)) {           //array inside array handle recursively
        write_array($value,$xml,$opt);
      }
      else{
        $xml->startElement("$key");     //<$key>
        write_value($value,$xml,$opt);
        $xml->endElement();             //</key>
      }
    }
  }

  /**
  * Correctly writes data values to xml
  */
  function write_value($value,$xml,$opt){     //writing values

    if ($opt->substitute_value) {             //substituting invalid chars
      $value = replace_invalid_values($value,$opt);
    }
    if (is_numeric($value)) {                 //value is number

      if (is_string($value)) {                // value is STRING !!!

        if ( $opt->string_is_attribute ) {    //-c
          $xml->startAttribute("value");
          $xml->writeRaw($value);
          $xml->endAttribute();
        }
        else $xml->writeRaw($value);          //$amp and so on, also value is still string
      }
      elseif (is_integer($value) || is_numeric($value)) {       //values are integers

        $value = floor($value);
        if ($opt->int_is_attribute) {                           //-i
          $xml->writeAttribute("value",$value);
        }
        else $xml->text("$value");           //value written inside tags
      }
    }

    elseif (is_bool($value)) {              // values are boolean

      if ($opt->values_to_elements) {       // --l

        if($value)  $xml->startElement("true");
        else        $xml->startElement("false");
        $xml->endElement();
      }
      else {                                //booleans written as attributes
        if($value)  $xml->writeAttribute("value","true");
        else        $xml->writeAttribute("value","false");
      }
    }
    elseif (is_string($value)) {            // string

        if ( $opt->string_is_attribute ) {  //-c
          $xml->startAttribute("value");
          $xml->writeRaw($value);
          $xml->endAttribute();
        }
        else $xml->writeRaw($value);
    }
    elseif (empty($value)) {                // empty value

      if(!is_array($value)){
        if ($opt->values_to_elements) {     //-l
          $xml->startElement("null");
          $xml->endElement();
        }
        $xml->writeAttribute("value","null");
      }
    }
  }

  /**
  * Handling -h, replaces invalid chars in keys
  */
  function replace_invalid_keys($key,$opt){

    if(is_string($key)){
      $key = preg_replace(INVALIDCHARSRGX, $opt->substitute_string, $key);
      $key = preg_replace(STARTCHARRGX, $opt->substitute_string, $key);
    }
    return $key;
  }

  /**
  * CHECK VALIDITY OF ELEMENT
  * XML tag can only start with valid unicode char or "_"
  * @return tue when xml tag is invalid
  */
  function check_element_validity($key,$opt){

    if (preg_match(STARTCHARRGX, $key) === 1 ) {      //check first character
      preg_match(STARTCHARRGX,$key,$matches);
      if ($matches[0] ==  "_") {
        return false;
      }
      return true;
    }
    if (  preg_match(INVALIDCHARSRGX, $key) === 1) {  //check other chars validity
      return true;
    }
    return false;
  }

  /**
  * Handling -c, replaces invalid chars in values via regex
  */
  function replace_invalid_values($value,$opt){

    if(is_string($value)){
      $value = preg_replace("/&/", "&amp;", $value);
      $value = preg_replace("/</", "&lt;", $value);
      $value = preg_replace("/>/", "&gt;", $value);
      $value = preg_replace("/\"/", "&quot;", $value);
      $value = preg_replace("/\'/", "&quot;", $value);
      }
    return $value;
  }

  /**
  * writes results to file
  */
  function write_file($opt,$xml)
  {
    if ($opt->write_to_file) {                        //write to file
      if (($out_file = fopen($opt->out_filename, "w")) === false) {
        err("Could not open file $opt->out_filename",3);
      }
      if ((fwrite($out_file,$xml->outputMemory(TRUE))) === false){
        err("Could not write to file",3);
      }
      if (!fclose($out_file)) {
        err("Could not close the file",3);
      }
    }
    else fwrite(STDOUT,$xml->outputMemory(TRUE));   //write to stdout

  }

  /**
  * CHECK IF --start AND --index-items was entered
  */
  function check_start_index($opt){
    if ($opt->change_start_index) {
      if ($opt->index_items === false) {
        err("Invalid args, use both --start and --index-items",1);
      }
      if ($opt->index < 0) {
        err("Start index is not valid",1);
      }
      if (is_numeric($opt->index) === false) {
        err("Start index($opt->index) has to be integer",1);
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
          "usage: php jsn.php  [OPTIONS] \n",
          "\t-r=root-element\t changes name of root element \n",
          "\t-r=array-name\t changes name of array tag default=\"array\"\n",
          "\t-r=array-name\t changes name of item tag default =\"item\"\n",
          "\t-a, --array-size \t writes size of field as attribute of field tag\n",
          "\t-t, --index-items \t each item of field is intexed\n",
          "\t-start=n \t n is start number of indexing items\n",
          "\t-s \t writes string as attributes instead if between tags\n",
          "\t-i \t writes number as attributes instead if between tags\n",
          "\t-l \t transforms boolean values to tags\n",
          "\t-x \t tranformt & to &amp; etc.\n\n",
          "created by Martin Krajnak, xkrajn02@fit.vutbr.cz\n";
    exit(0);
  }
?>
