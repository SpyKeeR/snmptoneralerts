<?php

/**
 * -------------------------------------------------------------------------
 * SNMP Toner Alerts plugin for GLPI
 * -------------------------------------------------------------------------
 *
 * LICENSE
 *
 * This file is part of SNMP Toner Alerts.
 *
 * SNMP Toner Alerts is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * SNMP Toner Alerts is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with SNMP Toner Alerts. If not, see <http://www.gnu.org/licenses/>.
 * -------------------------------------------------------------------------
 * @copyright Copyright (C) 2025 by SpyKeeR.
 * @license   GPLv3 https://www.gnu.org/licenses/gpl-3.0.html
 * @link      https://github.com/SpyKeeR/snmptoneralerts
 * -------------------------------------------------------------------------
 */

use Glpi\Plugin\Hooks;
use GlpiPlugin\Snmptoneralerts\Config;
use GlpiPlugin\Snmptoneralerts\TonerMonitor;
use GlpiPlugin\Snmptoneralerts\NotificationTargetTonerAlert;

define('PLUGIN_SNMPTONERALERTS_VERSION', '1.0.3');

// Minimal GLPI version, inclusive
define('PLUGIN_SNMPTONERALERTS_MIN_GLPI', '11.0.0');
// Maximum GLPI version, exclusive
define('PLUGIN_SNMPTONERALERTS_MAX_GLPI', '11.0.99');

/**
 * Init hooks of the plugin.
 * REQUIRED
 *
 * @return void
 */
function plugin_init_snmptoneralerts()
{
    global $PLUGIN_HOOKS;

    $PLUGIN_HOOKS['csrf_compliant']['snmptoneralerts'] = true;

    // Register Config class to add tab in GLPI Config
    Plugin::registerClass(Config::class, ['addtabon' => 'Config']);

    // Register notification target
    Plugin::registerClass(
        NotificationTargetTonerAlert::class,
        ['notificationtemplates_types' => true]
    );

    // Config page
    if (Session::haveRight('config', UPDATE)) {
        $PLUGIN_HOOKS['config_page']['snmptoneralerts'] = 'front/config.php';
    }

    // Change profile hook
    $PLUGIN_HOOKS['change_profile']['snmptoneralerts'] = 'plugin_change_profile_snmptoneralerts';
}

/**
 * Get the name and the version of the plugin
 * REQUIRED
 *
 * @return array
 */
function plugin_version_snmptoneralerts()
{
    return [
        'name'         => __('SNMP Toner Alerts', 'snmptoneralerts'),
        'version'      => PLUGIN_SNMPTONERALERTS_VERSION,
        'author'       => 'SpyKeeR',
        'license'      => 'GPLv3+',
        'homepage'     => 'https://github.com/SpyKeeR/snmptoneralerts',
        'requirements' => [
            'glpi' => [
                'min' => PLUGIN_SNMPTONERALERTS_MIN_GLPI,
                'max' => PLUGIN_SNMPTONERALERTS_MAX_GLPI,
            ],
        ],
    ];
}

/**
 * Check pre-requisites before install
 * OPTIONAL, but recommended
 *
 * @return boolean
 */
function plugin_snmptoneralerts_check_prerequisites()
{
    // Check PHP version
    if (version_compare(PHP_VERSION, '8.2.0', '<')) {
        echo "Ce plugin nécessite PHP 8.2 ou supérieur.";
        return false;
    }

    return true;
}

/**
 * Check configuration process
 *
 * @param boolean $verbose Whether to display message on failure. Defaults to false
 *
 * @return boolean
 */
function plugin_snmptoneralerts_check_config($verbose = false)
{
    if (true) {
        return true;
    }

    if ($verbose) {
        echo __('Installed / not configured', 'snmptoneralerts');
    }
    return false;
}
