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
    // const IMPACT_CTUHNC  = 19;
    const IMPACT_EPF    = 20;
    const IMPACT_EPM    = 21;
    const IMPACT_EPT    = 22;

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
        // self::IMPACT_CTUHNC => 'ctuh_nc',
        self::IMPACT_EPF    => 'epf',
        self::IMPACT_EPM    => 'epm',
        self::IMPACT_EPT    => 'ept',
    ];

    /**
     * Unit of impact criterias
     *
     * @var array
     */
    private static array $impact_units = [
        'gwp'    => ['g', 'CO2 eq'],
        'adp'    => ['g', 'SB eq'],
        'pe'     => ['J', ''],
        'gwppb'  => ['g', 'CO2 eq'],
        'gwppf'  => ['g', 'CO2 eq'],
        'gwpplu' => ['g', 'CO2 eq'],
        'ir'     => ['g', 'U235 eq'],
        'lu'     => null,
        'odp'    => ['g', 'CFC-11 eq'],
        'pm'     => null,
        'pocp'   => ['g', 'U235 eq'],
        'wu'     => ['m³', ''],
        'mips'   => ['g', ''],
        'adpe'   => ['g', 'SB eq'],
        'adpf'   => ['J', ''],
        'ap'     => ['mol', 'H+ eq'],
        'ctue'   => ['J', ''],
        // 'ctuh_c' => [null, 'CTUh'],
        // 'ctuh_nc' => [null, 'CTUh'],
        'epf'    => ['g', 'P eq'],
        'epm'    => ['g', 'N eq'],
        'ept'    => ['mol', 'N eq'],
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
     **/
    public static function getImpactId(string $type)
    {
        return array_search($type, self::$impact_types);
    }

    /**
     * Get the unit of an impact type
     *
     * @param string $type impact type name
     * @return array
     **/
    public static function getImpactUnit(string $type): array
    {
        return self::$impact_units[$type] ?? ['', ''];
    }

    /**
     * Get the unit of an impact type
     *
     * @param string $type impact type name
     * @return string
     **/
    public static function getEmbodiedImpactLabel(string $type): string
    {
        $label = match ($type) {
            'gwp'    => __('Embodied Global warming potential', 'carbon'),
            'adp'    => __('Embodied Abiotic depletion potential', 'carbon'),
            'pe'     => __('Embodied Primary energy consumed', 'carbon'),
            'gwppb'  => __('Embodied Climate change - Contribution of biogenic emissions', 'carbon'),
            'gwppf'  => __('Embodied Climate change - Contribution of fossil fuel emissions', 'carbon'),
            'gwpplu' => __('Embodied Climate change - Contribution of emissions from land use change', 'carbon'),
            'ir'     => __('Embodied Emissions of radionizing substances', 'carbon'),
            'lu'     => __('Embodied Land use', 'carbon'),
            'odp'    => __('Embodied Depletion of the ozone layer', 'carbon'),
            'pm'     => __('Embodied Fine particle emissions', 'carbon'),
            'pocp'   => __('Embodied Photochemical ozone formation', 'carbon'),
            'wu'     => __('Embodied Use of water resources', 'carbon'),
            'mips'   => __('Embodied Material input per unit of service', 'carbon'),
            'adpe'   => __('Embodied Use of mineral and metal resources', 'carbon'),
            'adpf'   => __('Embodied Use of fossil resources (including nuclear)', 'carbon'),
            'ap'     => __('Embodied Acidification', 'carbon'),
            'ctue'   => __('Embodied Human Toxicity - Carcinogenic Effects', 'carbon'),
            // 'ctuh_c' => __('Embodied Human Toxicity - Carcinogenic Effects', 'carbon'),
            // 'ctuh_nc' => __('Embodied Human toxicity - non-carcinogenic effects', 'carbon'),
            'epf'    => __('Embodied Eutrophication of freshwater', 'carbon'),
            'epm'    => __('Embodied Eutrophication of marine waters', 'carbon'),
            'ept'    => __('Embodied Terrestrial eutrophication', 'carbon'),
            default  => '',
        };
        return $label;
    }

    /**
     * Get the unit of an impact type
     *
     * @param string $type impact type name
     * @return string
     **/
    public static function getUsageImpactLabel(string $type): string
    {
        $label = match ($type) {
            'gwp'    => __('Usage Global warming potential', 'carbon'),
            'adp'    => __('Usage Abiotic depletion potential', 'carbon'),
            'pe'     => __('Usage Primary energy consumed', 'carbon'),
            'gwppb'  => __('Usage Climate change - Contribution of biogenic emissions', 'carbon'),
            'gwppf'  => __('Usage Climate change - Contribution of fossil fuel emissions', 'carbon'),
            'gwpplu' => __('Usage Climate change - Contribution of emissions from land use change', 'carbon'),
            'ir'     => __('Usage Emissions of radionizing substances', 'carbon'),
            'lu'     => __('Usage Land use', 'carbon'),
            'odp'    => __('Usage Depletion of the ozone layer', 'carbon'),
            'pm'     => __('Usage Fine particle emissions', 'carbon'),
            'pocp'   => __('Usage Photochemical ozone formation', 'carbon'),
            'wu'     => __('Usage Use of water resources', 'carbon'),
            'mips'   => __('Usage Material input per unit of service', 'carbon'),
            'adpe'   => __('Usage Use of mineral and metal resources', 'carbon'),
            'adpf'   => __('Usage Use of fossil resources (including nuclear)', 'carbon'),
            'ap'     => __('Usage Acidification', 'carbon'),
            'ctue'   => __('Usage Human Toxicity - Carcinogenic Effects', 'carbon'),
            // 'ctuh_c' => __('Usage Human Toxicity - Carcinogenic Effects', 'carbon'),
            // 'ctuh_nc' => __('Usage Human toxicity - non-carcinogenic effects', 'carbon'),
            'epf'    => __('Usage Eutrophication of freshwater', 'carbon'),
            'epm'    => __('Usage Eutrophication of marine waters', 'carbon'),
            'ept'    => __('Usage Terrestrial eutrophication', 'carbon'),
            default  => ''
        };
        return $label;
    }
}
