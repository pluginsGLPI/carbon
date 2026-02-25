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
    private const BASE_URL = 'https://glpi-plugins.readthedocs.io/%s/latest/carbon';

    const IMPACT_GWP    = 1; // Global warming potential
    const IMPACT_ADP    = 2; // Abiotic Depletion Potential
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
        'gwp'    => ['g', 'CO₂ eq'],
        'adp'    => ['g', 'SB eq'],
        'pe'     => ['J', ''],
        'gwppb'  => ['g', 'CO₂ eq'],
        'gwppf'  => ['g', 'CO₂ eq'],
        'gwpplu' => ['g', 'CO₂ eq'],
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
        'ctue'   => null,
        // 'ctuh_c' => [null, 'CTUh'],
        // 'ctuh_nc' => [null, 'CTUh'],
        'epf'    => ['g', 'P eq'],
        'epm'    => ['g', 'N eq'],
        'ept'    => ['mol', 'N eq'],
    ];

    /**
     * get an array of impact types
     *
     * @return array<int, string>
     */
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
     * @return array<string>
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
            'ctue'   => __('Embodied Freshwater ecotoxicity', 'carbon'),
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
            'ctue'   => __('Usage Freshwater ecotoxicity', 'carbon'),
            // 'ctuh_c' => __('Usage Human Toxicity - Carcinogenic Effects', 'carbon'),
            // 'ctuh_nc' => __('Usage Human toxicity - non-carcinogenic effects', 'carbon'),
            'epf'    => __('Usage Eutrophication of freshwater', 'carbon'),
            'epm'    => __('Usage Eutrophication of marine waters', 'carbon'),
            'ept'    => __('Usage Terrestrial eutrophication', 'carbon'),
            default  => ''
        };
        return $label;
    }

    /**
     * Get the unit of an impact type
     *
     * @param string $type impact type name
     * @return string
     **/
    public static function getEmbodiedAndUsageImpactLabel(string $type): string
    {
        $label = match ($type) {
            'gwp'    => __('Total Global warming potential', 'carbon'),
            'adp'    => __('Total Abiotic depletion potential', 'carbon'),
            'pe'     => __('Total Primary energy consumed', 'carbon'),
            'gwppb'  => __('Total Climate change - Contribution of biogenic emissions', 'carbon'),
            'gwppf'  => __('Total Climate change - Contribution of fossil fuel emissions', 'carbon'),
            'gwpplu' => __('Total Climate change - Contribution of emissions from land use change', 'carbon'),
            'ir'     => __('Total Emissions of radionizing substances', 'carbon'),
            'lu'     => __('Total Land use', 'carbon'),
            'odp'    => __('Total Depletion of the ozone layer', 'carbon'),
            'pm'     => __('Total Fine particle emissions', 'carbon'),
            'pocp'   => __('Total Photochemical ozone formation', 'carbon'),
            'wu'     => __('Total Use of water resources', 'carbon'),
            'mips'   => __('Total Material input per unit of service', 'carbon'),
            'adpe'   => __('Total Use of mineral and metal resources', 'carbon'),
            'adpf'   => __('Total Use of fossil resources (including nuclear)', 'carbon'),
            'ap'     => __('Total Acidification', 'carbon'),
            'ctue'   => __('Total Freshwater ecotoxicity', 'carbon'),
            // 'ctuh_c' => __('Total Human Toxicity - Carcinogenic Effects', 'carbon'),
            // 'ctuh_nc' => __('Total Human toxicity - non-carcinogenic effects', 'carbon'),
            'epf'    => __('Total Eutrophication of freshwater', 'carbon'),
            'epm'    => __('Total Eutrophication of marine waters', 'carbon'),
            'ept'    => __('Total Terrestrial eutrophication', 'carbon'),
            default  => '',
        };
        return $label;
    }

    public static function getCriteriaIcon(string $type): string
    {
        return match ($type) {
            // Global Warming Potential
            'gwp', 'gwppb', 'gwppf', 'gwpplu'
                => 'fa-solid fa-temperature-high',

            // Abiotic depletion (minerals / fossil)
            'adp', 'adpe'
                => 'fa-solid fa-gem',
            'adpf'
                => 'fa-solid fa-oil-can',

            // Primary energy
            'pe'
                => 'fa-solid fa-bolt',

            // Ionising radiation
            'ir'
                => 'fa-solid fa-radiation',

            // Land use
            'lu'
                => 'fa-solid fa-tree',

            // Ozone depletion
            'odp'
                => 'fa-solid fa-cloud',

            // Particulate matter
            'pm'
                => 'fa-solid fa-smog',

            // Photochemical ozone creation
            'pocp'
                => 'fa-solid fa-sun',

            // Water use
            'wu'
                => 'fa-solid fa-droplet',

            // Material input per service unit
            'mips'
                => 'fa-solid fa-boxes-stacked',

            // Acidification
            'ap'
                => 'fa-solid fa-flask',

            // Ecotoxicity (freshwater, marine, terrestrial)
            'epf', 'epm', 'ept'
                => 'fa-solid fa-fish',

            // Human toxicity / ecotoxicity
            'ctue', 'ctuh_c', 'ctuh_nc'
                => 'fa-solid fa-skull-crossbones',

            default
                => '', // or 'fa-solid fa-circle-question',
        };
    }

    public static function getCriteriaTooltip(string $type): string
    {
        return match ($type) {
            'gwp'    => __('Carbon emission in CO₂ equivalent', 'carbon'),
            'adp'    => __('Consumption of non renewable resources in Antimony equivalent.', 'carbon'),
            'pe'     => __('Primary energy consumed.', 'carbon'),
            'gwppb'  => __('', 'carbon'),
            'gwppf'  => __('', 'carbon'),
            'gwpplu' => __('', 'carbon'),
            'ir'     => __('', 'carbon'),
            'lu'     => __('', 'carbon'),
            'odp'    => __('', 'carbon'),
            'pm'     => __('', 'carbon'),
            'pocp'   => __('', 'carbon'),
            'wu'     => __('', 'carbon'),
            'mips'   => __('', 'carbon'),
            'adpe'   => __('', 'carbon'),
            'adpf'   => __('', 'carbon'),
            'ap'     => __('', 'carbon'),
            'ctue'   => __('', 'carbon'),
            // 'ctuh_c' => __('', 'carbon'),
            // 'ctuh_nc' => __('', 'carbon'),
            'epf'    => __('Usage Eutrophication of freshwater', 'carbon'),
            'epm'    => __('Usage Eutrophication of marine waters', 'carbon'),
            'ept'    => __('Usage Terrestrial eutrophication', 'carbon'),
            default  => ''
        };
    }

    public static function getCriteriaPictogram(string $type): string
    {
        $pictogram_file = match ($type) {
            'gwp'    => 'icon-carbon-emission.svg',
            'adp'    => 'icon-fossil-primary-energy.svg',
            'pe'     => 'icon-pickaxe.svg',
            'gwppb'  => '',
            'gwppf'  => '',
            'gwpplu' => '',
            'ir'     => '',
            'lu'     => '',
            'odp'    => '',
            'pm'     => '',
            'pocp'   => '',
            'wu'     => '',
            'mips'   => '',
            'adpe'   => '',
            'adpf'   => '',
            'ap'     => '',
            'ctue'   => '',
            // 'ctuh_c' => '',
            // 'ctuh_nc' => '',
            'epf'    => '',
            'epm'    => '',
            'ept'    => '',
            default  => ''
        };
        return $pictogram_file;
    }

    /**
     * Get external URL to a detailed description of the given path
     *
     * @param string $impact_type
     * @return string
     */
    public static function getCriteriaInfoLink(string $impact_type): string
    {
        // $lang = substr($_SESSION['glpilanguage'], 0, 2);
        $lang = 'en';
        $base_url = sprintf(
            self::BASE_URL,
            $lang
        );
        switch ($impact_type) {
            case 'gwp':
                return "$base_url/carbon/types_of_impact.html#carbon-dioxyde-equivalent";
            case 'adp':
                return "$base_url/types_of_impact.html#antimony-equivalent";
            case 'pe':
                return "$base_url/carbon/types_of_impact.html#primary-energy";
        }

        return '';
    }
}
