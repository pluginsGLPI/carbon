<?php

/**
 * -------------------------------------------------------------------------
 * carbon plugin for GLPI
 * -------------------------------------------------------------------------
 *
 * MIT License
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
 * SOFTWARE.
 * -------------------------------------------------------------------------
 * @copyright Copyright (C) 2024 Teclib' and contributors.
 * @license   MIT https://opensource.org/licenses/mit-license.php
 * @link      https://github.com/pluginsGLPI/carbon
 * -------------------------------------------------------------------------
 */

namespace GlpiPlugin\Carbon\Tests\History;

use GlpiPlugin\Carbon\Tests\DbTestCase;
use Computer as GlpiComputer;
use DateTime;
use DateInterval;
use DateTimeImmutable;
use GlpiPlugin\Carbon\CarbonEmission;
use Infocom;

class CommonAsset extends DbTestCase
{
    protected string $history_type = '';

    public function testGetStartDate()
    {
        $computer = $this->getItem(GlpiComputer::class, ['date_creation' => null, 'date_mod' => null]);
        $instance = new $this->history_type();
        $output = $this->callPrivateMethod($instance, 'getStartDate', $computer->getID());
        $this->assertNull($output);

        $computer->update([
            'id' => $computer->getID(),
            'comment' => 'test date_mod',
        ]);
        $output = $this->callPrivateMethod($instance, 'getStartDate', $computer->getID());
        $this->assertEquals($_SESSION["glpi_currenttime"], $output->format('Y-m-d H:i:s'));

        $computer->update([
            'id' => $computer->getID(),
            'date_creation' => '2019-01-01 00:00:00',
        ]);
        $output = $this->callPrivateMethod($instance, 'getStartDate', $computer->getID());
        $this->assertEquals('2019-01-01 00:00:00', $output->format('Y-m-d H:i:s'));

        $infocom = $this->getItem(Infocom::class, [
            'itemtype' => $computer->getType(),
            'items_id' => $computer->getID(),
        ]);

        $output = $this->callPrivateMethod($instance, 'getStartDate', $computer->getID());
        $this->assertEquals('2019-01-01 00:00:00', $output->format('Y-m-d H:i:s'));

        $infocom->update([
            'id'       => $infocom->getID(),
            'buy_date' => '2018-01-01 00:00:00',
        ]);
        $output = $this->callPrivateMethod($instance, 'getStartDate', $computer->getID());
        $this->assertEquals('2018-01-01 00:00:00', $output->format('Y-m-d H:i:s'));

        $infocom->update([
            'id'            => $infocom->getID(),
            'delivery_date' => '2018-01-01 00:00:00',
        ]);
        $output = $this->callPrivateMethod($instance, 'getStartDate', $computer->getID());
        $this->assertEquals('2018-01-01 00:00:00', $output->format('Y-m-d H:i:s'));

        $infocom->update([
            'id'       => $infocom->getID(),
            'use_date' => '2017-01-01 00:00:00',
        ]);
        $output = $this->callPrivateMethod($instance, 'getStartDate', $computer->getID());
        $this->assertEquals('2017-01-01 00:00:00', $output->format('Y-m-d H:i:s'));
    }
}
