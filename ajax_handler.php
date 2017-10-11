<?php

$json_request = $_REQUEST["type"];

if (isset($json_request) && $json_request !== "") 
{
    /*switch ($i) 
    {
        case "":
            echo "test";
            break;
    }*/
    
    // Sending a test response
    echo '{
        "names": [
                "Tim",
                "Bob",
                "Alice",
                "John",
                "Samantha",
                "Veronica"
              ],
              "datasets": [
                {
                  "label": "# of pets",
                  "data": 
                  [
                      12,
                      19,
                      3,
                      5,
                      2,
                      30
                  ],
                  "border-width": 1
                }]
      }';
}

?>