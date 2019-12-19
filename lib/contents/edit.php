<?php

if(!isset($_GET["scene"])) {
    header("Location: /edit.php?scene=init");
    exit();
}


if(!file_exists("logical.ikarus.sps.php")) {
    file_put_contents("logical.ikarus.sps.php", '<?php
return [
    "init" => [
        "name" => "Init"
    ]
];');
}

$logo = require "logical.ikarus.sps.php";



?><!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>
        Ikarus SPS :: Node Editor
    </title>
    <script>
        window.console = window.console || function(t) {};
    </script>
    <script>
        if (document.location.search.match(/type=embed/gi)) {
            window.parent.postMessage("resize", "*");
        }
    </script>
    <link rel='stylesheet' href='/public/css/stage0-menu-plugin.min.css'>
    <link rel='stylesheet' href='/public/css/stage0-render-plugin.min.css'>
    <style>
        html,body{
            height: 100%;
            margin: 0;
        }
        #d3ne {
            height: 100%;
            position: relative;
        }

        .socket.Signal {
            background: rgb(16, 202, 16) !important;
            border-radius: 0 !important;
            width: 20px !important;
            height: 20px !important;
        }

        .socket.Number {
            background: #ff1800 !important;
        }

        .socket.String {
            background: #0300ff !important;
        }

        .socket.Boolean {
            background: #00ff31 !important;
        }

        .item {
            width: auto !important;
        }

        .output > div {
            display: inline-block;
        }

        #ikarus-editor .node .collapse {
            width: auto;
            padding: 6px;
        }
    </style>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.12.0-1/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0/css/bootstrap.min.css" integrity="sha384-Gn5384xqQ1aoWXA+058RXPxPg6fy4IWvTNh0E263XmFcJlSAwiGgFAW/dAiS6JXm" crossorigin="anonymous">
    <script src="https://code.jquery.com/jquery-3.4.1.min.js" integrity="sha256-CSXorXvZcTkaix6Yvo6HppcZGetbYMGWSFlBw8HfCJo=" crossorigin="anonymous"></script>
    <script type="application/javascript" src="/public/js/api.js"></script>
</head>
<body translate="no">
<nav class="navbar navbar-expand-lg navbar-light bg-light">
    <a class="navbar-brand" href="#">Ikarus SPS</a>
    <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarSupportedContent" aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="Toggle navigation">
        <span class="navbar-toggler-icon"></span>
    </button>

    <div class="collapse navbar-collapse" id="navbarSupportedContent">
        <ul class="navbar-nav mr-auto">
            <li class="nav-item dropdown">
                <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                    <i class="fa fa-layer-group"></i> Scenes
                </a>
                <div class="dropdown-menu" aria-labelledby="navbarDropdown">
                    <?php
                    foreach($logo as $sceneID => $sceneName) {
                        $active = "";
                        if($sceneID == $_GET["scene"])
                            $active = ' active';
                        ?>
                        <a class="dropdown-item<?=$active?> scene-item" href="#" onclick="change_scene('<?=$sceneID?>')"><i class="fa fa-<?=$sceneID=='init'?'bolt':'palette'?>"></i> <?=$sceneName["name"]?></a>
                        <?php
                    }
                    ?>
                    <div class="dropdown-divider"></div>
                    <a class="dropdown-item" href="#" onclick="$('#new-modal').modal('show');"><i class="fa fa-plus-circle"></i> New</a>
                </div>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="#" onclick="save_scene()"><i class="fa fa-save"></i> Save</a>
            </li>
            <?php
            if($_GET["scene"] != 'init') {
                ?>
                <li class="nav-item text-danger">
                    <a class="nav-link" href="#" onclick="$('#delete-scene').modal('show')"><i class="fa fa-trash"></i> Delete</a>
                </li>
                <?php
            }
            ?>
        </ul>
    </div>
</nav>
<div id="ikarus-editor" class="node-editor"></div>
<script src='https://unpkg.com/stage0@0.0.15/dist/keyed.min.js'></script>
<script src='https://unpkg.com/stage0@0.0.15/dist/index.min.js'></script>

