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
use GlpiPlugin\Snmptoneralerts\TonerAlert;
use GlpiPlugin\Snmptoneralerts\Config as PluginConfig;
use GlpiPlugin\Snmptoneralerts\NotificationTargetTonerAlert;

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
    $defaults = PluginConfig::getDefaults();
    Config::setConfigurationValues('snmptoneralerts', $defaults);

    $default_charset   = DBConnection::getDefaultCharset();
    $default_collation = DBConnection::getDefaultCollation();
    $default_key_sign  = DBConnection::getDefaultPrimaryKeySignOption();

    // Table for excluded printers
    if (!$DB->tableExists('glpi_plugin_snmptoneralerts_excludedprinters')) {
        $query = "CREATE TABLE `glpi_plugin_snmptoneralerts_excludedprinters` (
                  `id` int {$default_key_sign} NOT NULL AUTO_INCREMENT,
                  `printers_id` int {$default_key_sign} NOT NULL,
                  `reason` text,
                  `date_creation` timestamp NULL DEFAULT NULL,
                  `users_id` int {$default_key_sign} NOT NULL DEFAULT '0',
                  PRIMARY KEY (`id`),
                  KEY `printers_id` (`printers_id`),
                  KEY `users_id` (`users_id`)
               ) ENGINE=InnoDB DEFAULT CHARSET={$default_charset} COLLATE={$default_collation} ROW_FORMAT=DYNAMIC;";
        $DB->doQuery($query) or die($DB->error());
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
                  `last_alert_date` timestamp NULL DEFAULT NULL,
                  `first_alert_date` timestamp NULL DEFAULT NULL,
                  `date_mod` timestamp NULL DEFAULT NULL,
                  PRIMARY KEY (`id`),
                  UNIQUE KEY `unique_cartridgeinfo` (`printers_cartridgeinfos_id`),
                  KEY `printers_id` (`printers_id`),
                  KEY `is_alert` (`is_alert`),
                  KEY `property` (`property`)
               ) ENGINE=InnoDB DEFAULT CHARSET={$default_charset} COLLATE={$default_collation} ROW_FORMAT=DYNAMIC;";
        $DB->doQuery($query) or die($DB->error());
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
                  `date_creation` timestamp NULL DEFAULT NULL,
                  PRIMARY KEY (`id`),
                  KEY `printers_id` (`printers_id`),
                  KEY `alert_type` (`alert_type`),
                  KEY `date_creation` (`date_creation`)
               ) ENGINE=InnoDB DEFAULT CHARSET={$default_charset} COLLATE={$default_collation} ROW_FORMAT=DYNAMIC;";
        $DB->doQuery($query) or die($DB->error());
    }

    // Table for notification sessions (itemtype for notifications)
    if (!$DB->tableExists('glpi_plugin_snmptoneralerts_notifications')) {
        $query = "CREATE TABLE `glpi_plugin_snmptoneralerts_notifications` (
                  `id` int {$default_key_sign} NOT NULL AUTO_INCREMENT,
                  `alert_type` enum('daily','weekly') NOT NULL,
                  `printers_count` int NOT NULL DEFAULT '0',
                  `toners_count` int NOT NULL DEFAULT '0',
                  `date_creation` timestamp NULL DEFAULT NULL,
                  PRIMARY KEY (`id`),
                  KEY `alert_type` (`alert_type`),
                  KEY `date_creation` (`date_creation`)
               ) ENGINE=InnoDB DEFAULT CHARSET={$default_charset} COLLATE={$default_collation} ROW_FORMAT=DYNAMIC;";
        $DB->doQuery($query) or die($DB->error());
    }

    // Register cron tasks
    // Check toner levels every 6 hours (4 times per day)
    CronTask::Register(
        TonerMonitor::class,
        'CheckTonerLevels',
        6 * HOUR_TIMESTAMP,
        ['state' => CronTask::STATE_WAITING, 'mode' => CronTask::MODE_EXTERNAL]
    );

    // Send daily alerts once per day between 6:00 AM and 8:00 AM
    CronTask::Register(
        TonerMonitor::class,
        'SendDailyAlerts',
        DAY_TIMESTAMP,
        [
            'state' => CronTask::STATE_WAITING,
            'mode' => CronTask::MODE_EXTERNAL,
            'hourmin' => 6,
            'hourmax' => 8
        ]
    );

    // Send weekly recap once per week between 12:00 PM and 2:00 PM
    CronTask::Register(
        TonerMonitor::class,
        'SendWeeklyRecap',
        WEEK_TIMESTAMP,
        [
            'state' => CronTask::STATE_WAITING,
            'mode' => CronTask::MODE_EXTERNAL,
            'hourmin' => 12,
            'hourmax' => 14
        ]
    );

    // Create notification templates and notifications
    plugin_snmptoneralerts_createNotifications();

    return true;
}

