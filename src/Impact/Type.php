<?php

/**
 * -------------------------------------------------------------------------
 * Carbon plugin for GLPI
 *
 * @copyright Copyright (C) 2024-2025 Teclib' and contributors.
 * @copyright Copyright (C) 2024 by the carbon plugin team.
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

namespace GlpiPlugin\Carbon\Impact;

class Type
{
    const IMPACT_GWP    = 0; // Global warming potential
    const IMPACT_ADP    = 1; // Abiotic Depletion Potential
    const IMPACT_PE     = 3; // Primary Energy
    const IMPACT_GWPPB  = 4;
    const IMPACT_GWPPF  = 5;
    const IMPACT_GWPPLU = 6;
    const IMPACT_IR     = 7;
    const IMPACT_LU     = 8;
    const IMPACT_ODP    = 9;
    const IMPACT_PM     = 10;
    const IMPACT_POCP   = 11;
    const IMPACT_WU     = 12;
    const IMPACT_MIPS   = 13;
    const IMPACT_ADPE   = 14;
    const IMPACT_ADPF   = 15;
    const IMPACT_AP     = 16;
    const IMPACT_CTUE   = 17;
    // const IMPACT_CTUHC  = 18;
    const IMPACT_EPF    = 19;
    const IMPACT_EPM    = 20;
    const IMPACT_EPT    = 21;

    private static array $impact_types = [
        self::IMPACT_GWP    => 'gwp',
        self::IMPACT_ADP    => 'adp',
        self::IMPACT_PE     => 'pe',
        self::IMPACT_GWPPB  => 'gwppb',
        self::IMPACT_GWPPF  => 'gwppf',
        self::IMPACT_GWPPLU => 'gwpplu',
        self::IMPACT_IR     => 'ir',
        self::IMPACT_LU     => 'lu',
        self::IMPACT_ODP    => 'odp',
        self::IMPACT_PM     => 'pm',
        self::IMPACT_POCP   => 'pocp',
        self::IMPACT_WU     => 'wu',
        self::IMPACT_MIPS   => 'mips',
        self::IMPACT_ADPE   => 'adpe',
        self::IMPACT_ADPF   => 'adpf',
        self::IMPACT_AP     => 'ap',
        self::IMPACT_CTUE   => 'ctue',
        // self::IMPACT_CTUHC  => 'ctuh_c',
        self::IMPACT_EPF    => 'epf',
        self::IMPACT_EPM    => 'epm',
        self::IMPACT_EPT    => 'ept',
    ];

    public static function getImpactTypes(): array
    {
        return self::$impact_types;
    }

    /**
     * get the ID of an impact type acronym
     *
     * @param string $type
     * @return int|string|false
     */
    public static function getImpactId(string $type)
    {
        return array_search($type, self::$impact_types);
    }
}
