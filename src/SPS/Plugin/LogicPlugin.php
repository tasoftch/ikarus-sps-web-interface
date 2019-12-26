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

namespace Ikarus\WEB\SPS\Plugin;


use Ikarus\Logic\EngineInterface;
use Ikarus\SPS\Event\DispatchedEventInterface;
use Ikarus\SPS\Exception\SPSException;
use Ikarus\SPS\Plugin\AbstractPlugin;
use Ikarus\SPS\Plugin\Listener\ListenerPluginInterface;
use TASoft\EventManager\EventManager;

/**
 * If you want to use the Ikarus Logic workflow, you need to add one logic plugin to the SPS engine.
 *
 * @package Ikarus\WEB\SPS\Plugin
 */
class LogicPlugin extends AbstractPlugin implements ListenerPluginInterface
{
    /** @var string */
    private $compiledProjectFilename;

    /** @var EngineInterface */
    private $logicEngine;

    /**
     * @return EngineInterface
     */
    public function getLogicEngine(): EngineInterface
    {
        return $this->logicEngine;
    }

    /**
     * @param EngineInterface $logicEngine
     */
    public function setLogicEngine(EngineInterface $logicEngine)
    {
        $this->logicEngine = $logicEngine;
    }

    /**
     * LogicPlugin constructor.
     * @param string $compiledProjectFilename
     * @param string|null $logicEngineClass
     */
    public function __construct(string $compiledProjectFilename)
    {
        $this->compiledProjectFilename = $compiledProjectFilename;
        $this->logicEngineClass = $logicEngineClass;
    }


    /**
     * @return string
     */
    public function getCompiledProjectFilename(): string
    {
        return $this->compiledProjectFilename;
    }

    /**
     * Called by the web engine to recompile the project.
     */
    public function loadCompiledProject() {
        throw new SPSException("Test Scheisse");
    }

    public function getEventNames(): array
    {
        // TODO: Implement getEventNames() method.
    }

    public function __invoke(string $eventName, DispatchedEventInterface $event, EventManager $eventManager, ...$arguments)
    {
        // TODO: Implement __invoke() method.
    }
}