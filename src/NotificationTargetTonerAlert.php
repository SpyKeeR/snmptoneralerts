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

use NotificationTarget;

/**
 * Notification target for toner alerts
 */
class NotificationTargetTonerAlert extends NotificationTarget
{
    /**
     * Get notification events
     *
     * @return array
     */
    public function getEvents()
    {
        return [
            'toner_alert_daily'  => __('Daily toner alert', 'snmptoneralerts'),
            'toner_alert_weekly' => __('Weekly toner alert recap', 'snmptoneralerts'),
        ];
    }

    /**
     * Get available tags for notifications
     *
     * @return void
     */
    public function getTags()
    {
        $tags = [
            'toner.threshold'       => __('Alert threshold percentage', 'snmptoneralerts'),
            'toner.count'           => __('Number of printers in alert', 'snmptoneralerts'),
            'toner.alert_type'      => __('Alert type (daily/weekly)', 'snmptoneralerts'),
        ];

        // Add foreach tags
        $tags['##FOREACH PRINTERS##'] = '';
        $tags['##ENDFOREACH PRINTERS##'] = '';
        $tags['printer.name'] = __('Printer name');
        $tags['printer.location'] = __('Location');
        $tags['printer.model'] = __('Model');
        
        $tags['##FOREACH TONERS##'] = '';
        $tags['##ENDFOREACH TONERS##'] = '';
        $tags['toner.property'] = __('Toner type', 'snmptoneralerts');
        $tags['toner.property_label'] = __('Toner name', 'snmptoneralerts');
        $tags['toner.level'] = __('Current level', 'snmptoneralerts');
        $tags['toner.reference'] = __('Cartridge reference', 'snmptoneralerts');
        $tags['toner.alert_number'] = __('Alert number', 'snmptoneralerts');

        foreach ($tags as $tag => $label) {
            $this->addTagToList([
                'tag'   => $tag,
                'label' => $label,
                'value' => true,
            ]);
        }
    }

    /**
     * Get data for notification
     *
     * @param array $options
     * @return void
     */
    public function getDatasForTemplate($event, $options = [])
    {
        global $CFG_GLPI;

        $this->data = [];
        $this->getTags();

        $config = Config::getConfig();
        
        // Global tags
        $this->data['##toner.threshold##'] = $config['threshold_percentage'] . '%';
        $this->data['##toner.alert_type##'] = $event == 'toner_alert_daily' ? 
            __('Daily', 'snmptoneralerts') : __('Weekly', 'snmptoneralerts');

        // Build printers list
        $printers_data = $options['printers'] ?? [];
        $this->data['##toner.count##'] = count($printers_data);

        // Foreach printers
        $printers_list = '';
        foreach ($printers_data as $printer_id => $printer_info) {
            $printers_list .= "=== " . $printer_info['printer_name'] . " ===\n";
            $printers_list .= __('Location') . ": " . $printer_info['location'] . "\n";
            $printers_list .= __('Model') . ": " . $printer_info['model'] . "\n\n";

            foreach ($printer_info['toners'] as $toner) {
                $property_label = self::getPropertyLabel($toner['property']);
                $reference = TonerMonitor::getCartridgeReference(
                    $printer_info['printer_model_id'] ?? 0,
                    $toner['property']
                );
                
                $alert_info = $event == 'toner_alert_daily' ? 
                    sprintf(__('Alert %d/%d', 'snmptoneralerts'), $toner['alert_count'] + 1, $config['max_daily_alerts']) :
                    sprintf(__('Persistent since %s', 'snmptoneralerts'), $toner['first_alert'] ?? '');

                $printers_list .= "  - " . $property_label . ": " . $toner['value'] . "% ";
                $printers_list .= "(" . __('Ref', 'snmptoneralerts') . ": " . $reference . ") ";
                $printers_list .= "[" . $alert_info . "]\n";
            }
            $printers_list .= "\n";
        }

        $this->data['##PRINTERS##'] = $printers_list;
    }

    /**
     * Get label for property
     *
     * @param string $property
     * @return string
     */
    private static function getPropertyLabel($property)
    {
        $labels = [
            'tonerblack'    => __('Black toner', 'snmptoneralerts'),
            'tonercyan'     => __('Cyan toner', 'snmptoneralerts'),
            'tonermagenta'  => __('Magenta toner', 'snmptoneralerts'),
            'toneryellow'   => __('Yellow toner', 'snmptoneralerts'),
            'drumblack'     => __('Black drum', 'snmptoneralerts'),
            'drumcyan'      => __('Cyan drum', 'snmptoneralerts'),
            'drummagenta'   => __('Magenta drum', 'snmptoneralerts'),
            'drumyellow'    => __('Yellow drum', 'snmptoneralerts'),
        ];

        return $labels[$property] ?? ucfirst($property);
    }
}
