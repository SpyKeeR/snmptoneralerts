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

namespace GlpiPlugin\Snmptoneralerts;

use CommonDBTM;
use CommonGLPI;
use Config as GlpiConfig;
use Dropdown;
use Html;
use Session;
use Toolbox;

/**
 * Configuration class for SNMP Toner Alerts plugin
 */
class Config extends CommonDBTM
{
    protected static $notable = true;

    public function getTabNameForItem(CommonGLPI $item, $withtemplate = 0)
    {
        if (!$withtemplate && $item->getType() == 'Config') {
            return __('SNMP Toner Alerts', 'snmptoneralerts');
        }

        return '';
    }

    public static function configUpdate($input)
    {
        return $input;
    }

    /**
     * Get default configuration values
     *
     * @return array
     */
    public static function getDefaults()
    {
        return [
            'threshold_percentage'     => 20,
            'email_recipients'         => '',
            'check_frequency_hours'    => 6,
            'alert_time_daily'         => '08:00:00',
            'alert_day_weekly'         => 5, // Friday
            'alert_time_weekly'        => '12:00:00',
            'max_daily_alerts'         => 3,
            'enable_daily_alerts'      => 1,
            'enable_weekly_alerts'     => 1,
        ];
    }

    /**
     * Get current configuration
     *
     * @return array
     */
    public static function getConfig()
    {
        $config = GlpiConfig::getConfigurationValues('plugin:Snmptoneralerts');
        $defaults = self::getDefaults();
        
        return array_merge($defaults, $config);
    }

    /**
     * Display configuration form
     */
    public function showFormConfig()
    {
        global $CFG_GLPI;

        if (!Session::haveRight('config', UPDATE)) {
            return false;
        }

        $config = self::getConfig();

        echo "<form name='form' action=\"" . Toolbox::getItemTypeFormURL('Config') . "\" method='post'>";
        echo "<div class='center' id='tabsbody'>";
        echo "<table class='tab_cadre_fixe'>";
        
        echo "<tr><th colspan='2'>" . __('SNMP Toner Alerts Configuration', 'snmptoneralerts') . '</th></tr>';

        echo "<input type='hidden' name='config_class' value='" . self::class . "'>";
        echo "<input type='hidden' name='config_context' value='plugin:Snmptoneralerts'>";

        // Threshold percentage
        echo "<tr class='tab_bg_1'>";
        echo "<td>" . __('Threshold percentage (%)', 'snmptoneralerts') . '</td>';
        echo "<td>";
        echo "<input type='number' name='threshold_percentage' value='" . $config['threshold_percentage'] . "' min='1' max='100' style='width: 100px;'>";
        echo " " . __('Alert when toner level is below this percentage', 'snmptoneralerts');
        echo '</td></tr>';

        // Email recipients
        echo "<tr class='tab_bg_1'>";
        echo "<td>" . __('Email recipients', 'snmptoneralerts') . '</td>';
        echo "<td>";
        echo "<input type='text' name='email_recipients' value='" . $config['email_recipients'] . "' style='width: 500px;'>";
        echo "<br><small>" . __('Separate multiple emails with commas', 'snmptoneralerts') . "</small>";
        echo '</td></tr>';

        // Check frequency
        echo "<tr class='tab_bg_1'>";
        echo "<td>" . __('Check frequency (hours)', 'snmptoneralerts') . '</td>';
        echo "<td>";
        echo "<input type='number' name='check_frequency_hours' value='" . $config['check_frequency_hours'] . "' min='1' max='24' style='width: 100px;'>";
        echo " " . __('How often to check toner levels', 'snmptoneralerts');
        echo '</td></tr>';

        // Max daily alerts
        echo "<tr class='tab_bg_1'>";
        echo "<td>" . __('Maximum daily alerts per toner', 'snmptoneralerts') . '</td>';
        echo "<td>";
        echo "<input type='number' name='max_daily_alerts' value='" . $config['max_daily_alerts'] . "' min='1' max='10' style='width: 100px;'>";
        echo " " . __('After this number, switch to weekly recap', 'snmptoneralerts');
        echo '</td></tr>';

        // Enable daily alerts
        echo "<tr class='tab_bg_1'>";
        echo "<td>" . __('Enable daily alerts', 'snmptoneralerts') . '</td>';
        echo "<td>";
        Dropdown::showYesNo('enable_daily_alerts', $config['enable_daily_alerts']);
        echo '</td></tr>';

        // Daily alert time
        echo "<tr class='tab_bg_1'>";
        echo "<td>" . __('Daily alert time', 'snmptoneralerts') . '</td>';
        echo "<td>";
        echo "<input type='time' name='alert_time_daily' value='" . substr($config['alert_time_daily'], 0, 5) . "' style='width: 120px;'>";
        echo '</td></tr>';

        // Enable weekly alerts
        echo "<tr class='tab_bg_1'>";
        echo "<td>" . __('Enable weekly alerts', 'snmptoneralerts') . '</td>';
        echo "<td>";
        Dropdown::showYesNo('enable_weekly_alerts', $config['enable_weekly_alerts']);
        echo '</td></tr>';

        // Weekly alert day
        echo "<tr class='tab_bg_1'>";
        echo "<td>" . __('Weekly alert day', 'snmptoneralerts') . '</td>';
        echo "<td>";
        $days = [
            1 => __('Monday'),
            2 => __('Tuesday'),
            3 => __('Wednesday'),
            4 => __('Thursday'),
            5 => __('Friday'),
            6 => __('Saturday'),
            0 => __('Sunday'),
        ];
        Dropdown::showFromArray('alert_day_weekly', $days, ['value' => $config['alert_day_weekly']]);
        echo '</td></tr>';

        // Weekly alert time
        echo "<tr class='tab_bg_1'>";
        echo "<td>" . __('Weekly alert time', 'snmptoneralerts') . '</td>';
        echo "<td>";
        echo "<input type='time' name='alert_time_weekly' value='" . substr($config['alert_time_weekly'], 0, 5) . "' style='width: 120px;'>";
        echo '</td></tr>';

        echo "<tr class='tab_bg_2'>";
        echo "<td colspan='2' class='center'>";
        echo "<input type='submit' name='update' class='btn btn-primary' value=\"" . _sx('button', 'Save') . '">';
        echo '</td></tr>';

        echo '</table></div>';
        Html::closeForm();
    }

    public static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0)
    {
        if ($item->getType() == 'Config') {
            $config = new self();
            $config->showFormConfig();
        }
        return true;
    }
}
