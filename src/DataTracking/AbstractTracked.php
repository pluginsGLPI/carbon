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

namespace GlpiPlugin\Carbon\DataTracking;

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
    public const DATA_QUALITY_UNSPECIFIED = 0;
    public const DATA_QUALITY_MANUAL = 1;
    public const DATA_QUALITY_RAW_REAL_TIME_MEASUREMENT_DOWNSAMPLED = 2;
    public const DATA_QUALITY_RAW_REAL_TIME_MEASUREMENT = 3;

    protected array $sources = [];

    abstract public function getValue(): mixed;

    public function __construct(mixed $source)
    {
        if (is_integer($source)) {
            $this->sources[] = $source;
        } elseif ($source instanceof AbstractTracked) {
            $this->sources = $source->getSource();
        } else {
            throw new LogicException('Invalid source');
        }
    }

    public function getSource(): array
    {
        return $this->sources;
    }

    protected function appendSource(int $source): AbstractTracked
    {
        $this->sources[] = $source;
        return $this;
    }

    public function getLowestSource()
    {
        return min($this->sources);
    }
}
