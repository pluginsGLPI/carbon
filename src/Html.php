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

namespace GlpiPlugin\Carbon;

use Locale;
use Html as GlpiHtml;

class Html
{
    /**
     * Display TimeField form
     *
     * @param string $name
     * @param array  $options
     *   - value      : default value to display (default '')
     *   - timestep   : step for time in minute (-1 use default config) (default -1)
     *   - maybeempty : may be empty ? (true by default)
     *   - canedit    : could not modify element (true by default)
     *   - mintime    : minimum allowed time (default '')
     *   - maxtime    : maximum allowed time (default '')
     *   - display    : boolean display or get string (default true)
     *   - rand       : specific random value (default generated one)
     *   - required   : required field (will add required attribute)
     *   - on_change  : function to execute when date selection changed
     * @return integer|string
     */
    public static function showTimeField(string $name, array $options = [])
    {
        /** @var array $CFG_GLPI */
        global $CFG_GLPI;

        $p = [
            'value'      => '',
            'maybeempty' => true,
            'canedit'    => true,
            'mintime'    => '',
            'maxtime'    => '',
            'timestep'   => -1,
            'display'    => true,
            'rand'       => mt_rand(),
            'required'   => false,
            'on_change'  => '',
        ];

        foreach ($options as $key => $val) {
            if (isset($p[$key])) {
                $p[$key] = $val;
            }
        }

        if ($p['timestep'] < 0) {
            $p['timestep'] = $CFG_GLPI['time_step'];
        }

        $hour_value = '';
        if (!empty($p['value'])) {
            $hour_value = $p['value'];
        }

        if (!empty($p['mintime'])) {
            // Check time in interval
            if (!empty($hour_value) && ($hour_value < $p['mintime'])) {
                $hour_value = $p['mintime'];
            }
        }
        if (!empty($p['maxtime'])) {
            // Check time in interval
            if (!empty($hour_value) && ($hour_value > $p['maxtime'])) {
                $hour_value = $p['maxtime'];
            }
        }
        // reconstruct value to be valid
        if (!empty($hour_value)) {
            $p['value'] = $hour_value;
        }

        $required = $p['required'] == true
         ? " required='required'"
         : "";
        $disabled = !$p['canedit']
         ? " disabled='disabled'"
         : "";
        $clear    = $p['maybeempty'] && $p['canedit']
         ? "<a data-clear  title='" . __s('Clear') . "'>
               <i class='input-group-text fas fa-times-circle pointer'></i>
            </a>"
         : "";

        $output = <<<HTML
         <div class="input-group flex-grow-1 flatpickr" id="showtime{$p['rand']}">
            <input type="text" name="{$name}" value="{$p['value']}"
                   {$required} {$disabled} data-input class="form-control rounded-start ps-2">
            <a class="input-button" data-toggle>
               <i class="input-group-text far fa-clock fa-lg pointer"></i>
            </a>
            $clear
         </div>
HTML;
        $timestep = (int) $p['timestep'];

        $locale = Locale::parseLocale($_SESSION['glpilanguage']);
        $locale_language = jsescape($locale['language']);
        $locale_region   = jsescape($locale['region']);
        $js = <<<JS
      $(function() {
         $("#showtime{$p['rand']}").flatpickr({
            dateFormat: 'H:i:S',
            wrap: true, // permits to have controls in addition to input (like clear or open date buttons)
            enableTime: true,
            noCalendar: true, // only time picker
            enableSeconds: true,
            time_24hr: true,
            locale: getFlatPickerLocale("{$locale_language}", "{$locale_region}"),
            minuteIncrement: $timestep,
            onChange: function(selectedDates, dateStr, instance) {
               {$p['on_change']}
            },
            allowInput: true,
            onClose(dates, currentdatestring, picker){
               picker.setDate(picker.altInput.value, true, picker.config.altFormat)
            },
            plugins: [
               CustomFlatpickrButtons()
            ]
         });
      });
JS;
        $output .= GlpiHtml::scriptBlock($js);

        if ($p['display']) {
            echo $output;
            return $p['rand'];
        }
        return $output;
    }
}
