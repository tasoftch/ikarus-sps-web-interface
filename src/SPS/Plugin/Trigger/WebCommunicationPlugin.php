<?php

namespace Ikarus\WEB\SPS\Plugin\Trigger;


use Ikarus\SPS\Plugin\AbstractPlugin;
use Ikarus\SPS\Plugin\Management\TriggeredPluginManagementInterface;
use Ikarus\SPS\Plugin\TearDownPluginInterface;
use Ikarus\SPS\Plugin\Trigger\TriggerPluginInterface;
use TASoft\Util\Pipe\PipeInterface;

class WebCommunicationPlugin extends AbstractPlugin implements TriggerPluginInterface, TearDownPluginInterface
{
    private $toProcessPipe;
    private $fromProcessPipe;

    /** @var resource */
    private $sock, $msgsock;
    public $tearDown = false;

    public function __construct(PipeInterface $toProcessPipe = NULL, PipeInterface $fromProcessPipe = NULL)
    {
        $this->toProcessPipe = $toProcessPipe;
        $this->fromProcessPipe = $fromProcessPipe;
    }

    public function run(TriggeredPluginManagementInterface $manager)
    {
        if (($this->sock = $sock = socket_create(AF_INET, SOCK_STREAM, SOL_TCP)) === false) {
            trigger_error( "socket_create() failed: " . socket_strerror(socket_last_error()), E_USER_WARNING);
        }

        if (socket_bind($sock, "0.0.0.0", 0) === false) {
            trigger_error( "socket_bind() failed: " . socket_strerror(socket_last_error($sock)), E_USER_WARNING);
        }

        socket_getsockname($sock, $name, $port);

        $this->getFromProcessPipe()->sendData($port);

        if (socket_listen($sock, 5) === false) {
            trigger_error( "socket_listen() failed: " . socket_strerror(socket_last_error($sock)), E_USER_WARNING);
        }
        do {
            $this->msgsock = $msgsock = socket_accept($sock);

            pcntl_signal_dispatch();

            if ($this->msgsock === false) {
                trigger_error( "socket_accept() failed: " . socket_strerror(socket_last_error($sock)), E_USER_WARNING);
                break;
            }

            declare(ticks=1) {
                $buf = socket_read ($msgsock, 2048, 0);
            }
            pcntl_signal_dispatch();

            if (false === $buf) {
                trigger_error( "socket_read() failed: " . socket_strerror(socket_last_error($msgsock)) , E_USER_WARNING);
                socket_close($msgsock);
                $this->msgsock = NULL;
                break;
            }

            $this->getFromProcessPipe()->sendData($buf);
            $response = $this->getToProcessPipe()->receiveData();

            socket_write($msgsock, $response, strlen($response));

            socket_close($msgsock);
            $this->msgsock = NULL;
        } while (1);

        socket_close($sock);
        $this->sock = NULL;
    }

    public function tearDown()
    {
        if($this->tearDown) {
            if($this->msgsock)
                socket_close($this->msgsock);
            if($this->sock)
                socket_close($this->sock);
        }
    }

    /**
     * @return PipeInterface
     */
    public function getToProcessPipe(): PipeInterface
    {
        return $this->toProcessPipe;
    }

    /**
     * @return PipeInterface
     */
    public function getFromProcessPipe(): PipeInterface
    {
        return $this->fromProcessPipe;
    }
}