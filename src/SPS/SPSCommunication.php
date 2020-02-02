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

namespace Ikarus\WEB\SPS;


use Ikarus\WEB\Exception\CommunicationException;
use Ikarus\WEB\Exception\SocketException;

class SPSCommunication
{
    /** @var string */
    private $address;
    /** @var int */
    private $port;

    /** @var float */
    private $timeout = 1.0;

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

    /**
     * Send silently to SPS
     *
     * @param $command
     * @param $error
     * @return string|null
     */
    public function sendSilentlyToSPS($command, &$error = NULL) {
        try {
            return $this->sendToSPS($command);
        } catch (\Throwable $exception) {
            $error = $exception;
        }
        return NULL;
    }

    /**
     * @return float
     */
    public function getTimeout(): float
    {
        return $this->timeout;
    }

    /**
     * @param float $timeout
     */
    public function setTimeout(float $timeout): void
    {
        $this->timeout = $timeout;
    }
}