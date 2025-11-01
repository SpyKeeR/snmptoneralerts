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
            return "<span class='d-flex align-items-center'><i class='ti ti-printer me-2'></i>" . __('Alertes toners SNMP', 'snmptoneralerts') . "</span>";
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
            'max_daily_alerts'         => 3,
        ];
    }

    /**
     * Get current configuration
     *
     * @return array
     */
    public static function getConfig()
    {
        $config = GlpiConfig::getConfigurationValues('snmptoneralerts');
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
        echo "<input type='hidden' name='config_context' value='snmptoneralerts'>";

        // Threshold percentage
        echo "<tr class='tab_bg_1'>";
        echo "<td>" . __('Threshold percentage (%)', 'snmptoneralerts') . '</td>';
        echo "<td>";
        echo "<input type='number' name='threshold_percentage' value='" . $config['threshold_percentage'] . "' min='1' max='100' style='width: 100px;'>";
        echo " " . __('Alert when toner level is below this percentage', 'snmptoneralerts');
        echo '</td></tr>';

        // Max daily alerts
        echo "<tr class='tab_bg_1'>";
        echo "<td>" . __('Maximum daily alerts per toner', 'snmptoneralerts') . '</td>';
        echo "<td>";
        echo "<input type='number' name='max_daily_alerts' value='" . $config['max_daily_alerts'] . "' min='1' max='10' style='width: 100px;'>";
        echo " " . __('After this number, switch to weekly recap', 'snmptoneralerts');
        echo '</td></tr>';

        // Quick links section
        echo "<tr class='tab_bg_1'>";
        echo "<td colspan='2' class='center'>";
        echo "<div style='padding: 15px; background: #f8f9fa; border-radius: 5px;'>";
        echo "<strong><i class='ti ti-link me-2'></i>" . __('Quick Configuration Links', 'snmptoneralerts') . "</strong>";
        echo "<div style='margin-top: 10px; display: flex; gap: 10px; flex-wrap: wrap; justify-content: center;'>";
        
        // Link to notifications
        echo "<a href='" . $CFG_GLPI['root_doc'] . "/front/notification.php' class='btn btn-sm btn-primary' target='_blank'>";
        echo "<i class='ti ti-bell me-1'></i>";
        echo __('Email Recipients', 'snmptoneralerts');
        echo "</a>";
        
        // Link to cron tasks
        echo "<a href='" . $CFG_GLPI['root_doc'] . "/front/crontask.php' class='btn btn-sm btn-primary' target='_blank'>";
        echo "<i class='ti ti-clock me-1'></i>";
        echo __('Scheduling & Frequency', 'snmptoneralerts');
        echo "</a>";
        
        // Link to notification templates
        echo "<a href='" . $CFG_GLPI['root_doc'] . "/front/notificationtemplate.php' class='btn btn-sm btn-primary' target='_blank'>";
        echo "<i class='ti ti-mail me-1'></i>";
        echo __('Email Templates', 'snmptoneralerts');
        echo "</a>";
        
        echo "</div>";
        echo "<div style='margin-top: 10px;'>";
        echo "<small class='text-muted'>";
        echo __('Recipients: Search "SNMP Toner Alert" in Notifications', 'snmptoneralerts') . " | ";
        echo __('Frequency: Edit "CheckTonerLevels", "SendDailyAlerts", "SendWeeklyRecap" tasks', 'snmptoneralerts');
        echo "</small>";
        echo "</div>";
        echo "</div>";
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
