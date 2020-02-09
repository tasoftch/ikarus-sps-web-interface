<?php

namespace Ikarus\WEB\SPS\Communication;


use Throwable;

abstract class AbstractCommunication implements CommunicationInterface
{
    /** @var float */
    private $timeout = 1.0;

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
        } catch (Throwable $exception) {
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

    public static function makeCommunication($config): ?CommunicationInterface {
        $host = $config["IKARUS_SPS_WEBC_ADDR"] ?? NULL;
        $port = $config["IKARUS_SPS_WEBC_PORT"] ?? 0;
        $unix = $config["IKARUS_SPS_WEBC_UNIX"] ?? NULL;

        if($host && $port)
            return new TriggerEngineCommunication($host, $port);
        elseif ($unix)
            return new CyclicEngineCommunication($unix);
        return NULL;
    }
}