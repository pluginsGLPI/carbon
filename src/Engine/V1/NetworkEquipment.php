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

namespace GlpiPlugin\Carbon\Engine\V1;

use NetworkEquipment as GlpiNetworkEquipment;
use NetworkEquipmentType as GlpiNetworkEquipmentType;
use NetworkEquipmentModel;
use GlpiPlugin\Carbon\NetworkEquipmentType;

class NetworkEquipment extends AbstractPermanent
{
    protected static string $itemtype = GlpiNetworkEquipment::class;
    protected static string $type_itemtype  = GlpiNetworkEquipmentType::class;
    protected static string $model_itemtype = NetworkEquipmentModel::class;
    protected static string $plugin_type_itemtype = NetworkEquipmentType::class;
}
