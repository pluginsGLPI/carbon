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

/**
 * Initialise the dashboard cards from data attributes.
 * Loaded right after the card markup (full page or AJAX), so it may run once per card:
 * elements already initialised are skipped.
 */
(function () {
    'use strict';

    let fit_text_observer = null;

    const init_charts = () => {
        document.querySelectorAll('[data-carbon-chart]:not([data-carbon-chart-ready])').forEach((element) => {
            element.dataset.carbonChartReady = '';
            const card_id = element.dataset.carbonHighlightCard;
            if (card_id && window.location.href.includes('dashboard')) {
                const card = document.getElementById(card_id);
                if (card) {
                    card.classList.add('border', 'border-2');
                }
            }
            new ApexCharts(element, JSON.parse(element.dataset.carbonChart)).render();
        });
    };

    const init_fit_text = () => {
        document.querySelectorAll('[data-carbon-fit-text]:not([data-carbon-fit-text-ready])').forEach((element) => {
            element.dataset.carbonFitTextReady = '';
            if (fit_text_observer === null) {
                fit_text_observer = new ResizeObserver((entries) => {
                    for (const entry of entries) {
                        $(entry.target).fitText(0.7);
                    }
                });
            }
            fit_text_observer.observe(element);
        });
    };

    $(() => {
        init_charts();
        init_fit_text();
    });
})();
