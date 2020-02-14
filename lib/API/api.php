<?php

use Ikarus\SPS\Client\TcpClient;
use Ikarus\SPS\Client\UnixClient;
use Ikarus\SPS\Exception\SPSException;
use Ikarus\WEB\Exception\SocketException;


$host = $CONFIG["IKARUS_SPS_WEBC_ADDR"] ?? NULL;
$port = $CONFIG["IKARUS_SPS_WEBC_PORT"] ?? 0;
$unix = $CONFIG["IKARUS_SPS_WEBC_UNIX"] ?? NULL;

if($host && $port)
    $communication = new TcpClient($host, $port);
elseif ($unix)
    $communication = new UnixClient($unix);
else
    throw new SPSException("Can not establish communication to sps");


if($_SERVER["REQUEST_URI"] == '/api/status') {
    try {
        header("Content-Type: application/json");

        $response = @$communication->sendCommand("status");
        echo json_encode([
            'success' => true,
            'errors' => [],
            'response' => $response
        ]);
    } catch (SocketException $exception) {
        if($exception->getCode() == 11)
            echo "busy";
    } catch (SPSException $exception) {
        echo json_encode([
            'success' => false,
            'errors' => [
                [
                    'level' => 'exception',
                    'code' => $exception->getCode(),
                    'message' => $exception->getMessage()
                ]
            ]
        ]);
    }
    exit();
}

if($_SERVER["REQUEST_URI"] == '/api/scene-save') {
    $scene = $_POST["scene"];
    header("Content-Type: application/json");

    $logo = require "logical.ikarus.sps.php";
    $data = [
        'errors' => [
            [
                "code" => 0,
                'message' => 'Scene content could not be assigned'
            ]
        ],
        'success' => 0,
    ];

    if(isset($logo[$scene])) {
        $content = json_decode( $_POST["content"], true);
        $logo[$scene]["content"] = $content;
        $logo[$scene]["transform"] = json_decode( $_POST["transform"], true);

        $d = var_export($logo, true);
        file_put_contents("logical.ikarus.sps.php", "<?php\nreturn $d;");
        unset($data["errors"][0]);
        $data["success"] = 1;
    }
    echo json_encode($data);
    exit();
}

if($_SERVER["REQUEST_URI"] == '/api/scene-add') {
    header("Content-Type: application/json");
    $name = $_POST["the-name"];

    $data = [
        'errors' => [],
        'success' => 1,
        "name" => $name
    ];

    if(!$name) {
        $data["success"] = 0;
        $data["errors"][] = [
            "code" => 401,
            'message' => 'Scene name must not be empty!'
        ];
    } else {
        $logo = require "logical.ikarus.sps.php";
        $slug = strtolower( preg_replace("/[^a-z0-9_\-]/i", '-', $name) );

        if(isset($logo[$slug])) {
            $data["success"] = 0;
            $data["errors"][] = [
                "code" => 401,
                'message' => "Scene name $name is already occupied."
            ];
        } else {
            $logo[$slug] = [
                "name" => $name
            ];
        }

        $ld = var_export($logo, true);
        file_put_contents("logical.ikarus.sps.php", "<?php\nreturn $ld;");

        $data["slug"] = $slug;
        $data["name"] = $name;
    }

    echo json_encode($data);
    exit();
}

if($_SERVER["REQUEST_URI"] == '/api/run') {
    try {
        echo $communication->sendCommand("run");
    } catch (Throwable $exception) {
    }
    exit();
}

if($_SERVER["REQUEST_URI"] == '/api/idle') {
    try {
        echo $communication->sendCommand("idle");
    } catch (Throwable $exception) {
    }
    exit();
}



if($_SERVER["REQUEST_URI"] == '/api/quit') {
    try {
        echo $communication->sendCommand("stop");
    } catch (Throwable $exception) {
    }
    exit();
}

if($_SERVER["REQUEST_URI"] == '/api/components.js') {
    require __DIR__ . "/components.js.php";
    exit();
}

if($_SERVER["REQUEST_URI"] == '/api/scenes.js') {
    require __DIR__ . "/scenes.js.php";
    exit();
}