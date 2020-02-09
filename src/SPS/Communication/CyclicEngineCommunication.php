<?php

namespace Ikarus\WEB\SPS\Communication;


use Ikarus\SPS\Exception\SPSException;

class CyclicEngineCommunication extends AbstractCommunication implements CommunicationInterface
{
    /** @var string */
    private $unixAddress;

    /**
     * @return string
     */
    public function getUnixAddress(): string
    {
        return $this->unixAddress;
    }

    /**
     * CyclicEngineCommunication constructor.
     * @param string $unixAddress
     */
    public function __construct(string $unixAddress)
    {
        $this->unixAddress = $unixAddress;
    }

    public function sendToSPS($command)
    {
        $sock = socket_create(AF_UNIX, SOCK_STREAM, 0);
        if(socket_connect($sock, $this->unixAddress) === false)
            throw new SPSException("Can not connect to unix address $this->unixAddress");

        socket_write($sock, $command);
        $buf = socket_read($sock, 2048);
        socket_close($sock);
        return $buf;
    }
}