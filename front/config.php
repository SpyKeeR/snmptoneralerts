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

use GlpiPlugin\Snmptoneralerts\Config as PluginConfig;

include('../../../inc/includes.php');

Session::checkRight('config', UPDATE);

$config = new PluginConfig();

// Ajout d'une imprimante à exclure
if (isset($_POST['add_exclusion'])) {
    global $DB;
    
    if (isset($_POST['printers_id']) && $_POST['printers_id'] > 0) {
        // Vérifier que l'imprimante n'est pas déjà exclue
        $existing = $DB->request([
            'SELECT' => 'id',
            'FROM' => 'glpi_plugin_snmptoneralerts_excludedprinters',
            'WHERE' => ['printers_id' => $_POST['printers_id']]
        ]);
        
        if (count($existing) == 0) {
            $DB->insert('glpi_plugin_snmptoneralerts_excludedprinters', [
                'printers_id' => $_POST['printers_id'],
                'reason' => $_POST['reason'] ?? '',
                'date_creation' => $_SESSION['glpi_currenttime'],
                'users_id' => Session::getLoginUserID()
            ]);
            
            Session::addMessageAfterRedirect(__('Printer successfully excluded', 'snmptoneralerts'), false, INFO);
        } else {
            Session::addMessageAfterRedirect(__('This printer is already excluded', 'snmptoneralerts'), false, WARNING);
        }
    }
    Html::back();
}

// Suppression d'une exclusion
if (isset($_POST['delete_exclusion'])) {
    global $DB;
    
    if (isset($_POST['exclusion_id']) && $_POST['exclusion_id'] > 0) {
        $DB->delete('glpi_plugin_snmptoneralerts_excludedprinters', [
            'id' => $_POST['exclusion_id']
        ]);
        
        Session::addMessageAfterRedirect(__('Exclusion successfully removed', 'snmptoneralerts'), false, INFO);
    }
    Html::back();
}

if (isset($_POST['update_config'])) {
    $config->update($_POST);
    Html::back();
} else {
    Html::header(__('SNMP Toner Alerts', 'snmptoneralerts'), $_SERVER['PHP_SELF'], 'config', 'plugins');
    $config->showForm();
    Html::footer();
}
