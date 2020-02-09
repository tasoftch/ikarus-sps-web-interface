<?php

namespace Ikarus\WEB\SPS\Plugin\Cyclic;


use Ikarus\SPS\Exception\SPSException;
use Ikarus\SPS\Plugin\AbstractPlugin;
use Ikarus\SPS\Plugin\Cyclic\CyclicPluginInterface;
use Ikarus\SPS\Plugin\Management\CyclicPluginManagementInterface;
use Ikarus\SPS\Plugin\TearDownPluginInterface;

class WebCommunicationPlugin extends AbstractPlugin implements CyclicPluginInterface, TearDownPluginInterface
{
    private $socket;
    private $socketName;

    public $stopControl = false;
    public $stopEngine = false;
    public $run = false;

    public $status = 'running';


    public function __construct($socketName = 'ikarus.sock')
    {
        $this->socketName = $socketName;
    }


    public function update(CyclicPluginManagementInterface $pluginManagement)
    {
        if(!$this->socket) {
            $this->socket = socket_create(AF_UNIX, SOCK_STREAM, 0);
            if(!socket_bind($this->socket, $this->socketName))
                throw new SPSException("Can not bind to unix address $this->socketName");

            if(!socket_listen($this->socket, 1))
                throw new SPSException("Can not listen on unix address $this->socketName");

            socket_set_nonblock($this->socket);
        }
        
        $msgsock = socket_accept($this->socket);
        if($msgsock) {
            $buffer = socket_read($msgsock, 2048);
            $response = $this->doCommand($buffer, $pluginManagement);
            socket_write($msgsock, $response);
            socket_close($msgsock);
        }
    }

    protected function doCommand($cmd, CyclicPluginManagementInterface $management) {
        switch ($cmd) {
            case 'status': return $this->status;
            case 'stop': $this->stopControl = true;
            case 'idle': $this->stopEngine = true; $management->stopEngine(); return 'OK';
            case 'run': $this->run = true; return 'OK';
            default:
                return -1;
        }
    }

    public function tearDown()
    {
        if($this->socket) {
            socket_close($this->socket);
            @unlink($this->socketName);
            $this->socket = NULL;
        }
    }
}