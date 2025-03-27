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

/**
 * This file is *REQUIRED* in the whole life time of the plugin
 * used in install process
 */

/**
 * Centralize ID of search options in a single place for btter handling
 * in case of conflict with an other plugin
 */
class SearchOptions
{
    private const SEARCH_OPTION_BASE = 128000;

    public const COMPUTER_USAGE_PROFILE_START_TIME = self::SEARCH_OPTION_BASE + 101;
    public const COMPUTER_USAGE_PROFILE_STOP_TIME  = self::SEARCH_OPTION_BASE + 102;
    public const COMPUTER_USAGE_PROFILE_DAY_1      = self::SEARCH_OPTION_BASE + 110;
    public const COMPUTER_USAGE_PROFILE_DAY_2      = self::SEARCH_OPTION_BASE + 111;
    public const COMPUTER_USAGE_PROFILE_DAY_3      = self::SEARCH_OPTION_BASE + 112;
    public const COMPUTER_USAGE_PROFILE_DAY_4      = self::SEARCH_OPTION_BASE + 113;
    public const COMPUTER_USAGE_PROFILE_DAY_5      = self::SEARCH_OPTION_BASE + 114;
    public const COMPUTER_USAGE_PROFILE_DAY_6      = self::SEARCH_OPTION_BASE + 115;
    public const COMPUTER_USAGE_PROFILE_DAY_7      = self::SEARCH_OPTION_BASE + 116;

    public const USAGE_INFO_COMPUTER_USAGE_PROFILE = self::SEARCH_OPTION_BASE + 202;

    public const HISTORICAL_DATA_SOURCE     = self::SEARCH_OPTION_BASE + 301;
    public const HISTORICAL_DATA_DL_ENABLED = self::SEARCH_OPTION_BASE + 302;

    public const CARBON_INTENSITY_SOURCE    = self::SEARCH_OPTION_BASE + 401;
    public const CARBON_INTENSITY_ZONE      = self::SEARCH_OPTION_BASE + 402;
    public const CARBON_INTENSITY_INTENSITY = self::SEARCH_OPTION_BASE + 403;

    public const POWER_CONSUMPTION = self::SEARCH_OPTION_BASE + 500;
    public const IS_HISTORIZABLE   = self::SEARCH_OPTION_BASE + 502;
    public const USAGE_PROFILE     = self::SEARCH_OPTION_BASE + 501;

    public const CARBON_EMISSION_DATE             = self::SEARCH_OPTION_BASE + 600;
    public const CARBON_EMISSION_ENERGY_PER_DAY   = self::SEARCH_OPTION_BASE + 601;
    public const CARBON_EMISSION_PER_DAY          = self::SEARCH_OPTION_BASE + 602;
    public const CARBON_EMISSION_ENERGY_QUALITY   = self::SEARCH_OPTION_BASE + 603;
    public const CARBON_EMISSION_EMISSION_QUALITY = self::SEARCH_OPTION_BASE + 604;
    public const CARBON_EMISSION_CALC_DATE        = self::SEARCH_OPTION_BASE + 605;
    public const CARBON_EMISSION_ENGINE           = self::SEARCH_OPTION_BASE + 606;
    public const CARBON_EMISSION_ENGINE_VER       = self::SEARCH_OPTION_BASE + 607;
}
