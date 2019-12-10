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

$URI = $_SERVER["REQUEST_URI"];

define("IKARUS_VERSION", '1.0.3');

if(preg_match("%/?public/(.+?)$%i", $URI, $ms)) {
    if(file_exists(__DIR__ . "$URI"))
        return false;
}

if(getenv("IKARUS_WEB_COMPONENTS_EMBED")) {
    $layoutFile = __DIR__ . "/contents/layout-embed.php";
} else {
    $layoutFile = __DIR__ . "/contents/layout.php";
}

if($URI == '/')
    $URI = 'index.php';

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

    require $layoutFile;
} else {
    http_response_code(404);
    echo "<h1>404 Not Found</h1>";
}