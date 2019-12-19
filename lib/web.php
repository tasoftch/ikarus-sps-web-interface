<?php
/**
 * BSD 3-Clause License
 *
 * Copyright (c) 2019, TASoft Applications
 * All rights reserved.
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions are met:
 *
 *  Redistributions of source code must retain the above copyright notice, this
 *   list of conditions and the following disclaimer.
 *
 *  Redistributions in binary form must reproduce the above copyright notice,
 *   this list of conditions and the following disclaimer in the documentation
 *   and/or other materials provided with the distribution.
 *
 *  Neither the name of the copyright holder nor the names of its
 *   contributors may be used to endorse or promote products derived from
 *   this software without specific prior written permission.
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS"
 * AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE
 * IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE
 * DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT HOLDER OR CONTRIBUTORS BE LIABLE
 * FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL
 * DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR
 * SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER
 * CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY,
 * OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE
 * OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 */

// This is the routing file of a web interface process.
// It will route all /public/* URIs to the public folder and deliver the file if available.
// It routes all /api/* URIs to the API/api.php script

ini_set("error_reporting", E_ALL);

require "vendor/autoload.php";

$URI = $_SERVER["REQUEST_URI"];
define("IKARUS_VERSION", '1.0.3');


if(preg_match("%/?public/(.+?)$%i", $URI, $ms)) {
    $ext = explode(".", $URI);
    switch(strtolower(array_pop($ext))) {
        case 'js':
            header("Content-Type: application/javascript");
            break;
        case 'json':
            header("Content-Type: application/json");
            break;
        case 'css':
            header("Content-Type: text/css");
            break;
    }

    if(file_exists(__DIR__ . "$URI")) {
        header("Cache-Control: max-age=86400");
        readfile(__DIR__ . "$URI");
    } else
        http_response_code(404);

    exit();
}

if(preg_match("%/?api/%", $URI)) {
    require __DIR__ . "/API/api.php";
    exit();
}

if(getenv("IKARUS_WEB_COMPONENTS_EMBED")) {
    $layoutFile = __DIR__ . "/contents/layout-embed.php";
} else {
    $layoutFile = __DIR__ . "/contents/layout.php";
}

if($URI == '/')
    $URI = 'index.php';

if(stripos($URI, '/edit.php') === 0) {
    require __DIR__ . "/contents/edit.php";
    return;
}

$FILE = __DIR__ . "/contents/$URI";
if(file_exists($FILE)) {
    $TITLE = "Ahh";

    $tokens = token_get_all( file_get_contents($FILE) );
    foreach($tokens as $token) {
        if(is_array($token) && $token[0] == T_DOC_COMMENT) {
            $comment = $token[1];

            if(preg_match("%^\s*\*\s*@title\s+(.+?)\s*$%im", $comment, $ms)) {
                $TITLE = $ms[1];
            }
        }
    }

    $CONFIG = NULL;
    if(file_exists( getcwd() . "/config.json" )) {
        $CONFIG = json_decode( file_get_contents(  getcwd() . "/config.json"  ), true );
    }
    try {
        require $layoutFile;
    } catch (Throwable $exception) {
        var_dump($exception);
    }

} else {
    http_response_code(404);
    echo "<h1>404 Not Found</h1>";
}