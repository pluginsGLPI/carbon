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

use Glpi\Dashboard\Item as DashboardItem;
use function Safe\json_decode;
use function Safe\json_encode;

/** @var DBmysql $DB */
/** @var Migration $migration */

$dashboard_item = new DashboardItem();
$rows = $dashboard_item->find([
    'card_id' => 'plugin_carbon_assets_completeness_ratio',
]);

foreach ($rows as $row) {
    $card_options = json_decode($row['card_options'], true);
    if (!is_array($card_options) || ($card_options['widgettype'] ?? null) !== 'apex_radar') {
        continue;
    }

    $card_options['widgettype'] = 'radar';
    $dashboard_item->update([
        'id' => $row['id'],
        'card_options' => json_encode($card_options),
    ]);
}
