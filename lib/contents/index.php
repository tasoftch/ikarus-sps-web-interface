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

/**
 * @var $CONFIG
 */

?>
<script type="application/javascript" src="/public/js/api.js"></script>

<script type="application/javascript">
    $(function() {
        Skyline.API.get("/api/status")
            .success(function(data) {
                update_state(data);
            });
    });

    function update_state(state) {
        $(".btn.control").addClass('disabled');

        switch (state) {
            case 'ready':
                $("#status-text").setClass("text-primary").text("Ready");
                $("#btn-run").removeClass("disabled");
                $("#btn-stop").removeClass("disabled");
                $("#btn-edit").removeClass("disabled");
                break;
            case 'idle':
                $("#status-text").setClass("text-primary").text("Ready (Idle)");
                $("#btn-run").removeClass("disabled");
                $("#btn-stop").removeClass("disabled");
                $("#btn-edit").removeClass("disabled");
                break;
            case 'busy':
            case 'running':
                $("#status-text").setClass("text-success").text("Running");
                $("#btn-idle").removeClass("disabled");
                break;
            case 'stopped':
                $("#status-text").setClass("text-danger").text("Stopped");
                break;
        }
    }

    function run_sps() {
        Skyline.API.get("/api/run")
            .success(function(data) {
                if(data == 'OK') {
                    update_state('running');
                } else
                    alert("Could not run SPS");
            });
    }

    function idle_sps() {
        Skyline.API.get("/api/idle")
            .success(function(data) {
                if(data == 'OK') {
                    update_state('idle');
                } else
                    alert("Could interrupt run SPS");
            });
    }

    function stop_sps() {
        $("#problem-modal").modal("show");
    }

    function stop_sps_real() {
        Skyline.API.get("/api/quit")
            .success(function(data) {
                if(data == 'OK') {
                    update_state('stopped');
                    setTimeout(function() {
                        window.location.reload();
                    }, 500);
                } else
                    alert("Could not stop SPS");
            });
    }

    $(function() {
        Skyline.API.get("/api/myStromDev")
            .success(function(data) {
                console.log(data);
            })
            .error(function(err) {
                console.error(err);
            });
    });
</script>
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
                        <span class="text-danger" id="status-text">
                            Unknown
                        </span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between">
                        SPS Process
                        <span>
                            <?php
                            echo isset($CONFIG["IKARUS_SPS_PROCID"]) ? ('#' . $CONFIG["IKARUS_SPS_PROCID"]) : "-.-";
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
                        <button onclick="run_sps()" class="btn btn-sm btn-success disabled control" id="btn-run">
                            Run
                        </button>
                        <span class="text-muted">Runs the engine</span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between">
                        <button onclick="idle_sps()" class="btn btn-sm btn-warning disabled control" id="btn-idle">
                            Pause
                        </button>
                        <span class="text-muted">Interrupts the engine</span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between">
                        <button onclick="stop_sps();" class="btn btn-sm btn-danger disabled control" id="btn-stop">
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
                    <a class="btn btn-primary disabled control text-white" id="btn-edit" href="/edit.php" target="_blank">
                        Open Editor
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="problem-modal" tabindex="-1" role="dialog" aria-labelledby="problem-modal-title" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title" id="problem-modal-title">Stop SPS</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true" class="white-text">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="text-center">
                    <p id="problem-modal-msg">
                        Stopping the SPS will shutdown all connected processes as well.<br>
                        That means also this web server.
                    </p>
                    <p>
                        After stopping the SPS, you are not able to interact with the SPS using this website anymore!<br>
                        Do you really want to stop the SPS now?
                    </p>
                </div>
            </div>
            <div class="modal-footer d-flex justify-content-between" onclick="$('#problem-modal').modal('hide');">
                <button class="btn btn-primary">
                    Cancel
                </button>
                <button class="btn btn-danger" onclick="stop_sps_real()">
                    Stop SPS
                </button>
            </div>
        </div>
    </div>
</div>
