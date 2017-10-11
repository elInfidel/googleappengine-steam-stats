<?php

$json_request = $_REQUEST["type"];

if (isset($json_request) && $json_request !== "") 
{
    switch ($i) 
    {
        case "":
            echo "i is apple";
            break;
    }
    
    echo "The server is listening! We received: ".$json_request;
}

?>