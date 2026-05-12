<?php

/**
 * -------------------------------------------------------------------------
 * Carbon plugin for GLPI
 *
 * @copyright Copyright (C) 2024-2025 Teclib' and contributors.
 * @license   https://www.gnu.org/licenses/gpl-3.0.txt GPLv3+
 * @link      https://github.com/pluginsGLPI/carbon
 *
 * -------------------------------------------------------------------------
 *
 * LICENSE
 *
 * This file is part of Carbon plugin for GLPI.
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 *
 * -------------------------------------------------------------------------
 */

namespace GlpiPlugin\Carbon\DataTracking;

use Dropdown;
use LogicException;

/**
 * Tracks the source of the contained data
 *
 * When the instance modelizes the result of a calculation
 * sources are ordered by retrieval order.
 * This allows an evaluation of the quality of the stored value.
 */
abstract class AbstractTracked
{
    /**
     * Quality of data, must be ordered from 0 (lowest quality) to highest quality
     */
    public const DATA_QUALITY_UNSET_VALUE = -1;
    public const DATA_QUALITY_UNSPECIFIED = 0;
    public const DATA_QUALITY_MANUAL = 1;
    public const DATA_QUALITY_ESTIMATED = 2;
    public const DATA_QUALITY_RAW_REAL_TIME_MEASUREMENT_DOWNSAMPLED = 3;
    public const DATA_QUALITY_RAW_REAL_TIME_MEASUREMENT = 4;

    /** @var array<int> $sources Source qualities */
    protected array $sources = [];

    abstract public function getValue();

    public function __construct($source = null)
    {
        if ($source === null) {
            return;
        }
        $this->appendSource($source);
    }

    /**
     * Get name of data qualities
     *
     * @return array
     */
    public static function getDataQualities(): array
    {
        return [
            self::DATA_QUALITY_UNSET_VALUE                           => __('Impact not evaluated', 'carbon'),
            self::DATA_QUALITY_UNSPECIFIED                           => __('Unspecified quality', 'carbon'),
            self::DATA_QUALITY_MANUAL                                => __('Manual data', 'carbon'),
            self::DATA_QUALITY_ESTIMATED                             => __('Estimated data', 'carbon'),
            self::DATA_QUALITY_RAW_REAL_TIME_MEASUREMENT_DOWNSAMPLED => __('Downsampled data', 'carbon'),
            self::DATA_QUALITY_RAW_REAL_TIME_MEASUREMENT             => __('Measured data', 'carbon'),
        ];
    }

    /**
     * Show or return HTML code displaying a dropdown of data qualities
     * @see constants DATA_QUALITY_*
     *
     * @param string $name
     * @param array $options
     * @return int|string
     */
    public static function dropdownQuality(string $name, array $options = [])
    {
        $items = self::getDataQualities();
        return Dropdown::showFromArray($name, $items, $options);
    }

    public function getSource(): array
    {
        return $this->sources;
    }

    public function appendSource($source): AbstractTracked
    {
        if (is_integer($source)) {
            $this->sources[] = $source;
        } elseif ($source instanceof AbstractTracked) {
            $this->sources = $source->getSource();
        } else {
            throw new LogicException('Invalid source');
        }
        return $this;
    }

    public function getLowestSource()
    {
        return min($this->sources);
    }
}
