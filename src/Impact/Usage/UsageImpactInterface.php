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
 * @copyright Copyright (C) 2024 by the carbon plugin team.
 * @license   MIT https://opensource.org/licenses/mit-license.php
 * @link      https://github.com/pluginsGLPI/carbon
 * -------------------------------------------------------------------------
 */

namespace GlpiPlugin\Carbon\Impact\Usage;

use CommonDBTM;
use GlpiPlugin\Carbon\Engine\V1\EngineInterface;

interface UsageImpactInterface
{
    /**
     * Get an instance of the impact calculation engine for the itemtype of the analyzed object
     *
     * @param CommonDBTM $item
     * @return EngineInterface
     */
    // public static function getEngine(CommonDBTM $item): EngineInterface;

    /**
     * Get  the itemtype of the asset handled by this class
     *
     * @return string
     */
    public static function getItemtype(): string;

    /**
     * Set the maximum count of items to calculate with evaluateItems()
     *
     * @param integer $limit
     * @return void
     */
    public function setLimit(int $limit);

    /**
     * Get query to find items we can evaluate
     * @param array $crit Criterias to aass to WHERE clause
     *
     * @return array
     */
    public function getEvaluableQuery(array $crit = []): array;

    /**
     * Start the evaluation of all items
     *
     * @return int count of successfully evaluated assets
     */
    public function evaluateItems(): int;

    /**
     * Evaluate all impacts of the asset
     *
     * @param integer    $id
     * @return bool      true if success, false otherwise
     */
    public function evaluateItem(int $id): bool;
}
