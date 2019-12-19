<?php

$referer = $_SERVER["HTTP_REFERER"];
$url = parse_url($referer);

if($q = $url["query"] ?? NULL) {
    parse_str($q, $query);

    if($sceneID = $query["scene"] ?? NULL) {
        if(file_exists("logical.ikarus.sps.php")) {
            $logo = require "logical.ikarus.sps.php";
            if($scene = $logo[$sceneID] ?? NULL) {

                $content = $scene["content"] ?? [
                    'id' => 'ikarus@0.1.0',
                        'nodes' => new stdClass()
                    ];
                printf("editor.fromJSON(%s);\n", json_encode($content, JSON_UNESCAPED_SLASHES));

                if($transform = $scene["transform"] ?? NULL) {
                    printf("$(function() {editor.view.area.transform=%s;editor.view.area.update();});", json_encode($transform, JSON_UNESCAPED_SLASHES));
                }

            } else {
                $name = htmlspecialchars($sceneID);
                echo "console.error('Scene $name not found.');";
                echo "alert('Scene $name not found.');";
            }

        } else
            echo "console.error('Ikarus SPS configuration file not found.');";
        return;
    }
}

echo "console.error('No scene found.');";