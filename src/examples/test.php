<?php
include_once __DIR__.DIRECTORY_SEPARATOR."ncplus/EpgException.php";
include_once __DIR__.DIRECTORY_SEPARATOR."ncplus/EpgParser.php";

use ncplus\EpgParser;

$parser = new EpgParser(['curlTor' => true]);

$data = $parser->loadDay('2016-05-05');
if ($data) {
    $parsed = $parser->parseCommonData($data);
    foreach($parsed['programs'] as $row){
        var_dump($row);
        break;
    }

} else {

}
var_dump($parser->getErrors());
