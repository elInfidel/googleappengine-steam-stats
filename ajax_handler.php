<?php

use Google\Cloud\Storage\StorageClient;

$json_request = $_REQUEST["type"];

if (isset($json_request) && $json_request !== "") 
{   
    // Sending a test response
    $obj = new stdClass;

    $dataset = new stdClass;
    $dataset->label = "# of pets";
    $dataset->data = array(12, 19, 3, 5, 2, 30);

    $labels = array("Tim","Bob","Alice","John","Samantha","Veronica");
    $datasets = array($dataset);

    $obj->data = array($labels, $dataset);

    echo json_encode($obj);
}

?>