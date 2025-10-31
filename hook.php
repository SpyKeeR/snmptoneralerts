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

use GlpiPlugin\Snmptoneralerts\TonerMonitor;
use GlpiPlugin\Snmptoneralerts\Config;

/**
 * Plugin change profile hook
 */
function plugin_change_profile_snmptoneralerts()
{
    // Logic when profile changes if needed
}

/**
 * Plugin install process
 *
 * @return boolean
 */
function plugin_snmptoneralerts_install()
{
    global $DB;

    $migration = new Migration(PLUGIN_SNMPTONERALERTS_VERSION);
    
    // Set default configuration values
    $defaults = Config::getDefaults();
    Config::setConfigurationValues('plugin:Snmptoneralerts', $defaults);

    $default_charset   = DBConnection::getDefaultCharset();
    $default_collation = DBConnection::getDefaultCollation();
    $default_key_sign  = DBConnection::getDefaultPrimaryKeySignOption();

    // Table for excluded printers
    if (!$DB->tableExists('glpi_plugin_snmptoneralerts_excludedprinters')) {
        $query = "CREATE TABLE `glpi_plugin_snmptoneralerts_excludedprinters` (
                  `id` int {$default_key_sign} NOT NULL AUTO_INCREMENT,
                  `printers_id` int {$default_key_sign} NOT NULL,
                  `reason` text,
                  `date_creation` datetime DEFAULT NULL,
                  `users_id` int {$default_key_sign} NOT NULL DEFAULT '0',
                  PRIMARY KEY (`id`),
                  KEY `printers_id` (`printers_id`),
                  KEY `users_id` (`users_id`)
               ) ENGINE=InnoDB DEFAULT CHARSET={$default_charset} COLLATE={$default_collation} ROW_FORMAT=DYNAMIC;";
        $DB->doQuery($query);
    }

    // Table for toner states (current alert status)
    if (!$DB->tableExists('glpi_plugin_snmptoneralerts_states')) {
        $query = "CREATE TABLE `glpi_plugin_snmptoneralerts_states` (
                  `id` int {$default_key_sign} NOT NULL AUTO_INCREMENT,
                  `printers_cartridgeinfos_id` int {$default_key_sign} NOT NULL,
                  `printers_id` int {$default_key_sign} NOT NULL,
                  `property` varchar(100) NOT NULL,
                  `current_value` varchar(50) DEFAULT NULL,
                  `is_alert` tinyint NOT NULL DEFAULT '0',
                  `alert_count` int NOT NULL DEFAULT '0',
                  `last_alert_date` datetime DEFAULT NULL,
                  `first_alert_date` datetime DEFAULT NULL,
                  `date_mod` datetime DEFAULT NULL,
                  PRIMARY KEY (`id`),
                  UNIQUE KEY `unique_cartridgeinfo` (`printers_cartridgeinfos_id`),
                  KEY `printers_id` (`printers_id`),
                  KEY `is_alert` (`is_alert`),
                  KEY `property` (`property`)
               ) ENGINE=InnoDB DEFAULT CHARSET={$default_charset} COLLATE={$default_collation} ROW_FORMAT=DYNAMIC;";
        $DB->doQuery($query);
    }

    // Table for alerts history
    if (!$DB->tableExists('glpi_plugin_snmptoneralerts_alerts')) {
        $query = "CREATE TABLE `glpi_plugin_snmptoneralerts_alerts` (
                  `id` int {$default_key_sign} NOT NULL AUTO_INCREMENT,
                  `printers_cartridgeinfos_id` int {$default_key_sign} NOT NULL DEFAULT '0',
                  `printers_id` int {$default_key_sign} NOT NULL,
                  `property` varchar(100) NOT NULL,
                  `value_at_alert` varchar(50) DEFAULT NULL,
                  `alert_type` enum('daily','weekly') NOT NULL,
                  `alert_count` int NOT NULL DEFAULT '1',
                  `notification_sent` tinyint NOT NULL DEFAULT '0',
                  `date_creation` datetime DEFAULT NULL,
                  PRIMARY KEY (`id`),
                  KEY `printers_id` (`printers_id`),
                  KEY `alert_type` (`alert_type`),
                  KEY `date_creation` (`date_creation`)
               ) ENGINE=InnoDB DEFAULT CHARSET={$default_charset} COLLATE={$default_collation} ROW_FORMAT=DYNAMIC;";
        $DB->doQuery($query);
    }

    // Register cron tasks
    // Check toner levels every 6 hours (4 times per day)
    CronTask::Register(
        TonerMonitor::class,
        'CheckTonerLevels',
        6 * HOUR_TIMESTAMP,
        ['state' => CronTask::STATE_WAITING, 'mode' => CronTask::MODE_EXTERNAL]
    );

    // Send daily alerts once per day at 8:00 AM
    CronTask::Register(
        TonerMonitor::class,
        'SendDailyAlerts',
        DAY_TIMESTAMP,
        ['state' => CronTask::STATE_WAITING, 'mode' => CronTask::MODE_EXTERNAL]
    );

    // Send weekly recap once per week on Friday at noon
    CronTask::Register(
        TonerMonitor::class,
        'SendWeeklyRecap',
        WEEK_TIMESTAMP,
        ['state' => CronTask::STATE_WAITING, 'mode' => CronTask::MODE_EXTERNAL]
    );

    return true;
}


/**
 * Plugin uninstall process
 *
 * @return boolean
 */
function plugin_snmptoneralerts_uninstall()
{
    global $DB;

    $config = new Config();
    $config->deleteConfigurationValues('plugin:Snmptoneralerts');

    // Drop plugin tables
    $tables = [
        'glpi_plugin_snmptoneralerts_excludedprinters',
        'glpi_plugin_snmptoneralerts_states',
        'glpi_plugin_snmptoneralerts_alerts',
    ];

    foreach ($tables as $table) {
        if ($DB->tableExists($table)) {
            $DB->doQuery("DROP TABLE `$table`");
        }
    }

    // Remove cron tasks
    $DB->delete('glpi_crontasks', [
        'itemtype' => TonerMonitor::class,
    ]);

    return true;
}
