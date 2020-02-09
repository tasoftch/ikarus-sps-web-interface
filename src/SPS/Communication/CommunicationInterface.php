<?php

namespace Ikarus\WEB\SPS\Communication;


interface CommunicationInterface
{
    /**
     * Sends a command to the SPS and expecting a response
     *
     * @param $command
     * @return string
     */
    public function sendToSPS($command);

    /**
     * @return float
     */
    public function getTimeout(): float;
}