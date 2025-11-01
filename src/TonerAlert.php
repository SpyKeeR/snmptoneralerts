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

/**
 * Notification session class for SNMP Toner Alerts
 * Each notification batch (daily/weekly) creates one record
 */
class TonerAlert extends CommonDBTM
{
    /**
     * Get type name
     *
     * @param int $nb
     * @return string
     */
    public static function getTypeName($nb = 0)
    {
        return __('SNMP Toner Alerts', 'snmptoneralerts');
    }

    /**
     * Get table name for this class
     *
     * @return string
     */
    public static function getTable($classname = null)
    {
        return 'glpi_plugin_snmptoneralerts_notifications';
    }

    /**
     * Get search options
     *
     * @return array
     */
    public function rawSearchOptions()
    {
        $tab = parent::rawSearchOptions();

        $tab[] = [
            'id'            => '2',
            'table'         => self::getTable(),
            'field'         => 'alert_type',
            'name'          => __('Alert type', 'snmptoneralerts'),
            'datatype'      => 'specific',
            'massiveaction' => false
        ];

        $tab[] = [
            'id'            => '3',
            'table'         => self::getTable(),
            'field'         => 'printers_count',
            'name'          => __('Number of printers', 'snmptoneralerts'),
            'datatype'      => 'number',
            'massiveaction' => false
        ];

        $tab[] = [
            'id'            => '4',
            'table'         => self::getTable(),
            'field'         => 'date_creation',
            'name'          => __('Date'),
            'datatype'      => 'datetime',
            'massiveaction' => false
        ];

        return $tab;
    }
}
