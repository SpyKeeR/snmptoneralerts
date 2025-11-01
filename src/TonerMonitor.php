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
use CronTask;
use DBConnection;
use QueuedNotification;

/**
 * Main monitoring class for toner levels
 */
class TonerMonitor extends CommonDBTM
{
    protected static $notable = true;

    /**
     * Return the localized name of the current Type
     *
     * @return string
     */
    public static function getTypeName($nb = 0)
    {
        return __('SNMP Toner Alerts', 'snmptoneralerts');
    }

    /**
     * Get search options for the itemtype
     *
     * @return array
     */
    public function rawSearchOptions()
    {
        $tab = [];

        $tab[] = [
            'id'   => 'common',
            'name' => self::getTypeName()
        ];

        $tab[] = [
            'id'            => '1',
            'table'         => 'glpi_plugin_snmptoneralerts_states',
            'field'         => 'id',
            'name'          => __('ID'),
            'datatype'      => 'itemlink',
            'massiveaction' => false
        ];

        return $tab;
    }

    /**
     * Get cron task information
     *
     * @param string $name Task name
     * @return array
     */
    public static function cronInfo($name)
    {
        switch ($name) {
            case 'CheckTonerLevels':
                return [
                    'description' => __('Check toner levels from SNMP', 'snmptoneralerts'),
                ];

            case 'SendDailyAlerts':
                return [
                    'description' => __('Send daily toner alerts', 'snmptoneralerts'),
                ];

            case 'SendWeeklyRecap':
                return [
                    'description' => __('Send weekly toner alerts recap', 'snmptoneralerts'),
                ];
        }

        return [];
    }

    /**
     * Check toner levels and update alert states
     * Should run 3-4 times per day
     *
     * @param CronTask $task
     * @return int
     */
    public static function cronCheckTonerLevels($task)
    {
        global $DB;

        $config = Config::getConfig();
        $threshold = $config['threshold_percentage'];
        $max_alerts = $config['max_daily_alerts'];
        $checked = 0;
        $alerted = 0;
        $cleared = 0;

        // Get all non-excluded printers with cartridge info
        $result = $DB->request([
            'SELECT' => [
                'pci.id AS cartridgeinfo_id',
                'pci.printers_id',
                'pci.property',
                'pci.value',
                'p.name AS printer_name',
                'p.locations_id',
                'p.printermodels_id'
            ],
            'FROM' => 'glpi_printers_cartridgeinfos AS pci',
            'INNER JOIN' => [
                'glpi_printers AS p' => [
                    'ON' => [
                        'p' => 'id',
                        'pci' => 'printers_id'
                    ]
                ]
            ],
            'LEFT JOIN' => [
                'glpi_plugin_snmptoneralerts_excludedprinters AS ep' => [
                    'ON' => [
                        'ep' => 'printers_id',
                        'p' => 'id'
                    ]
                ]
            ],
            'WHERE' => [
                'ep.id' => null,
                'p.is_deleted' => 0,
                'pci.property' => ['tonerblack', 'tonercyan', 'tonermagenta', 'toneryellow',
                                   'drumblack', 'drumcyan', 'drummagenta', 'drumyellow']
            ],
            'ORDER' => ['p.id', 'pci.property']
        ]);

        foreach ($result as $row) {
            $checked++;
            $value = $row['value'];
            
            // Skip if value is not numeric
            if (!is_numeric($value)) {
                continue;
            }

            $value_int = intval($value);
            $cartridgeinfo_id = $row['cartridgeinfo_id'];

            // Check if state exists
            $state_result = $DB->request([
                'FROM' => 'glpi_plugin_snmptoneralerts_states',
                'WHERE' => ['printers_cartridgeinfos_id' => $cartridgeinfo_id]
            ]);
            $state = null;
            
            if (count($state_result) > 0) {
                $state = $state_result->current();
            }

            // Toner is below threshold
            if ($value_int < $threshold) {
                if ($state) {
                    // Update existing state
                    $DB->update(
                        'glpi_plugin_snmptoneralerts_states',
                        [
                            'current_value' => $value,
                            'is_alert'      => 1,
                            'date_mod'      => $_SESSION['glpi_currenttime'],
                        ],
                        ['id' => $state['id']]
                    );

                    // If alert was previously cleared, reset counter
                    if ($state['is_alert'] == 0) {
                        $DB->update(
                            'glpi_plugin_snmptoneralerts_states',
                            [
                                'alert_count'      => 0,
                                'first_alert_date' => $_SESSION['glpi_currenttime'],
                            ],
                            ['id' => $state['id']]
                        );
                        $alerted++;
                    }
                } else {
                    // Create new state with alert
                    $DB->insert(
                        'glpi_plugin_snmptoneralerts_states',
                        [
                            'printers_cartridgeinfos_id' => $cartridgeinfo_id,
                            'printers_id'                 => $row['printers_id'],
                            'property'                    => $row['property'],
                            'current_value'               => $value,
                            'is_alert'                    => 1,
                            'alert_count'                 => 0,
                            'first_alert_date'            => $_SESSION['glpi_currenttime'],
                            'date_mod'                    => $_SESSION['glpi_currenttime'],
                        ]
                    );
                    $alerted++;
                }
            } else {
                // Toner is above threshold - clear alert if needed
                if ($state && $state['is_alert'] == 1) {
                    $DB->update(
                        'glpi_plugin_snmptoneralerts_states',
                        [
                            'current_value'    => $value,
                            'is_alert'         => 0,
                            'alert_count'      => 0,
                            'last_alert_date'  => null,
                            'first_alert_date' => null,
                            'date_mod'         => $_SESSION['glpi_currenttime'],
                        ],
                        ['id' => $state['id']]
                    );
                    $cleared++;
                } elseif ($state) {
                    // Just update value
                    $DB->update(
                        'glpi_plugin_snmptoneralerts_states',
                        [
                            'current_value' => $value,
                            'date_mod'      => $_SESSION['glpi_currenttime'],
                        ],
                        ['id' => $state['id']]
                    );
                }
            }
        }

        $task->log("Checked: $checked toners, New alerts: $alerted, Cleared: $cleared");
        $task->setVolume($checked);

        return 1;
    }

