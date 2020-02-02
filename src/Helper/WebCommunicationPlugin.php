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

namespace Ikarus\WEB\Helper;


use Ikarus\SPS\Plugin\AbstractPlugin;
use Ikarus\SPS\Plugin\Management\TriggeredPluginManagementInterface;
use Ikarus\SPS\Plugin\TearDownPluginInterface;
use Ikarus\SPS\Plugin\Trigger\TriggerPluginInterface;
use TASoft\Util\Pipe\PipeInterface;

/**
 * Class WebCommunicationPlugin
 * @package Ikarus\SPS\Plugin\Trigger
 * @internal
 */
class WebCommunicationPlugin extends AbstractPlugin implements TriggerPluginInterface, TearDownPluginInterface
{
    private $toProcessPipe;
    private $fromProcessPipe;

    /** @var resource */
    private $sock, $msgsock;

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
            if (($this->msgsock = $msgsock = socket_accept($sock)) === false) {
                trigger_error( "socket_accept() failed: " . socket_strerror(socket_last_error($sock)), E_USER_WARNING);
                break;
            }

            declare(ticks=1) {
                $buf = socket_read ($msgsock, 2048, 0);
            }

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
        if($this->msgsock)
            socket_close($this->msgsock);
        if($this->sock)
            socket_close($this->sock);
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