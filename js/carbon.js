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

window.GLPIPlugin = window.GLPIPlugin || {};

window.GLPIPlugin.Carbon = {
    onSourceChange: function() {
        var value = document.querySelector("select[name='plugin_carbon_sources_id']").value;
        var zoneDropdown = document.querySelector("select[name='plugin_carbon_zones_id']").closest('div.form-field');
        if (value == 0 && !zoneDropdown.classList.contains('d-none')) {
            zoneDropdown.classList.add('d-none');
        } else if (value != 0 && zoneDropdown.classList.contains('d-none')) {
            zoneDropdown.classList.remove('d-none');
        }
    }
};

document.addEventListener('click', (e) => {
    const element = e.target.closest('[data-carbon-confirm]');
    if (element && !window.confirm(element.dataset.carbonConfirm)) {
        e.preventDefault();
        e.stopImmediatePropagation();
    }
}, true);

document.addEventListener('click', (e) => {
    const element = e.target.closest('[data-carbon-submit-get-link]');
    if (element === null || e.defaultPrevented) {
        return;
    }
    e.preventDefault();
    submitGetLink(
        element.dataset.carbonSubmitGetLink,
        JSON.parse(element.dataset.carbonSubmitGetLinkFields ?? '{}')
    );
});

$(document).on('change', 'select[name="plugin_carbon_sources_id"]', function () {
    const zone_dom_id = $('select[name="plugin_carbon_zones_id"]').prop('id').replace(/\D+/g, '');
    $('select[name="plugin_carbon_zones_id"]').closest('.btn-group').load(
        CFG_GLPI.root_doc + '/plugins/carbon/ajax/dropdownZone.php', {
            plugin_carbon_sources_id: document.querySelector('select[name="plugin_carbon_sources_id"]').value,
            dom_id: zone_dom_id
        }
    );
});

$(document).on('click', '[data-carbon-toggle-zone]', function (event) {
    event.preventDefault();
    const csrf_token = this.dataset.carbonCsrfToken;
    const form_data = new FormData();
    form_data.append('_glpi_csrf_token', csrf_token);
    form_data.append('id', this.dataset.carbonToggleZone);
    fetch(CFG_GLPI.root_doc + '/plugins/carbon/ajax/toggleZoneDownload.php', {
        method: 'POST',
        headers: {
            'X-Glpi-Csrf-Token': csrf_token
        },
        body: form_data,
    }).then(() => {
        reloadTab();
    });
});
