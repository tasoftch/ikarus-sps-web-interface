<?php

namespace Ikarus\WEB\SPS\Plugin\Cyclic;


use Ikarus\SPS\Exception\SPSException;
use Ikarus\SPS\Plugin\AbstractPlugin;
use Ikarus\SPS\Plugin\Cyclic\CyclicPluginInterface;
use Ikarus\SPS\Plugin\Intermediate\AbstractIntermediatePlugin;
use Ikarus\SPS\Plugin\Intermediate\CyclicIntermediatePlugin;
use Ikarus\SPS\Plugin\Management\CyclicPluginManagementInterface;
use Ikarus\SPS\Plugin\Management\PluginManagementInterface;
use Ikarus\SPS\Plugin\TearDownPluginInterface;

class WebCommunicationPlugin extends CyclicIntermediatePlugin
{
    public $stopControl = false;
    public $stopEngine = false;
    public $run = false;

    public $status = 'running';

    protected function doCommand($cmd, PluginManagementInterface $management): string {
        switch ($cmd) {
            case 'status': return $this->status;
            case 'stop': $this->stopControl = true;
            case 'idle': $this->stopEngine = true; $management->stopEngine(); return 'OK';
            case 'run': $this->run = true; return 'OK';
            default:
                return -1;
        }
    }

    public function reuseAddress(): bool
    {
        return true;
    }
}