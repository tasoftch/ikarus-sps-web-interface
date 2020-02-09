<?php

namespace Ikarus\WEB\SPS\Communication;


use Ikarus\WEB\Exception\CommunicationException;
use Ikarus\WEB\Exception\SocketException;

class TriggerEngineCommunication extends AbstractCommunication implements CommunicationInterface
{
    /** @var string */
    private $address;
    /** @var int */
    private $port;


    /**
     * SPSCommunication constructor.
     * @param string $address
     * @param int $port
     */
    public function __construct(string $address, int $port)
    {
        $this->address = $address;
        $this->port = $port;
    }

    /**
     * @return string
     */
    public function getAddress(): string
    {
        return $this->address;
    }

    /**
     * @return int
     */
    public function getPort(): int
    {
        return $this->port;
    }

    /**
     * Sends a command to the SPS
     *
     * @param $command
     */
    public function sendToSPS($command) {
        $socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
        if($socket === 0)
            throw new SocketException("Could not create tcp socket");

        if(socket_connect($socket, $this->getAddress(), $this->getPort()) === false) {
            $e = new SocketException("Can not contact SPS. There might be a configuration error");
            $e->setSocket($socket);
            throw $e;
        }


        if(($to = $this->getTimeout()) > 0.1 ) {
            $sec = floor($to);
            $usec = $to - $sec;

            socket_set_option($socket,SOL_SOCKET, SO_RCVTIMEO, [
                "sec"=>$sec,
                "usec"=>$usec
            ]);
        }

        socket_write($socket, $command, strlen($command));

        $buffer = "";
        socket_clear_error($socket);
        while ($out = socket_read($socket, 2048)) {
            $buffer .= $out;
        }

        $error = socket_last_error($socket);
        socket_close($socket);
        if($error != 0) {
            $e = new CommunicationException(socket_strerror( $error ), $error);
            $e->setSocket($socket);
            throw $e;
        }
        return $buffer;
    }
}