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

namespace GlpiPlugin\Carbon\Impact\Embodied\Internal;

use GlpiPlugin\Carbon\DataTracking\TrackedFloat;
use GlpiPlugin\Carbon\Impact\Type;
use GlpiPlugin\Carbon\MonitorModel;
use Monitor as GlpiMonitor;
use MonitorModel as GlpiMonitorModel;

/**
 * This embodied impact
 */
class Monitor extends AbstractAsset
{
    protected static string $itemtype = GlpiMonitor::class;

    protected function doEvaluation(): ?array
    {
        if (GlpiMonitorModel::isNewID($this->item->fields['monitormodels_id'])) {
            return [];
        }

        $model = new MonitorModel();
        $model->getFromDBByCrit([
            'monitormodels_id' => $this->item->fields['monitormodels_id'],
        ]);
        if ($model->isNewItem()) {
            return [];
        }

        $impacts = [];
        $types = Type::getImpactTypes();
        foreach ($types as $type) {
            switch ($type) {
                case 'gwp':
                    if (!empty($model->fields['gwp'])) {
                        $impacts[Type::IMPACT_GWP] = new TrackedFloat(
                            $model->fields['gwp'] * 1000, // UI Field in KgCO2Eq
                            null,
                            $model->fields['gwp_quality']
                        );
                    }
                    break;

                case 'adp':
                    if (!empty($model->fields['adp'])) {
                        $impacts[Type::IMPACT_ADP] = new TrackedFloat(
                            $model->fields['adp'],
                            null,
                            $model->fields['adp_quality']
                        );
                    }
                    break;

                case 'pe':
                    if (!empty($model->fields['pe'])) {
                        $impacts[Type::IMPACT_PE] = new TrackedFloat(
                            $model->fields['pe'],
                            null,
                            $model->fields['pe_quality']
                        );
                    }
                    break;
            }
        }

        return $impacts;
    }
}
