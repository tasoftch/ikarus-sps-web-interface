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

use Ikarus\Logic\Editor\ComponentSerialization;
use Ikarus\Logic\Editor\Localization\LocalizationInterface;
use Ikarus\SPS\Helper\PluginManager;
use Ikarus\SPS\Helper\ProcessManager;
use Ikarus\SPS\Plugin\PluginManagementInterface;
use Ikarus\SPS\Plugin\Trigger\CallbackTriggerPlugin;
use Ikarus\WEB\SPS\Plugin\LogicPlugin;
use Ikarus\WEB\SPS\Plugin\Trigger\WebCommunicationPlugin;
use Ikarus\Web\WebServerProcess;
use TASoft\Util\Pipe\UnixPipe;
use Ikarus\SPS\Engine;

class WebEngine extends Engine implements WebEngineInterface
{
    /** @var string  */
    private $hostname = 'localhost';
    /** @var int  */
    private $port = 25000;
    /** @var LocalizationInterface|null */
    private $localization;
    /** @var bool */
    private $launchSPS = false;


    /**
     * WebEngine constructor.
     * @param string $hostname
     * @param int $port
     * @param string $name
     */
    public function __construct($hostname = 'localhost', $port = 25000, $name = 'Ikarus SPS, (c) by TASoft Applications')
    {
        parent::__construct($name);
        $this->hostname = $hostname;
        $this->port = $port;
    }


    public function run()
    {
        /** @var WebCommunicationPlugin $remoteServer */
        $remoteServer = new WebCommunicationPlugin();
        $webProcessManager = new ProcessManager();


        echo "## Main Process: ", getmypid(), "\n";

        $webServer = new WebServerProcess($this->getHostname(), $this->getPort());
        $webServer->run();

        echo "## Web Process : ", $webServer->getProcessID(), "\n";


        try {
            $didLaunch = 0;
            $webPluginManager = new PluginManager();

            $from = new UnixPipe();
            $to = new UnixPipe();

            $remoteServer->setToProcessPipe($to);
            $remoteServer->setFromProcessPipe($from);

            $webProcessManager->fork($remoteServer);
            if(!$webProcessManager->isMainProcess()) {
                // Is in plugin listener
                echo "## Run WEB/SPS : ", getmypid(), "\n";

                $remoteServer->run($webPluginManager);
                exit();
            }

            $port = $from->receiveData(true);

            $logicPlugin = NULL;
            foreach($this->getPlugins() as $plugin) {
                if($plugin instanceof LogicPlugin) {
                    $logicPlugin = $plugin;
                    $this->removePlugin($plugin);
                    break;
                }
            }


            file_put_contents( $this->getConfigFileName(), json_encode([
                'IKARUS_SPS_WEBC_ADDR' => $this->getMyHostname(),
                'IKARUS_SPS_WEBC_PORT' => $port,
                'IKARUS_SPS_PROCID' => getmypid(),
                'IKARUS_SPS_LOGIC_ENABLED' => $logicPlugin ? true : false
            ], JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES) );

            $this->addPlugin(new CallbackTriggerPlugin(function(PluginManagementInterface $management) use ($from, $to) {
                $command = $from->receiveData();
                $response = "OK";

                if($command == 'status') {
                    $response = 'running';
                } elseif($command == 'idle') {
                    $management->dispatchEvent("QUIT");
                }

                $to->sendData($response);
            }));

            while (1) {
                if(!$this->launchSPS() || $didLaunch) {
                    // Wait for signal from webserver (signal: run, stop, idle and status)
                    echo "WIFS: ";

                    $command = $from->receiveData();
                    $response = "OK";

                    echo $command, PHP_EOL;

                    if($command == 'status') {
                        $response = $didLaunch ? 'idle' : 'ready';
                    } elseif($command == 'components' && $logicPlugin) {
                        $response = ComponentSerialization::getSerializedComponents( $this->getPlugins(), 'serialize', $this->getLocalization() );
                    }

                    $to->sendData($response);
                    if($command == 'quit')
                        break;
                    if($command != 'run')
                        continue;

                    echo "... launching SPS ...\n";
                }

                $didLaunch = 1;

                if($logicPlugin) {
                    $logicPlugin->loadCompiledProject();
                    $this->addPlugin($logicPlugin);
                }

                parent::run();

                if($logicPlugin)
                    $this->removePlugin($logicPlugin);
            }
        } catch (\Throwable $exception) {
            echo "** " . $exception->getMessage(), "\n";
            throw $exception;
        } finally {
            $webServer->kill();
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

    /**
     * @return LocalizationInterface|null
     */
    public function getLocalization(): ?LocalizationInterface
    {
        return $this->localization;
    }

    /**
     * @param LocalizationInterface|null $localization
     */
    public function setLocalization(?LocalizationInterface $localization)
    {
        $this->localization = $localization;
    }

    /**
     * @return string
     */
    public function getHostname(): string
    {
        return $this->hostname;
    }

    /**
     * @return int
     */
    public function getPort(): int
    {
        return $this->port;
    }

    /**
     * If this method returns true, the SPS engine will run immediately after running the web server.
     * Otherwise you need to start the SPS via the web interface.
     *
     * @return bool
     */
    public function launchSPS(): bool
    {
        return $this->launchSPS;
    }

    /**
     * @param bool $launchSPS
     */
    public function setLaunchSPS(bool $launchSPS): void
    {
        $this->launchSPS = $launchSPS;
    }
}