    /**
     * Send daily alerts for toners below threshold
     * Should run once per day in the morning
     *
     * @param CronTask $task
     * @return int
     */
    public static function cronSendDailyAlerts($task)
    {
        global $DB;

        $config = Config::getConfig();
        $max_alerts = $config['max_daily_alerts'];
        $sent = 0;

        // Get all toners in alert state with alert_count < max_alerts
        $result = $DB->request([
            'SELECT' => [
                's.*',
                'p.name AS printer_name',
                'p.locations_id',
                'p.printermodels_id AS printermodels_id',
                'l.completename AS location_name',
                'pm.name AS printer_model_name'
            ],
            'FROM' => 'glpi_plugin_snmptoneralerts_states AS s',
            'INNER JOIN' => [
                'glpi_printers AS p' => [
                    'ON' => [
                        'p' => 'id',
                        's' => 'printers_id'
                    ]
                ]
            ],
            'LEFT JOIN' => [
                'glpi_locations AS l' => [
                    'ON' => [
                        'l' => 'id',
                        'p' => 'locations_id'
                    ]
                ],
                'glpi_printermodels AS pm' => [
                    'ON' => [
                        'pm' => 'id',
                        'p' => 'printermodels_id'
                    ]
                ]
            ],
            'WHERE' => [
                's.is_alert' => 1,
                'p.is_deleted' => 0,
                new \QueryExpression('s.alert_count < ' . $DB->escape($max_alerts))
            ],
            'ORDER' => ['p.name', 's.property']
        ]);
        $alerts_by_printer = [];

        foreach ($result as $row) {
            $printer_id = $row['printers_id'];
            if (!isset($alerts_by_printer[$printer_id])) {
                $alerts_by_printer[$printer_id] = [
                    'printer_name'      => $row['printer_name'],
                    'location'          => $row['location_name'] ?? __('Unknown'),
                    'model'             => $row['printer_model_name'] ?? __('Unknown'),
                    'printer_model_id'  => $row['printermodels_id'],
                    'toners'            => [],
                ];
            }

            $alerts_by_printer[$printer_id]['toners'][] = [
                'property'      => $row['property'],
                'value'         => $row['current_value'],
                'alert_count'   => $row['alert_count'],
                'state_id'      => $row['id'],
            ];
        }

        // Send notifications if there are alerts
        if (count($alerts_by_printer) > 0) {
            $sent = self::sendAlertNotification($alerts_by_printer, 'daily', $config);
            
            // Update alert counters
            foreach ($alerts_by_printer as $printer_data) {
                foreach ($printer_data['toners'] as $toner) {
                    $DB->update(
                        'glpi_plugin_snmptoneralerts_states',
                        [
                            'alert_count'     => $toner['alert_count'] + 1,
                            'last_alert_date' => $_SESSION['glpi_currenttime'],
                        ],
                        ['id' => $toner['state_id']]
                    );

                    // Log in history
                    $DB->insert(
                        'glpi_plugin_snmptoneralerts_alerts',
                        [
                            'printers_cartridgeinfos_id' => 0, // Will be updated if needed
                            'printers_id'                 => $printer_data['toners'][0]['state_id'],
                            'property'                    => $toner['property'],
                            'value_at_alert'              => $toner['value'],
                            'alert_type'                  => 'daily',
                            'alert_count'                 => $toner['alert_count'] + 1,
                            'notification_sent'           => 1,
                            'date_creation'               => $_SESSION['glpi_currenttime'],
                        ]
                    );
                }
            }
        }

        $task->log("Sent daily alerts for $sent printers");
        $task->setVolume($sent);

        return 1;
    }

