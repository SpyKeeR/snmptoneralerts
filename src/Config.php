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
            'threshold_percentage'     => 5,
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
        global $CFG_GLPI, $DB;

        if (!Session::haveRight('config', UPDATE)) {
            return false;
        }

        $config = self::getConfig();

        // ===== SECTION 1: EXCLUSION DES IMPRIMANTES =====
        echo "<div class='center' id='excluded_printers'>";
        echo "<form name='form_exclude' action=\"" . $CFG_GLPI['root_doc'] . "/plugins/snmptoneralerts/front/config.php\" method='post'>";
        echo "<table class='tab_cadre_fixe'>";
        
        echo "<tr><th colspan='5'>" . __('Excluded Printers Management', 'snmptoneralerts') . '</th></tr>';
        
        echo "<tr class='tab_bg_1'>";
        echo "<th>" . __('Printer', 'snmptoneralerts') . '</th>';
        echo "<th>" . __('Reason', 'snmptoneralerts') . '</th>';
        echo "<th>" . __('Excluded by', 'snmptoneralerts') . '</th>';
        echo "<th>" . __('Date', 'snmptoneralerts') . '</th>';
        echo "<th></th>";
        echo "</tr>";

        // Récupérer les imprimantes exclues
        $excluded = $DB->request([
            'SELECT' => [
                'ep.id',
                'ep.printers_id',
                'ep.reason',
                'ep.users_id',
                'ep.date_creation',
                'p.name AS printer_name',
                'u.name AS user_name'
            ],
            'FROM' => 'glpi_plugin_snmptoneralerts_excludedprinters AS ep',
            'INNER JOIN' => [
                'glpi_printers AS p' => [
                    'ON' => [
                        'p' => 'id',
                        'ep' => 'printers_id'
                    ]
                ]
            ],
            'LEFT JOIN' => [
                'glpi_users AS u' => [
                    'ON' => [
                        'u' => 'id',
                        'ep' => 'users_id'
                    ]
                ]
            ],
            'ORDER' => 'ep.date_creation DESC'
        ]);

        if (count($excluded) > 0) {
            foreach ($excluded as $row) {
                echo "<tr class='tab_bg_2'>";
                echo "<td>" . $row['printer_name'] . "</td>";
                echo "<td>" . ($row['reason'] ?: '-') . "</td>";
                echo "<td>" . ($row['user_name'] ?: '-') . "</td>";
                echo "<td>" . Html::convDateTime($row['date_creation']) . "</td>";
                echo "<td>";
                echo "<form method='post' action='" . $CFG_GLPI['root_doc'] . "/plugins/snmptoneralerts/front/config.php' style='display:inline;'>";
                echo "<input type='hidden' name='exclusion_id' value='" . $row['id'] . "'>";
                echo "<button type='submit' name='delete_exclusion' class='btn btn-sm btn-danger' onclick=\"return confirm('" . __('Are you sure you want to remove this exclusion?', 'snmptoneralerts') . "');\">";
                echo "<i class='ti ti-trash'></i> " . __('Remove', 'snmptoneralerts');
                echo "</button>";
                echo Html::closeForm(false);
                echo "</td>";
                echo "</tr>";
            }
        } else {
            echo "<tr class='tab_bg_2'>";
            echo "<td colspan='5' class='center'>" . __('No excluded printers', 'snmptoneralerts') . '</td>';
            echo "</tr>";
        }

        // Formulaire d'ajout
        echo "<tr class='tab_bg_1'>";
        echo "<td>";
        Dropdown::show('Printer', [
            'name' => 'printers_id',
            'display_emptychoice' => true,
            'condition' => ['is_deleted' => 0],
        ]);
        echo "</td>";
        echo "<td>";
        echo "<input type='text' name='reason' placeholder=\"" . __('Reason for exclusion', 'snmptoneralerts') . "\" style='width: 100%;'>";
        echo "</td>";
        echo "<td colspan='3' class='center'>";
        echo "<input type='submit' name='add_exclusion' class='btn btn-sm btn-success' value=\"" . __('Add exclusion', 'snmptoneralerts') . '">';
        echo "</td>";
        echo "</tr>";

        echo '</table>';
        Html::closeForm();
        echo "</div>";

        // ===== SECTION 2: CONFIGURATION PRINCIPALE =====
        echo "<form name='form' action=\"" . Toolbox::getItemTypeFormURL('Config') . "\" method='post' style='margin-top: 20px;'>";
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

        // Bouton Sauvegarder juste après la configuration
        echo "<tr class='tab_bg_2'>";
        echo "<td colspan='2' class='center'>";
        echo "<input type='submit' name='update' class='btn btn-primary' value=\"" . _sx('button', 'Save') . '">';
        echo '</td></tr>';

        // Quick links section avec URLs de recherche
        echo "<tr class='tab_bg_1'>";
        echo "<td colspan='2' class='center'>";
        echo "<div style='padding: 20px; background: #f8f9fa; border-radius: 5px;'>";
        echo "<strong style='font-size: 1.1em;'><i class='ti ti-link me-2'></i>" . __('Quick Configuration Links', 'snmptoneralerts') . "</strong>";
        echo "<div style='margin-top: 15px; display: flex; gap: 15px; flex-wrap: wrap; justify-content: center;'>";
        
        // Link to cron tasks with search filter
        $crontask_url = $CFG_GLPI['root_doc'] . "/front/crontask.php?as_map=0&browse=0&unpublished=1&criteria%5B0%5D%5Blink%5D=AND&criteria%5B0%5D%5Bfield%5D=8&criteria%5B0%5D%5Bsearchtype%5D=equals&criteria%5B0%5D%5Bvalue%5D=GlpiPlugin%5CSnmptoneralerts%5CTonerMonitor&params%5Bhide_criteria%5D=0";
        echo "<a href='" . $crontask_url . "' class='btn btn-primary' style='padding: 10px 20px; font-size: 1em;'>";
        echo "<i class='ti ti-clock me-2'></i>";
        echo __('Scheduling & Frequency', 'snmptoneralerts');
        echo "</a>";
        
        // Link to notifications with search filter
        $notification_url = $CFG_GLPI['root_doc'] . "/front/notification.php?as_map=0&browse=0&unpublished=1&criteria%5B0%5D%5Blink%5D=AND&criteria%5B0%5D%5Bfield%5D=5&criteria%5B0%5D%5Bsearchtype%5D=equals&criteria%5B0%5D%5Bvalue%5D=GlpiPlugin%5CSnmptoneralerts%5CTonerAlert&params%5Bhide_criteria%5D=0";
        echo "<a href='" . $notification_url . "' class='btn btn-primary' style='padding: 10px 20px; font-size: 1em;'>";
        echo "<i class='ti ti-bell me-2'></i>";
        echo __('Email Recipients', 'snmptoneralerts');
        echo "</a>";
        
        // Link to notification templates with search filter
        $template_url = $CFG_GLPI['root_doc'] . "/front/notificationtemplate.php?as_map=0&browse=0&unpublished=1&criteria%5B0%5D%5Blink%5D=AND&criteria%5B0%5D%5Bfield%5D=4&criteria%5B0%5D%5Bsearchtype%5D=equals&criteria%5B0%5D%5Bvalue%5D=GlpiPlugin%5CSnmptoneralerts%5CTonerAlert&params%5Bhide_criteria%5D=0";
        echo "<a href='" . $template_url . "' class='btn btn-primary' style='padding: 10px 20px; font-size: 1em;'>";
        echo "<i class='ti ti-mail me-2'></i>";
        echo __('Email Templates', 'snmptoneralerts');
        echo "</a>";
        
        echo "</div>";
        echo "</div>";
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
