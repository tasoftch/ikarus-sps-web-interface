<?php
/**
 * @var SPSCommunication $communication
 */


use Ikarus\WEB\SPSCommunication;

$data = $communication->sendSilentlyToSPS("components");
$components = unserialize($data);

print_r($components);

    exit();
?>
var signalSocket = new Rete.Socket('Signal');

var anySocket = new Rete.Socket('Any');
var numberSocket = new Rete.Socket('Number');
var stringSocket = new Rete.Socket('String');

var boolSocket = new Rete.Socket("Boolean");

anySocket.combineWith(numberSocket);
anySocket.combineWith(stringSocket);
anySocket.combineWith(boolSocket);

numberSocket.combineWith(stringSocket);

numberSocket.combineWith(boolSocket);
stringSocket.combineWith(boolSocket);

var Stage0ValueControl = {
    template: '<input type="text"/>',
    data() {
        return {
            value: 0
        };
    },
    methods: {
            update() {
                if (this.root) {
                    this.putData(this.ikey, this.root.value);
                }

                this.emitter.trigger("process");
            }
        },

    mounted() {
        const _self = this;

        this.root.value = this.getData(this.ikey);

        this.root.onkeyup = function (e) {
            _self.root.update();
        };

        this.root.onmouseup = function (e) {
            _self.root.update();
        };

        this.root.ondblclick = function (e) {
            e.stopPropagation();
        };
    }
};


class ValueControl extends Rete.Control {
    constructor(emitter, key, readonly) {
        super(key);
        this.component = Stage0ValueControl;
        this.props = { emitter, ikey: key, readonly };
    }

    setValue(val) {
        this.stage0Context.root.value = val;
    }
}

var components = [];

<?php
/**
 * @var SPSCommunication $communication
 */


use Ikarus\WEB\SPSCommunication;

$data = $communication->sendSilentlyToSPS("components");
$components = unserialize($data);

if(is_iterable($components)) {
    foreach($components as $componentName => $component) {
        $class = preg_replace("/[^a-z0-9_]/i", '_', $componentName);

        ?>
class <?=$class?>Component extends Rete.Component {
    constructor(name, label, menuPath) {
        super(name);
        this.label = label;
        this.path = menuPath;
    }

    builder(node)
    {
<?php
        foreach(($component['outputs'] ?? []) as $oid => $output) {

            printf( "\t\tnode.addOutput(new Rete.Output('$oid', %s, %s, %s));\n", var_export($output["label"], true), $output["type"], var_export($output["multiple"], true));
        }

        foreach(($component['inputs'] ?? []) as $oid => $input) {
            if($input["type"] == 'signalSocket')
                $input["multiple"] = true;

            printf( "\t\tnode.addInput(new Rete.Input('$oid', %s, %s, %s));\n", var_export($input["label"], true), $input["type"], var_export($input["multiple"], true));
        }

        if($cnts = $component["controls"] ?? NULL) {
            foreach($cnts as $cnt)
                echo "\t\tnode.addControl( new $cnt(this.editor, 'value') );\n";
        }
        ?>
        node.label = this.label;
    }
}
<?php
        if($ids = $component["identifiers"] ?? NULL) {
            foreach($ids as $id => $info) {
                printf("components.push( new {$class}Component(%s, %s, %s) );\n",
                    var_export($component["name"].":$id", true),
                    var_export($info["label"], true),
                    json_encode($info["menu"])
                );
            }
        } else {
            printf("components.push( new {$class}Component(%s, %s, %s) );\n",
                var_export($component["name"], true),
                var_export($component["label"], true),
                json_encode($component["menu"])
            );
        }
        echo "\n";
    }
}