    /**
     * Send weekly recap for persistent alerts
     * Should run once per week (Friday by default)
     *
     * @param CronTask $task
     * @return int
     */
    public static function cronSendWeeklyRecap($task)
    {
        global $DB;

        $config = Config::getConfig();
        $max_alerts = $config['max_daily_alerts'];
        $sent = 0;

        // Get all toners still in alert state with alert_count >= max_alerts
        $result = $DB->request([
            'SELECT' => [
                's.*',
                'p.name AS printer_name',
                'p.locations_id',
                'p.printermodels_id AS printermodels_id',
                'l.completename AS location_name',
                'pm.name AS printer_model_name'
            ],
            'FROM' => 'glpi_plugin_snmptoneralerts_states AS s',
            'INNER JOIN' => [
                'glpi_printers AS p' => [
                    'ON' => [
                        'p' => 'id',
                        's' => 'printers_id'
                    ]
                ]
            ],
            'LEFT JOIN' => [
                'glpi_locations AS l' => [
                    'ON' => [
                        'l' => 'id',
                        'p' => 'locations_id'
                    ]
                ],
                'glpi_printermodels AS pm' => [
                    'ON' => [
                        'pm' => 'id',
                        'p' => 'printermodels_id'
                    ]
                ]
            ],
            'WHERE' => [
                's.is_alert' => 1,
                'p.is_deleted' => 0,
                new \QueryExpression('s.alert_count >= ' . $DB->escape($max_alerts))
            ],
            'ORDER' => ['p.name', 's.property']
        ]);
        $alerts_by_printer = [];

        foreach ($result as $row) {
            $printer_id = $row['printers_id'];
            if (!isset($alerts_by_printer[$printer_id])) {
                $alerts_by_printer[$printer_id] = [
                    'printer_name'      => $row['printer_name'],
                    'location'          => $row['location_name'] ?? __('Unknown'),
                    'model'             => $row['printer_model_name'] ?? __('Unknown'),
                    'printer_model_id'  => $row['printermodels_id'],
                    'toners'            => [],
                ];
            }

            $alerts_by_printer[$printer_id]['toners'][] = [
                'property'      => $row['property'],
                'value'         => $row['current_value'],
                'alert_count'   => $row['alert_count'],
                'first_alert'   => $row['first_alert_date'],
                'state_id'      => $row['id'],
            ];
        }

        // Send notifications if there are persistent alerts
        if (count($alerts_by_printer) > 0) {
            $sent = self::sendAlertNotification($alerts_by_printer, 'weekly', $config);
        }

        $task->log("Sent weekly recap for $sent printers");
        $task->setVolume($sent);

        return 1;
    }

