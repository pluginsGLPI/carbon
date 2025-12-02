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

namespace GlpiPlugin\Carbon\Impact\Engine\Boavizta\Tests;

use Monitor as GlpiMonitor;
use MonitorType as GlpiMonitorType;
use MonitorModel as glpiMonitorModel;
use DBmysql;
use GlpiPlugin\Carbon\Impact\Embodied\AbstractEmbodiedImpact;
use GlpiPlugin\Carbon\Impact\Embodied\Boavizta\Monitor as BoaviztaMonitor;
use GlpiPlugin\Carbon\Tests\Impact\Engine\AbstractEmbodiedImpactTest;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(BoaviztaMonitor::class)]
class MonitorTest extends AbstractEmbodiedImpactTest
{
    protected static string $itemtype = GlpiMonitor::class;
    protected static string $itemtype_type = GlpiMonitorType::class;
    protected static string $itemtype_model = GlpiMonitorModel::class;
}
