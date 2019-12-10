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

/**
 * @title Welcome to Ikarus SPS Web Interface
 */

$SPS_PROCESS = getenv("IKARUS_SPS_PROCESS");

?>
<h1 class="my-5 text-center">Welcome to Ikarus SPS Web Interface</h1>
<p class="text-muted mb-3">
    You have here a quick overview what is going on at this SPS.
</p>
<div class="row">
    <div class="col-lg-6 mt-4">
        <div class="card">
            <h5 class="card-header">SPS Engine</h5>
            <div class="card-body">
                <ul class="list-group">
                    <li class="list-group-item d-flex justify-content-between">
                        Status
                        <span class="text-danger">
                            Unreachable
                        </span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between">
                        SPS Process
                        <span>
                            <?php
                            echo $SPS_PROCESS ? "#$SPS_PROCESS" : "-.-";
                            ?>
                        </span>
                    </li>
                </ul>
            </div>
        </div>
    </div>

    <div class="col-lg-6 mt-4">
        <div class="card">
            <h5 class="card-header">SPS Web Interface</h5>
            <div class="card-body">
                <ul class="list-group">
                    <li class="list-group-item d-flex justify-content-between">
                        Device Host
                        <span>
                            <?php
                            echo $_SERVER["HTTP_HOST"] ?? gethostbyname("localhost");
                            ?>
                        </span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between">
                        Web Process
                        <span>
                            <?php
                            echo "#", getmypid();
                            ?>
                        </span>
                    </li>
                </ul>
            </div>
        </div>
    </div>

    <div class="col-lg-6 mt-4">
        <div class="card">
            <h5 class="card-header">SPS Control</h5>
            <div class="card-body">
                <p class="text-muted">
                    Controlling the engine from here. If this does not work, you need to fix the configuration.<br>
                    Please note, stopping the engine also drops the connection to the web interface
                </p>
                <ul class="list-group">
                    <li class="list-group-item d-flex justify-content-between">
                        <button class="btn btn-sm btn-success disabled">
                            Run
                        </button>
                        <span class="text-muted">Runs the engine</span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between">
                        <button class="btn btn-sm btn-warning disabled">
                            Pause
                        </button>
                        <span class="text-muted">Interrupts the engine</span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between">
                        <button class="btn btn-sm btn-danger disabled">
                            Stop
                        </button>
                        <span class="text-muted">Stops the engine</span>
                    </li>
                </ul>
            </div>
        </div>
    </div>

    <div class="col-lg-6 mt-4">
        <div class="card">
            <h5 class="card-header">SPS Logic</h5>
            <div class="card-body">
                <p class="text-muted">
                    You can only control the SPS via the Web Interface if the Webserver could connect to the SPS and the SPS does not run.
                </p>
                <hr>
                <div class="text-center">
                    <button class="btn btn-primary disabled">
                        Open Editor
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