<script src='/public/js/rete.min.js'></script>
<script src='/public/js/stage0-render-plugin.min.js'></script>

<script src='/public/js/stage0-menu-plugin.min.js'></script>

<script src='https://cdn.jsdelivr.net/npm/rete-connection-plugin@0.3.1/build/connection-plugin.min.js'></script>
<script src='https://cdnjs.cloudflare.com/ajax/libs/lodash.js/4.17.11/lodash.min.js'></script>
<script src='https://cdn.jsdelivr.net/npm/rete-comment-plugin@0.2.0/build/comment-plugin.min.js'></script>

<script src="https://code.jquery.com/pep/0.4.3/pep.js"></script>

<script src="/api/components.js"></script>

<script id="rendered-js">
    var container = document.querySelector('#ikarus-editor');

    var editor = new Rete.NodeEditor('ikarus@0.1.0', container);

    editor.use(ConnectionPlugin);
    editor.use(Stage0RenderPlugin);
    editor.use(Stage0MenuPlugin, {
        menuOptions: {
            delay: 100,
            docked: false,
            allocate() {
                return [];
            }
        }
    });

    editor.use(CommentPlugin);

    components.map(c => {
        editor.register(c);
    });

    editor.view.resize();
    editor.trigger("process");


    function create_scene(sender) {
        Skyline.API.post("/api/scene-add", new FormData(sender))
            .success(function(data) {
                alert(data);
            })
            .error(function(err) {
                alert(err.message ? err.message : err);
            });
        return false;
    }

    function save_scene() {
        var data = JSON.stringify( editor.toJSON() );

        var fd = new FormData();
        fd.append("scene", '<?= htmlspecialchars($_GET["scene"]) ?>');
        fd.append("content", data);
        fd.append("transform", JSON.stringify(editor.view.area.transform));

        Skyline.API.post("/api/scene-save", fd)
            .success(function(data) {
                alert("Scene was saved successfully.");
            })
            .error(function(err) {
                alert(err.message ? err.message : err);
            });
    }

    function change_scene(sceneID) {
        window.location.href = '/edit.php?scene='+sceneID;
    }

    editor.on("nodecreated", function() {
        editor.trigger("hidecontextmenu");
    })
</script>

<script src="/api/scenes.js"></script>

<script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.12.9/umd/popper.min.js" integrity="sha384-ApNbgh9B+Y1QKtv3Rn7W3mgPxhU9K/ScQsAP7hUibX39j7fakFPskvXusvfa0b4Q" crossorigin="anonymous"></script>
<script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0/js/bootstrap.min.js" integrity="sha384-JZR6Spejh4U02d8jOt6vLEHfe/JQGiRRSQQxSfFWpi1MquVdAyjUar5+76PVCmYl" crossorigin="anonymous"></script>

<div class="modal fade" id="new-modal" tabindex="-1" role="dialog" aria-labelledby="new-modal-title" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <form action="" onsubmit="return create_scene(this);" id="frm">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title" id="new-modal-title">New Scene</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true" class="white-text">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="text-center">
                        <p id="problem-modal-msg">
                            This action adds a new scene to your logic project of the SPS.
                        </p>
                        <p class="text-muted">
                            You can add as many scenes as you want, but probably not all components will be available in all scenes.
                        </p>

                            <div class="form-group text-left">
                                <label for="scene-name">Scene Name</label>
                                <input type="text" name="the-name" class="form-control" id="scene-name" aria-describedby="emailHelp" placeholder="Untitled Scene">
                            </div>

                    </div>
                </div>
                <div class="modal-footer d-flex justify-content-between">
                    <button class="btn btn-danger" data-dismiss="modal" type="button">
                        <i class="fa fa-arrow-alt-circle-left"></i> Cancel
                    </button>
                    <button class="btn btn-success" type="submit">
                        <i class="fa fa-plus-square"></i> Add
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
</body>
</html>