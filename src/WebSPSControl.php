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
use Ikarus\SPS\CyclicEngine;
use Ikarus\SPS\EngineInterface;
use Ikarus\SPS\Helper\CyclicPluginManager;
use Ikarus\SPS\Helper\ProcessManager;
use Ikarus\SPS\Helper\TriggeredPluginManager;
use Ikarus\SPS\Logic\Plugin\AbstractLogicEnginePlugin;
use Ikarus\SPS\Plugin\Management\CyclicPluginManagementInterface;
use Ikarus\SPS\Plugin\Management\TriggeredPluginManagementInterface;
use Ikarus\SPS\Plugin\TearDownPluginInterface;
use Ikarus\SPS\Plugin\Trigger\CallbackTriggerPlugin;
use Ikarus\SPS\TriggeredEngine;
use Ikarus\WEB\Helper\WebServerProcess;
use TASoft\Util\Pipe\UnixPipe;
use TASoft\Util\ValueInjector;

class WebSPSControl implements WebSPSControlInterface
{
    /** @var LocalizationInterface|null */
    private $localization;
    /** @var EngineInterface */
    private $sps;

    private $startImmediately = false;

    public $debug = false;

    /**
     * WebSPSControl constructor.
     * @param EngineInterface $spsEngine
     */
    public function __construct(EngineInterface $spsEngine, bool $startImmediately = false)
    {
        $this->sps = $spsEngine;
        $this->startImmediately = $startImmediately;
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
            $logicPlugin = NULL;

            $sps = $this->sps;

            foreach($sps->getPlugins() as $plugin) {
                if($plugin instanceof AbstractLogicEnginePlugin) {
                    $logicPlugin = $plugin;
                    break;
                }
            }

            $webPluginManager = new TriggeredPluginManager();

            $webServer = new WebServerProcess($host, $port);
            $webServer->run();

            $this->debug("## Web Process: %d\n", $webServer->getProcessID());

            $configName = $this->getConfigFileName();

            if($sps instanceof TriggeredEngine) {
                $from = new UnixPipe();
                $to = new UnixPipe();

                $webServerCommunication = new \Ikarus\WEB\SPS\Plugin\Trigger\WebCommunicationPlugin($to, $from);
                $webProcessManager->fork($webServerCommunication);
                if(!$webProcessManager->isMainProcess()) {
                    // Is in plugin listener
                    echo "## Run WEB/SPS/IFCE : ", getmypid(), "\n";

                    $webServerCommunication->run($webPluginManager);
                    exit();
                }

                $port = $from->receiveData(true);
                file_put_contents( $configName, json_encode([
                    'IKARUS_SPS_WEBC_ADDR' => $this->getMyHostname(),
                    'IKARUS_SPS_WEBC_PORT' => $port,
                    'IKARUS_SPS_PROCID' => getmypid(),
                    'IKARUS_SPS_LOGIC_ENABLED' => $logicPlugin ? true : false,
                    "IKARUS_SPS_FILE_ROOT" => $file_root = getcwd()
                ], JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES) );

                // Add a status and stop sps trigger
                $sps->addPlugin(new CallbackTriggerPlugin(function(TriggeredPluginManagementInterface $management) use ($from, $to) {
                    $command = $from->receiveData();
                    $response = "OK";

                    if($command == 'status') {
                        $response = 'running';
                        $to->sendData($response);
                    } elseif($command == 'idle') {
                        $to->sendData($response);
                        $management->stopEngine();
                        return;
                    }
                }));

                $getControlCommand = function($response, $didLaunch) use ($from, $to) {
                    $command = $from->receiveData();
                    if($command == 'status')
                        $to->sendData($didLaunch ? 'idle' : 'ready');
                    else
                        $to->sendData("OK");
                    if($command == 'stop')
                        return 'quit';

                    return $command;
                };
            } elseif($sps instanceof CyclicEngine) {
                $file_root = getcwd();

                file_put_contents( $configName, json_encode([
                    'IKARUS_SPS_WEBC_UNIX' => "$file_root/ikarus.sock",
                    'IKARUS_SPS_PROCID' => getmypid(),
                    'IKARUS_SPS_LOGIC_ENABLED' => $logicPlugin ? true : false,
                    "IKARUS_SPS_FILE_ROOT" => $file_root
                ], JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES) );

                $sps->addPlugin( $comPlugin = new \Ikarus\WEB\SPS\Plugin\Cyclic\WebCommunicationPlugin() );

                $management = new CyclicPluginManager();
                $vi = new ValueInjector($management);
                $vi->f = function() use ($sps) {return $sps->getFrequency();};
                $vi->rtf = $vi->se = function() {};

                $getControlCommand = function($response) use ($comPlugin, $management) {
                    usleep( 1 / $management->getFrequency() * 1e6 );
                    $comPlugin->status = $response ?: 'ready';
                    $comPlugin->run = $comPlugin->stopControl = $comPlugin->stopEngine = false;

                    $comPlugin->update($management);

                    if($comPlugin->stopControl)
                        return 'quit';
                    if($comPlugin->run) {
                        $comPlugin->status = 'running';
                        return "run";
                    }

                    return "status";
                };
            }

            $didLaunch = 0;
            $lastResponse = NULL;

            echo "Welcome to Ikaurs SPS Tecnologies!\n";
            echo "You are running now the web controller for\n * \033[0,32m", $sps->getName(), "\033[0m\n";
            echo "The web interface is reachable under http://$host:$port\nHave Fun!\n\n";

            echo "Wait for instruction...";
            if($this->startImmediately())
                echo "\nrun\n";

            while(1) {
                if(!$this->startImmediately() || $didLaunch) {
                    $command = $getControlCommand($lastResponse, $didLaunch);
                    $lastResponse = 'OK';

                    if($command == 'status')
                        $lastResponse = $didLaunch ? 'idle' : 'ready';

                    if($command == 'quit') {
                        echo "\nquit\n";
                        break;
                    }

                    if($command != 'run') {
                        continue;
                    } else
                        echo "\nrun\n";
                }

                $didLaunch = 1;
                $sps->run();
                echo "Tear down...\n";
                echo "Wait for instruction...";
            }

            if(isset($comPlugin))
                $comPlugin->tearDown();
            if(isset($webServerCommunication)) {
                $webServerCommunication->tearDown = true;
                $webServerCommunication->tearDown();
            }
        } catch (\Throwable $exception) {
            printf( "\033[0;31m** Fatal Error: %s\033[0m\n" , $exception->getMessage());
        } finally {
            $webServer->kill(SIGTERM);
            $webProcessManager->killAll();
            $webProcessManager->waitForAll();
        }

        unlink($configName);

        echo "Good Bye!\n";
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

    /**
     * @return bool
     */
    public function startImmediately(): bool
    {
        return $this->startImmediately;
    }
}