    /**
     * Send alert notification via GLPI notification system
     *
     * @param array $alerts_data
     * @param string $type 'daily' or 'weekly'
     * @param array $config
     * @return int Number of notifications sent
     */
    private static function sendAlertNotification($alerts_data, $type, $config)
    {
        $event_name = $type == 'daily' ? 'toner_alert_daily' : 'toner_alert_weekly';
        $printers_count = count($alerts_data);
        
        // Create a TonerAlert object for this notification batch
        $toner_alert = new TonerAlert();
        $notification_id = $toner_alert->add([
            'alert_type'     => $type,
            'printers_count' => $printers_count,
            'toners_count'   => array_sum(array_map(function($p) { return count($p['toners']); }, $alerts_data)),
            'date_creation'  => $_SESSION['glpi_currenttime']
        ]);

        if ($notification_id) {
            // Get the TonerAlert object we just created
            $toner_alert_obj = new TonerAlert();
            if ($toner_alert_obj->getFromDB($notification_id)) {
                // Raise notification event with TonerAlert as itemtype
                \NotificationEvent::raiseEvent(
                    $event_name,
                    $toner_alert_obj,
                    [
                        'printers' => $alerts_data,
                        'config'   => $config
                    ]
                );
                // Return the number of printers (for log display)
                return $printers_count;
            }
        }

        return 0;
    }

    /**
     * Get cartridge info (name and reference) from printer model and toner property
     *
     * @param int $printer_model_id
     * @param string $property
     * @return array ['name' => string, 'ref' => string]
     */
    public static function getCartridgeReference($printer_model_id, $property)
    {
        global $DB;

        // Map property to color search term
        $color_map = [
            'tonerblack'    => ['black', 'noir', 'bk'],
            'tonercyan'     => ['cyan', 'c'],
            'tonermagenta'  => ['magenta', 'm'],
            'toneryellow'   => ['yellow', 'jaune', 'y'],
            'drumblack'     => ['drum black', 'tambour noir'],
            'drumcyan'      => ['drum cyan', 'tambour cyan'],
            'drummagenta'   => ['drum magenta', 'tambour magenta'],
            'drumyellow'    => ['drum yellow', 'tambour jaune'],
        ];

        $search_terms = $color_map[$property] ?? [];
        
        if (empty($search_terms)) {
            return ['name' => __('Unknown'), 'ref' => ''];
        }

        // Search for cartridge items linked to this printer model
        $result = $DB->request([
            'SELECT' => [
                'ci.ref',
                'ci.name',
                'ci.comment'
            ],
            'FROM' => 'glpi_cartridgeitems AS ci',
            'INNER JOIN' => [
                'glpi_cartridgeitems_printermodels AS cipm' => [
                    'ON' => [
                        'cipm' => 'cartridgeitems_id',
                        'ci' => 'id'
                    ]
                ]
            ],
            'WHERE' => [
                'cipm.printermodels_id' => $printer_model_id
            ]
        ]);

        // First pass: search for specific color
        foreach ($result as $row) {
            $comment = strtolower($row['comment'] ?? '');
            $name = strtolower($row['name'] ?? '');
            
            foreach ($search_terms as $term) {
                if (strpos($comment, strtolower($term)) !== false || 
                    strpos($name, strtolower($term)) !== false) {
                    return [
                        'name' => $row['name'] ?? '',
                        'ref' => $row['ref'] ?? ''
                    ];
                }
            }
        }

        // Second pass: for color toners (not black), search for tri-color cartridge
        $is_color_toner = in_array($property, ['tonercyan', 'tonermagenta', 'toneryellow']);
        if ($is_color_toner) {
            foreach ($result as $row) {
                $comment = strtolower($row['comment'] ?? '');
                $name = strtolower($row['name'] ?? '');
                
                if (strpos($comment, 'tri-color') !== false || 
                    strpos($name, 'tri-color') !== false ||
                    strpos($comment, 'tricolor') !== false || 
                    strpos($name, 'tricolor') !== false ||
                    strpos($comment, 'couleur') !== false || 
                    strpos($name, 'couleur') !== false) {
                    return [
                        'name' => $row['name'] ?? '',
                        'ref' => $row['ref'] ?? ''
                    ];
                }
            }
        }

        return ['name' => __('Not defined'), 'ref' => ''];
    }
}
