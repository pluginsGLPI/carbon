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

/** @var DBmysql $DB */
/** @var Migration $migration */

use Glpi\Dashboard\Item as DashboardItem;

$new_criterias = [
    'gwppb'   => '(unit g CO2 eq) Climate change - Contribution of biogenic emissions',
    'gwppf'   => '(unit g CO2 eq) Climate change - Contribution of fossil fuel emissions',
    'gwpplu'  => '(unit g CO2 eq) Climate change - Contribution of emissions from land use change',
    'ir'      => '(unit g U235 eq) Emissions of radionizing substances',
    'lu'      => '(unit none) Land use',
    'odp'     => '(unit g CFC-11 eq) Depletion of the ozone layer',
    'pm'      => '(unit Disease occurrence) Fine particle emissions',
    'pocp'    => '(unit g NMVOC eq) Photochemical ozone formation',
    'wu'      => '(unit L) Use of water resources',
    'mips'    => '(unit g) Material input per unit of service',
    'adpe'    => '(unit g SB eq) Use of mineral and metal resources',
    'adpf'    => '(unit J) Use of fossil resources (including nuclear)',
    'ap'      => '(unit mol H+ eq) Acidification',
    'ctue'    => '(unit CTUe) Freshwater ecotoxicity',
    // ctuh_c => '(unit CTUh) Human toxicity - non-carcinogenic effects',
    'epf'     => '(unit g P eq) Eutrophication of freshwater',
    'epm'     => '(unit g N eq) Eutrophication of marine waters',
    'ept'     => '(unit mol N eq) Terrestrial eutrophication',
];

$table = 'glpi_plugin_carbon_embodiedimpacts';
$previous_criteria = 'pe';
foreach ($new_criterias as $criteria => $comment) {
    $migration->addField(
        $table,
        $criteria,
        'float DEFAULT \'0\'',
        [
            'comment' => $comment,
            'after'   => $previous_criteria . '_quality',
        ]
    );
    $migration->addField(
        $table,
        $criteria . '_quality',
        'int unsigned NOT NULL DEFAULT \'0\'',
        [
            'comment' => 'DataTtacking\\AbstractTracked::DATA_QUALITY_* constants',
            'after'   => $criteria,
        ]
    );
    // $migration->dropField($table, $criteria);
    // $migration->dropField($table, $criteria . '_quality');
    $previous_criteria = $criteria;
}

// Uniformize existing impact : make floats signed
$old_criterias = [
    'gwp' => '(unit g CO2 eq) Global warming potential',
    'adp' => '(unit g Sb eq) Abiotic depletion potential',
    'pe'  => '(unit J) Primary energy',
];
foreach ($old_criterias as $criteria => $comment) {
    $migration->changeField($table, $criteria, $criteria, 'float DEFAULT \'0\'', [
        'comment' => $comment,
    ]);
}

// Rename cards for the report
$dashboard_item = new DashboardItem();
$rows = $dashboard_item->find([
    'card_id' => 'plugin_carbon_report_embodied_global_warming'
]);
foreach ($rows as $row) {
    $card_options = json_decode($row['card_options'], true);
    if ($card_options['widgettype'] === 'embodied_global_warming') {
        $card_options['widgettype'] = 'impact_criteria_number';
    }
    $dashboard_item->update([
        'id' => $row['id'],
        'card_id' => 'plugin_carbon_report_embodied_gwp_impact',
        'card_options' => json_encode($card_options),
    ]);
}

$dashboard_item = new DashboardItem();
$rows = $dashboard_item->find([
    'card_id' => 'plugin_carbon_report_usage_abiotic_depletion'
]);
foreach ($rows as $row) {
    $card_options = json_decode($row['card_options'], true);
    if ($card_options['widgettype'] === 'usage_abiotic_depletion') {
        $card_options['widgettype'] = 'impact_criteria_number';
    }
    $dashboard_item->update([
        'id' => $row['id'],
        'card_id' => 'plugin_carbon_report_usage_adp_impact',
        'card_options' => json_encode($card_options),
    ]);
}

$dashboard_item = new DashboardItem();
$rows = $dashboard_item->find([
    'card_id' => 'plugin_carbon_report_embodied_abiotic_depletion'
]);
foreach ($rows as $row) {
    $card_options = json_decode($row['card_options'], true);
    if ($card_options['widgettype'] === 'embodied_abiotic_depletion') {
        $card_options['widgettype'] = 'impact_criteria_number';
    }
    $dashboard_item->update([
        'id' => $row['id'],
        'card_id' => 'plugin_carbon_report_embodied_adp_impact',
        'card_options' => json_encode($card_options),
    ]);
}

$dashboard_item = new DashboardItem();
$rows = $dashboard_item->find([
    'card_id' => 'plugin_carbon_report_embodied_pe_impact'
]);
foreach ($rows as $row) {
    $card_options = json_decode($row['card_options'], true);
    if ($card_options['widgettype'] === 'embodied_primary_energy') {
        $card_options['widgettype'] = 'impact_criteria_number';
    }
    $dashboard_item->update([
        'id' => $row['id'],
        'card_id' => 'plugin_carbon_report_embodied_pe_impact',
        'card_options' => json_encode($card_options),
    ]);
}

// Rename cards for the standard dashboard : usage indicators

$dashboard_item = new DashboardItem();
$rows = $dashboard_item->find([
    'card_id' => 'plugin_carbon_total_usage_power'
]);
foreach ($rows as $row) {
    $dashboard_item->update([
        'id' => $row['id'],
        'card_id' => 'plugin_carbon_usage_pe_impact',
    ]);
}

$dashboard_item = new DashboardItem();
$rows = $dashboard_item->find([
    'card_id' => 'plugin_carbon_total_usage_carbon_emission'
]);
foreach ($rows as $row) {
    $dashboard_item->update([
        'id' => $row['id'],
        'card_id' => 'plugin_carbon_usage_gwp_impact',
    ]);
}

$dashboard_item = new DashboardItem();
$rows = $dashboard_item->find([
    'card_id' => 'plugin_carbon_total_usage_adp_impact'
]);
foreach ($rows as $row) {
    $dashboard_item->update([
        'id' => $row['id'],
        'card_id' => 'plugin_carbon_usage_adp_impact',
    ]);
}

// Rename cards for the standard dashboard : Embodied + usage indicators
$dashboard_item = new DashboardItem();
$rows = $dashboard_item->find([
    'card_id' => 'plugin_carbon_total_gwp_impact'
]);
foreach ($rows as $row) {
    $dashboard_item->update([
        'id' => $row['id'],
        'card_id' => 'plugin_carbon_all_scopes_gwp_impact',
    ]);
}

$dashboard_item = new DashboardItem();
$rows = $dashboard_item->find([
    'card_id' => 'plugin_carbon_total_adp_impact'
]);
foreach ($rows as $row) {
    $dashboard_item->update([
        'id' => $row['id'],
        'card_id' => 'plugin_carbon_all_scopes_adp_impact',
    ]);
}