/**
 * Create notification templates and notifications
 *
 * @return void
 */
function plugin_snmptoneralerts_createNotifications()
{
    global $DB;

    // Create notification templates for daily and weekly alerts
    $notification_template = new NotificationTemplate();
    $notification = new Notification();
    $notif_notificationtemplate = new Notification_NotificationTemplate();

    // Template for Daily Alerts
    $template_id_daily = null;
    if ($notification_template->getFromDBByCrit(['itemtype' => TonerAlert::class, 'name' => 'SNMP Toner Alert - Daily'])) {
        $template_id_daily = $notification_template->fields['id'];
    }
    
    if (!$template_id_daily) {
        $template_id_daily = $notification_template->add([
            'name'     => __('SNMP Toner Alert - Daily', 'snmptoneralerts'),
            'itemtype' => TonerAlert::class,
            'comment'  => __('Template for daily toner alerts', 'snmptoneralerts')
        ]);

        // Add translation (French)
        $translation = new NotificationTemplateTranslation();
        $translation->add([
            'notificationtemplates_id' => $template_id_daily,
            'language'                 => '',
            'subject'                  => 'Alertes toners - Quotidienne (##toner.count## imprimante##toner.s##)',
            'content_text'             => "Bonjour,\n\n##toner.count## imprimante(s) ont des toners en dessous du seuil d'alerte (##toner.threshold##%).\n\nType d'alerte: ##toner.alert_type##\n\n##PRINTERS##\n\nMerci de vérifier les niveaux et de remplacer les toners nécessaires.\n\n---\nSNMP Toner Alerts pour GLPI\nCe message est envoyé automatiquement.",
            'content_html'             => '<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>
<body style="margin: 0; padding: 0; font-family: Arial, Helvetica, sans-serif; background-color: #f5f5f5;">
    <table width="100%" cellpadding="0" cellspacing="0" style="background-color: #f5f5f5; padding: 20px 0;">
        <tr>
            <td align="center">
                <table width="600" cellpadding="0" cellspacing="0" style="background-color: #ffffff; border: 1px solid #e5e7eb;">
                    <!-- Header -->
                    <tr>
                        <td style="background-color: #667eea; padding: 20px; text-align: center; border-bottom: 4px solid #5a67d8;">
                            <h1 style="margin: 0; color: #ffffff; font-size: 24px; font-weight: bold;">
                                🖨️ Alertes Toners - SNMP
                            </h1>
                            <p style="margin: 10px 0 0 0; color: #ffffff; font-size: 16px; font-weight: normal;">
                                Surveillance quotidienne
                            </p>
                        </td>
                    </tr>
                    
                    <!-- Summary -->
                    <tr>
                        <td style="padding: 15px 30px; background-color: #fff7e6; border-bottom: 3px solid #f59e0b;">
                            <table width="100%" cellpadding="0" cellspacing="0">
                                <tr>
                                    <td style="text-align: center;">
                                        <p style="margin: 0; font-size: 22px; font-weight: bold; color: #f59e0b;">
                                            ##toner.count## imprimante##toner.s## en alerte
                                        </p>
                                        <p style="margin: 10px 0 0 0; color: #78716c; font-size: 15px;">
                                            Seuil d\'alerte : <strong>##toner.threshold##%</strong> • Type : <strong>##toner.alert_type##</strong>
                                        </p>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                    
                    <!-- Printers List -->
                    <tr>
                        <td style="padding: 30px;">
                            ##PRINTERS_HTML##
                        </td>
                    </tr>
                    
                    <!-- Footer -->
                    <tr>
                        <td style="background-color: #f9fafb; padding: 20px 30px; border-top: 2px solid #e5e7eb;">
                            <p style="margin: 0; color: #374151; font-size: 15px; text-align: center; font-weight: bold;">
                                Action recommandée : Vérifiez les niveaux et remplacez les toners nécessaires.
                            </p>
                            <p style="margin: 15px 0 0 0; color: #6b7280; font-size: 13px; text-align: center;">
                                SNMP Toner Alerts pour GLPI - Message automatique<br>
                                Ne pas répondre à cet email
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>'
        ]);
    }

    // Template for Weekly Recap
    $template_id_weekly = null;
    if ($notification_template->getFromDBByCrit(['itemtype' => TonerAlert::class, 'name' => 'SNMP Toner Alert - Weekly'])) {
        $template_id_weekly = $notification_template->fields['id'];
    }
    
    if (!$template_id_weekly) {
        $template_id_weekly = $notification_template->add([
            'name'     => __('SNMP Toner Alert - Weekly', 'snmptoneralerts'),
            'itemtype' => TonerAlert::class,
            'comment'  => __('Template for weekly toner recap', 'snmptoneralerts')
        ]);

        // Add translation (French)
        $translation = new NotificationTemplateTranslation();
        $translation->add([
            'notificationtemplates_id' => $template_id_weekly,
            'language'                 => '',
            'subject'                  => 'Récapitulatif toners - Hebdomadaire (##toner.count## imprimante##toner.s##)',
            'content_text'             => "Bonjour,\n\n##toner.count## imprimante(s) ont des toners en dessous du seuil d'alerte (##toner.threshold##%).\n\nType d'alerte: ##toner.alert_type## (alertes répétées)\n\n##PRINTERS##\n\nCes imprimantes nécessitent une attention urgente.\n\n---\nSNMP Toner Alerts pour GLPI\nCe message est envoyé automatiquement.",
            'content_html'             => '<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>
<body style="margin: 0; padding: 0; font-family: Arial, Helvetica, sans-serif; background-color: #f5f5f5;">
    <table width="100%" cellpadding="0" cellspacing="0" style="background-color: #f5f5f5; padding: 20px 0;">
        <tr>
            <td align="center">
                <table width="600" cellpadding="0" cellspacing="0" style="background-color: #ffffff; border: 1px solid #e5e7eb;">
                    <!-- Header -->
                    <tr>
                        <td style="background-color: #dc2626; padding: 20px; text-align: center; border-bottom: 4px solid #991b1b;">
                            <h1 style="margin: 0; color: #ffffff; font-size: 24px; font-weight: bold;">
                                🚨 Récapitulatif Hebdomadaire - SNMP
                            </h1>
                            <p style="margin: 10px 0 0 0; color: #ffffff; font-size: 16px; font-weight: normal;">
                                Alertes persistantes nécessitant une action
                            </p>
                        </td>
                    </tr>
                    
                    <!-- Summary -->
                    <tr>
                        <td style="padding: 12px 30px; background-color: #fef2f2; border-bottom: 3px solid #dc2626;">
                            <table width="100%" cellpadding="0" cellspacing="0">
                                <tr>
                                    <td style="text-align: center;">
                                        <p style="margin: 0; font-size: 22px; font-weight: bold; color: #dc2626;">
                                            ##toner.count## imprimante##toner.s## en alerte persistante
                                        </p>
                                        <p style="margin: 8px 0 0 0; color: #78716c; font-size: 15px;">
                                            Seuil d\'alerte : <strong>##toner.threshold##%</strong> • Type : <strong>##toner.alert_type##</strong>
                                        </p>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                    
                    <!-- Printers List -->
                    <tr>
                        <td style="padding: 30px;">
                            ##PRINTERS_HTML##
                        </td>
                    </tr>
                    
                    <!-- Footer -->
                    <tr>
                        <td style="background-color: #f9fafb; padding: 20px 30px; border-top: 2px solid #e5e7eb;">
                            <p style="margin: 0; color: #dc2626; font-size: 16px; text-align: center; font-weight: bold;">
                                Action urgente : Vérifier la remontée SNMP et la connectivité de l\'imprimante, sinon la décommissionner.
                            </p>
                            <p style="margin: 15px 0 0 0; color: #6b7280; font-size: 13px; text-align: center;">
                                SNMP Toner Alerts pour GLPI - Message automatique<br>
                                Ne pas répondre à cet email
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>'
        ]);
    }

    // Create Notification for Daily Alerts
    $notif_id_daily = null;
    if ($notification->getFromDBByCrit(['itemtype' => TonerAlert::class, 'event' => 'toner_alert_daily'])) {
        $notif_id_daily = $notification->fields['id'];
    }
    
    if (!$notif_id_daily) {
        $notif_id_daily = $notification->add([
            'name'         => __('SNMP Toner Alert - Daily', 'snmptoneralerts'),
            'entities_id'  => 0,
            'itemtype'     => TonerAlert::class,
            'event'        => 'toner_alert_daily',
            'is_active'    => 1,
            'is_recursive' => 1
        ]);

        // Link notification to template
        $notif_notificationtemplate->add([
            'notifications_id'         => $notif_id_daily,
            'notificationtemplates_id' => $template_id_daily,
            'mode'                     => Notification_NotificationTemplate::MODE_MAIL
        ]);

        // Add Administrator as default recipient
        $notif_target = new NotificationTarget();
        $notif_target->add([
            'notifications_id' => $notif_id_daily,
            'type'             => Notification::GLOBAL_ADMINISTRATOR,
            'items_id'         => Notification::GLOBAL_ADMINISTRATOR
        ]);
    }

    // Create Notification for Weekly Recap
    $notif_id_weekly = null;
    if ($notification->getFromDBByCrit(['itemtype' => TonerAlert::class, 'event' => 'toner_alert_weekly'])) {
        $notif_id_weekly = $notification->fields['id'];
    }
    
    if (!$notif_id_weekly) {
        $notif_id_weekly = $notification->add([
            'name'         => __('SNMP Toner Alert - Weekly', 'snmptoneralerts'),
            'entities_id'  => 0,
            'itemtype'     => TonerAlert::class,
            'event'        => 'toner_alert_weekly',
            'is_active'    => 1,
            'is_recursive' => 1
        ]);

        // Link notification to template
        $notif_notificationtemplate->add([
            'notifications_id'         => $notif_id_weekly,
            'notificationtemplates_id' => $template_id_weekly,
            'mode'                     => Notification_NotificationTemplate::MODE_MAIL
        ]);

        // Add Administrator as default recipient
        $notif_target = new NotificationTarget();
        $notif_target->add([
            'notifications_id' => $notif_id_weekly,
            'type'             => Notification::GLOBAL_ADMINISTRATOR,
            'items_id'         => Notification::GLOBAL_ADMINISTRATOR
        ]);
    }
}


/**
 * Plugin uninstall process
 *
 * @return boolean
 */
function plugin_snmptoneralerts_uninstall()
{
    global $DB;

    // Delete configuration using native GLPI Config class
    $config = new Config();
    $config->deleteConfigurationValues('snmptoneralerts');

    // Security: Ensure all configuration keys are removed from glpi_configs
    // In case deleteConfigurationValues didn't work properly
    $DB->delete('glpi_configs', [
        'context' => 'snmptoneralerts'
    ]);

    // Drop plugin tables
    $tables = [
        'glpi_plugin_snmptoneralerts_excludedprinters',
        'glpi_plugin_snmptoneralerts_states',
        'glpi_plugin_snmptoneralerts_alerts',
        'glpi_plugin_snmptoneralerts_notifications',
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

    // Remove notifications created by the plugin
    $DB->delete('glpi_notifications', [
        'itemtype' => TonerAlert::class,
    ]);

    // Remove notification templates created by the plugin
    $DB->delete('glpi_notificationtemplates', [
        'itemtype' => TonerAlert::class,
    ]);

    // Note: Notification targets and translations are automatically deleted
    // by GLPI's foreign key constraints (ON DELETE CASCADE)

    return true;
}
