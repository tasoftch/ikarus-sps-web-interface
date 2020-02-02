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

interface WebSPSControlInterface
{
    /**
     * Return here the sps you want to control with this web controller
     *
     * @return EngineInterface
     */
    public function getControlledSPS(): EngineInterface;

    /**
     * Runs the sps controller.
     * Please note that this method call does not run the sps! Only the controller.
     *
     * Please note that you can only access the SPS web control via the hostname and the given port.
     * Example: hostname: 192.168.0.100 and port 8000
     *  So type http://192.168.0.100:8000 in your browser.
     *
     * @param string $host  Normally an ip address to reach this sps web control
     * @param int $port     The port number to reach the web controller
     * @return int
     */
    public function run(string $host = '0.0.0.0', int $port = 80);

    /**
     * Gets a localization instance to translate labels into desired languages.
     *
     * @return LocalizationInterface|null
     */
    public function getLocalization(): ?LocalizationInterface;
}