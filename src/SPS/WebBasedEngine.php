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

namespace Ikarus\SPS;


use Ikarus\SPS\Exception\SPSException;
use Ikarus\SPS\Helper\PluginManager;
use Ikarus\SPS\Helper\ProcessManager;
use Ikarus\SPS\Plugin\Trigger\WebCommunicationPlugin;
use Ikarus\Web\WebServerProcess;

class WebBasedEngine extends Engine
{
    private $HttpHost = 'localhost';
    private $HttpPort = 25000;

    /**
     * @var bool If set to true, starts the SPS on launch, otherwise you must start the SPS from web interface
     */
    public $immediatelyRunSPSOnStart = false;


    public function run()
    {
        /** @var WebCommunicationPlugin $remoteServer */
        $remoteServer = NULL;
        foreach($this->plugins->getOrderedElements() as $plugin) {
            if($plugin instanceof WebCommunicationPlugin) {
                $remoteServer = $plugin;
                break;
            }
        }

        if($remoteServer) {
            $this->plugins->remove($remoteServer);

            $host = $remoteServer->getAddress();
            $port = $remoteServer->getPort();

            putenv("IKARUS_SPS_COMMUNICATION_ADDR=$host");
            putenv("IKARUS_SPS_COMMUNICATION_PORT=$port");

            if($this->getHttpHost() && $this->getHttpPort()) {
                $webServer = new WebServerProcess($this->getHttpHost(), $this->getHttpPort());
                $webServer->run();

                try {
                    $didLaunch = 0;
                    $webProcessManager = new ProcessManager();
                    $webProcessManager->fork($remoteServer);

                    $webPluginManager = new PluginManager();

                    if(!$webProcessManager->isMainProcess()) {
                        // Is in plugin listener
                        $remoteServer->run($webPluginManager);
                        exit();
                    }

                    while (1) {
                        if(!$this->immediatelyRunSPSOnStart || $didLaunch) {
                            // Wait for signal from webserver (signal: run, stop
                            if($webPluginManager->trapEvent($name, $event, $arguments)) {
                                if(strcasecmp($name, "stop") === 0)
                                    break;
                                if(strcasecmp($name, 'run') !== 0)
                                    continue;
                            }
                        }
                        $didLaunch = 1;
                        parent::run();
                    }
                } catch (\Throwable $exception) {
                    throw $exception;
                } finally {
                    $webServer->kill();
                    $webProcessManager->killAll();
                }
            } else
                throw new SPSException("No host/port specified to run web server");
        } else {
            throw new SPSException("Web SPS Engine can not run. No WebCommunicationPlugin defined for communication");
        }
    }

    /**
     * @return int
     */
    public function getHttpPort(): int
    {
        return $this->HttpPort;
    }

    /**
     * @param int $HttpPort
     */
    public function setHttpPort(int $HttpPort): void
    {
        $this->HttpPort = $HttpPort;
    }

    /**
     * @return string
     */
    public function getHttpHost(): string
    {
        return $this->HttpHost;
    }

    /**
     * @param string $HttpHost
     */
    public function setHttpHost(string $HttpHost): void
    {
        $this->HttpHost = $HttpHost;
    }
}