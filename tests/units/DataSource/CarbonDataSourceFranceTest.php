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

namespace GlpiPlugin\Carbon\DataSource\Tests;

use DateTime;
use GlpiPlugin\Carbon\DataSource\CarbonDataSourceFrance;
use GlpiPlugin\Carbon\Tests\CommonTestCase;

// curl -X 'GET' \
//   'https://odre.opendatasoft.com/api/explore/v2.1/catalog/datasets/eco2mix-national-tr/records?select=taux_co2%2Cdate_heure&where=date_heure%20IN%20%5Bdate%272024-06-16T14%3A00%3A00%27%20TO%20date%272024-06-16T20%3A00%3A00%27%5D&order_by=date_heure%20desc&limit=50&offset=0&timezone=UTC&include_links=false&include_app_metas=false' \
//   -H 'accept: application/json; charset=utf-8'

class CarbonDataSourceFranceTest extends CommonTestCase
{
    public function testGetCarbonIntensity()
    {
        $source = new CarbonDataSourceFrance();

        $date = new DateTime();
        $intensity = $source->getCarbonIntensity('France', '', '', $date);

        $this->assertIsInt($intensity);
        $this->assertTrue(0 < $intensity && $intensity < 100);
    }
}
