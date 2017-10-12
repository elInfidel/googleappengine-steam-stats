<?php

use Google\Cloud\Storage\StorageClient;

$json_request = $_REQUEST["type"];

if (isset($json_request) && $json_request !== "") 
{   
    echo file_get_contents("gs://".getenv('BUCKET_NAME')."/apps_summary.json");
}

?>