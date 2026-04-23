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

namespace GlpiPlugin\Carbon\DataSource\CarbonIntensity;

use CommonDBTM;
use CronTask as GlpiCronTask;
use Glpi\Application\View\TemplateRenderer;
use GlpiPlugin\Carbon\CarbonIntensity;
use GlpiPlugin\Carbon\CronTask;
use GlpiPlugin\Carbon\DataSource\AbstractCronTask as DatasourceAbstractCronTask;
use GlpiPlugin\Carbon\DataSource\CronTaskInterface;
use GlpiPlugin\Carbon\Source_Zone;
use GlpiPlugin\Carbon\Toolbox;
use GlpiPlugin\Carbon\Zone;
use RuntimeException;

abstract class AbstractCronTask extends DatasourceAbstractCronTask implements CronTaskInterface
{
    protected static string $client_name;

    protected static string $downloadMethod;

    /**
     * Filter out the gaps to remove fales gaps caused by DST switch
     * Needed after a call to Toolbox::findTemporalGapsInTable()
     * TODO: replace its inner implementation with this method on each call, when necessary
     * In the SQL function DATE_ADD() we may do the following process
     * DATE_ADD('2022-03-27 01:00:00', INTERVAL 1 HOUR) and '2022-03-27 01:00:00' + INTERVAL 1 HOUR
     * while we use Europe/Paris timezone (or any timezone usinf DST)
     * Both expressions return '2022-03-27 02:00:00' and it matches the exact time where we switch to summer time
     * '2022-03-27 02:00:00' should be actually '2022-03-27 03:00:00', but this is not what happens with MySQL 8.0
     * Therefore when the date '2022-03-27 02:00:00' is converted into a DateTime object in PHP with Europe/Paris timezone
     * it is converted into '2022-03-27 03:00:00'.
     * When the start of a gap and the end of a gap, both converted into a DateTime object, are equal
     * then this means that we are switching to summer time and the gap is irrelevant
     * The code below tracks such intervals and filters them out
     *
     * @param array $gaps
     * @param Source_Zone $source_zone
     * @return array
     */
    abstract protected function dstFilter(array $gaps, Source_Zone $source_zone): array;

    public function showForCronTask(CommonDBTM $item)
    {
        switch ($item->fields['name']) {
            case static::$downloadMethod:
                $this->showGapsReport();
        }
    }

    public function showGapsReport()
    {
        $renderer = TemplateRenderer::getInstance();
        $template = <<<TWIG
        {% import "components/form/fields_macros.html.twig" as fields %}
        {{ fields.largeTitle(__('Gaps in carbon intensity time series', 'carbon')) }}
        <div>{{ __('Only zones with download enabled are displayed.', 'carbon') }}</div>
        <div>&nbsp;</div>
TWIG;
        echo $renderer->renderFromStringTemplate($template);
        $oldest_asset_date = (new Toolbox())->getOldestAssetDate();
        $client = ClientFactory::create(static::$client_name);
        $source_name = $client->getSourceName();
        foreach ($client->getSupportedZones() as $zone_name) {
            $source_zone = new Source_Zone();
            if (!$source_zone->getFromDbBySourceAndZone($source_name, $zone_name)) {
                continue;
            }
            if (!$source_zone->fields['is_download_enabled']) {
                continue;
            }
            $zone_id = $source_zone->fields['plugin_carbon_zones_id'];
            $carbon_intensity = new CarbonIntensity();
            $entries = $carbon_intensity->findGaps(
                $source_zone,
                $oldest_asset_date
            );
            $entries = $this->dstFilter($entries, $source_zone);
            $total = count($entries);
            $zone = Zone::getById($zone_id);

            $template = <<<TWIG
            {% import "components/form/fields_macros.html.twig" as fields %}
            {{ fields.smallTitle(__('Gaps for the zone %s', 'carbon')|format(zone_name)) }}
TWIG;
            echo $renderer->renderFromStringTemplate($template, ['zone_name' => $zone->fields['name']]);
            $renderer->display('components/datatable.html.twig', [
                'is_tab' => true,
                'nopager' => true,
                'nofilter' => true,
                'nosort' => true,
                'columns' => [
                    'start' => __('Start'),
                    'end' => __('End', 'carbon'),
                ],
                'footers' => [
                    ['', '', '', __('Total'), $total, ''],
                ],
                'footer_class' => 'fw-bold',
                'entries' => $entries,
                'total_number' => $total,
                'filtered_number' => $total,
                'showmassiveactions' => false,
                'massiveactionparams' => [
                    'num_displayed' => $total,
                    'container'     => 'mass' . static::class . mt_rand(),
                ],
            ]);
        }
    }

    /**
     * Uniformize download method of carbon intensity clients
     * The design of \CronTask requires that each cron task to use a unique method name, across itemtypes
     *
     * @param string $name
     * @param array $arguments
     * @return int
     */
    public static function __callStatic($name, $arguments)
    {
        if ($name === 'cron' . static::$downloadMethod) {
            return self::cronDownload(...$arguments);
        }
        throw new RuntimeException('Not implemented');
    }

    /**
     * Automatic action to download carbon intensity from a data source
     *
     * @return int
     */
    public static function cronDownload(GlpiCronTask $task): int
    {
        $client = ClientFactory::create(static::$client_name);
        return CronTask::downloadCarbonIntensityFromSource($task, $client, new CarbonIntensity());
    }
}
