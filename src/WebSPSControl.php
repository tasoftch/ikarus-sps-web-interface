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

namespace Ikarus\WEB;


use Ikarus\Logic\Editor\Localization\LocalizationInterface;
use Ikarus\SPS\EngineInterface;
use Ikarus\SPS\Helper\ProcessManager;
use Ikarus\SPS\Helper\TriggeredPluginManager;
use Ikarus\SPS\Logic\Plugin\AbstractLogicEnginePlugin;
use Ikarus\SPS\Plugin\Management\TriggeredPluginManagementInterface;
use Ikarus\SPS\Plugin\Trigger\CallbackTriggerPlugin;
use Ikarus\SPS\TriggeredEngine;
use Ikarus\WEB\Helper\WebCommunicationPlugin;
use Ikarus\WEB\Helper\WebServerProcess;
use TASoft\Util\Pipe\UnixPipe;

class WebSPSControl implements WebSPSControlInterface
{
    /** @var LocalizationInterface|null */
    private $localization;
    /** @var EngineInterface */
    private $sps;

    public $debug = false;

    /**
     * WebSPSControl constructor.
     * @param EngineInterface $spsEngine
     */
    public function __construct(EngineInterface $spsEngine)
    {
        $this->sps = $spsEngine;
    }

    /**
     * @return LocalizationInterface|null
     */
    public function getLocalization(): ?LocalizationInterface
    {
        return $this->localization;
    }

    public function getControlledSPS(): EngineInterface
    {
        return $this->sps;
    }

    protected function debug($msg, ...$args) {
        if($this->debug)
            vprintf($msg, $args);
    }

    public function run(string $host = '0.0.0.0', int $port = 80)
    {
        $webProcessManager = new ProcessManager();
        $this->debug("## Main Process: %d\n", getmypid());

        try {
            $didLaunch = 0;
            $logicPlugin = NULL;

            $sps = $this->sps;

            foreach($sps->getPlugins() as $plugin) {
                if($plugin instanceof AbstractLogicEnginePlugin) {
                    $logicPlugin = $plugin;
                    break;
                }
            }

            $webPluginManager = new TriggeredPluginManager();

            $from = new UnixPipe();
            $to = new UnixPipe();

            $webServer = new WebServerProcess($host, $port);
            $webServer->run();

            $this->debug("## Web Process: %d\n", $webServer->getProcessID());

            $webServerCommunication = new WebCommunicationPlugin($to, $from);
            $webProcessManager->fork($webServerCommunication);
            if(!$webProcessManager->isMainProcess()) {
                // Is in plugin listener
                echo "## Run WEB/SPS : ", getmypid(), "\n";

                $webServerCommunication->run($webPluginManager);
                exit();
            }

            $port = $from->receiveData(true);


            file_put_contents( $this->getConfigFileName(), json_encode([
                'IKARUS_SPS_WEBC_ADDR' => $this->getMyHostname(),
                'IKARUS_SPS_WEBC_PORT' => $port,
                'IKARUS_SPS_PROCID' => getmypid(),
                'IKARUS_SPS_LOGIC_ENABLED' => $logicPlugin ? true : false
            ], JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES) );

            if($sps instanceof TriggeredEngine) {
                // Add a status and stop sps trigger
                $sps->addPlugin(new CallbackTriggerPlugin(function(TriggeredPluginManagementInterface $management) use ($from, $to) {
                    $command = $from->receiveData();
                    $response = "OK";

                    if($command == 'status') {
                        $response = 'running';
                    } elseif($command == 'idle') {
                        $management->dispatchEvent("QUIT");
                    }

                    $to->sendData($response);
                }));


            }

        } catch (\Throwable $exception) {
            printf( "\033[0;31m** Fatal Error: %s\033[0m\n" , $exception->getMessage());
        } finally {
            $webServer->kill(SIGTERM);
            $webProcessManager->killAll();
        }
    }

    /**
     * Gets the configuration file name to tell the web server, how to reach the SPS
     *
     * @return string
     */
    public function getConfigFileName(): string {
        return "config.json";
    }

    /**
     * Called to detect the hostname of the SPS engine
     *
     * @return string
     */
    public function getMyHostname(): string {
        if(preg_match_all('/inet.+?(\d+\.\d+\.\d+\.\d+)/i', `ifconfig`, $ips)) {
            foreach($ips[1] as $ip) {
                if($ip == '127.0.0.1')
                    continue;
                return $ip;
            }
        }
        return "127.0.0.1";
    }
}