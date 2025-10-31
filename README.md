# SNMP Toner Alerts pour GLPI

[![License: GPL v3](https://img.shields.io/badge/License-GPLv3-blue.svg)](https://www.gnu.org/licenses/gpl-3.0)
[![GLPI](https://img.shields.io/badge/GLPI-11.0-green.svg)](https://glpi-project.org/)
[![Version](https://img.shields.io/badge/version-1.0.2-orange.svg)](https://github.com/SpyKeeR/snmptoneralerts)

Plugin GLPI pour la surveillance automatique des niveaux de toners via SNMP avec alertes intelligentes.

## ✨ Fonctionnalités

- 📊 **Surveillance automatique** des niveaux de toners SNMP
- 🔔 **Alertes progressives** (journalières puis hebdomadaires)
- 🎯 **Gestion par toner** (pas par imprimante)
- ⚙️ **Configuration flexible** (seuils, emails, fréquences)
- 🚫 **Exclusion d imprimantes** problématiques
- 📧 **Notifications GLPI** avec balises dynamiques
- 🔗 **Références cartouches** automatiques

## 🚀 Installation rapide

1. Télécharger dans plugins/snmptoneralerts
2. **Configuration > Plugins** > Installer > Activer
3. **Configuration > SNMP Toner Alerts**

Voir [INSTALL.md](INSTALL.md) pour les détails.

## 📋 Prérequis

- GLPI >= 11.0.0
- PHP >= 8.2
- NetInventory/NetDiscovery configuré

## 📖 Documentation

- [INSTALL.md](INSTALL.md) - Installation détaillée
- [CHANGELOG.md](CHANGELOG.md) - Historique des versions
- [LICENSE](LICENSE) - Licence GPL-3.0

## ��️ Utilisation

### Configuration

1. Accéder à **Configuration > SNMP Toner Alerts**
2. Définir le seuil d alerte (ex: 20%)
3. Ajouter les emails destinataires
4. Ajuster les fréquences si besoin

### Actions automatiques

3 tâches cron sont créées automatiquement:
- **CheckTonerLevels** - Vérifie les niveaux 4x/jour
- **SendDailyAlerts** - Alertes quotidiennes le matin
- **SendWeeklyRecap** - Récapitulatif hebdomadaire vendredi midi

## 🗄️ Architecture

### Tables créées

- glpi_plugin_snmptoneralerts_excludedprinters - Imprimantes exclues
- glpi_plugin_snmptoneralerts_states - États actuels avec compteurs
- glpi_plugin_snmptoneralerts_alerts - Historique des alertes

### Mapping SNMP

| Propriété SNMP | Recherché dans comment |
|----------------|------------------------|
| tonerblack     | black, noir, bk        |
| tonercyan      | cyan, c                |
| tonermagenta   | magenta, m             |
| toneryellow    | yellow, jaune, y       |
| drumblack      | drum black             |

## 💬 Support

- **Issues**: [GitHub Issues](https://github.com/SpyKeeR/snmptoneralerts/issues)
- **Discussions**: [GitHub Discussions](https://github.com/SpyKeeR/snmptoneralerts/discussions)

## 📝 Licence

GPL-3.0-or-later - Voir [LICENSE](LICENSE)

## 👤 Auteur

**SpyKeeR** - [GitHub](https://github.com/SpyKeeR)

---

*Note: Nécessite NetInventory configuré avec remontée SNMP des imprimantes*
