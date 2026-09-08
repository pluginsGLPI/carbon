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
use Config as GlpiConfig;
use Glpi\Exception\Http\AccessDeniedHttpException;
use Glpi\Exception\Http\BadRequestHttpException;
use Glpi\Exception\Http\NotFoundHttpException;
use GlpiPlugin\Carbon\Source;
use GlpiPlugin\Carbon\Source_Zone;

include(__DIR__ . '/../../../inc/includes.php');

// Check if plugin is activated...
if (!Plugin::isPluginActive('carbon')) {
    throw new NotFoundHttpException();
} elseif (!Source::canView() || !GlpiConfig::canUpdate()) {
    throw new AccessDeniedHttpException();
} elseif (!isset($_GET['id'])) {
    throw new BadRequestHttpException();
} else {
    $source_zone = new Source_Zone();
    if (!$source_zone->getFromDB($_GET['id'])) {
        throw new BadRequestHttpException();
    } elseif (!$source_zone->toggleZone()) {
        throw new BadRequestHttpException();
    }
}
