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

namespace GlpiPlugin\Carbon;

use DateTime;
use DateTimeImmutable;

class Toolbox
{
    /**
     * Get the oldest asset date in the database
     *
     * @return DateTimeImmutable
     */
    public function getOldestAssetDate(): ?DateTimeImmutable
    {
        $itemtypes = Config::getSupportedAssets();
        $oldest_date = null;
        foreach ($itemtypes as $itemtype) {
            /** @var CommonDBTM $item */
            $item = new $itemtype();
            $result = $item->find([], ['date_creation DESC'], 1);
            if (count($result) === 1) {
                $row = array_pop($result);
                if ($oldest_date === null || $row['date_creation'] < $oldest_date) {
                    $oldest_date = $row['date_creation'];
                }
            }
        }

        if ($oldest_date === null) {
            return $this->getDefaultCarbonIntensityDownloadDate();
        }
        return DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $oldest_date);
    }

    /**
     * Get default date where environnemental imapct shouw be known
     * when no inventory data is available
     */
    public function getDefaultCarbonIntensityDownloadDate(): DateTimeImmutable
    {
        $start_date = new DateTime('1 year ago');
        $start_date->setDate($start_date->format('Y'), 1, 1);
        $start_date->setTime(0, 0, 0);
        return DateTimeImmutable::createFromMutable($start_date);
    }
}
