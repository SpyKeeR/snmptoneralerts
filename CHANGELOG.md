# Changelog - SNMP Toner Alerts

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.0.3] - 2025-10-31

### 🌍 Localisation

#### Ajouté
- **Traductions françaises complètes** (`locales/fr_FR.po`)
  - 50+ chaînes traduites pour l'interface et les notifications
  - Support multilingue complet du plugin
- **Fichier template de traduction** (`locales/snmptoneralerts.pot`)
  - Base pour futures traductions (en_US, es_ES, etc.)
  - Format gettext standard
- **Fichier compilé** (`locales/fr_FR.mo`)
  - Compilation automatique avec `tools/compile_mo.php`
  - 3,062 bytes - prêt pour production

### 🧹 Nettoyage

#### Supprimé
- **front/dropdown.form.php** - Reste inutilisé du plugin exemple
- **front/dropdown.php** - Reste inutilisé du plugin exemple
- Fichiers non nécessaires pour le fonctionnement du plugin

### 📚 Documentation

#### Ajouté
- **Documentation complète du système de gabarits de mail**
  - Explication de l'architecture `NotificationTargetTonerAlert`
  - Guide de personnalisation des templates dans GLPI
  - Liste exhaustive des balises disponibles
  - Exemples de templates HTML et texte

### 🔧 Technique
- Mise à jour de tous les en-têtes de fichiers
- Uniformisation des copyrights et licences
- Nettoyage de l'arborescence du projet

---

## [1.0.2] - 2025-10-31

### 🎉 Version finale complète

Cette version représente la première version stable et fonctionnelle du plugin SNMP Toner Alerts.

### ✨ Fonctionnalités principales

#### Surveillance SNMP
- **Monitoring automatique** des niveaux de toners via données SNMP remontées par NetInventory
- **Exploitation de la table** `glpi_printers_cartridgeinfos` pour récupérer les niveaux en temps réel
- **Support multi-marques** : compatible avec toutes les imprimantes remontant des données SNMP
- **Types de consommables supportés** :
  - Toners : noir, cyan, magenta, jaune
  - Drums (tambours) : noir, cyan, magenta, jaune

#### Système d'alertes progressives
- **Gestion par toner individuel** : chaque consommable est suivi indépendamment
- **Alertes journalières** : envoyées le matin (08h00 par défaut) jusqu'à 3 fois maximum par toner
- **Récapitulatif hebdomadaire** : vendredi midi (12h00 par défaut) pour les alertes persistantes (>3)
- **Compteur intelligent** : indication "Alerte X/3" dans chaque notification
- **Reset automatique** : remise à zéro des compteurs quand le niveau redevient normal

#### CronTasks automatiques
- **CheckTonerLevels** : vérifie les niveaux 4 fois par jour (toutes les 6h par défaut)
  - Met à jour l'état d'alerte de chaque toner
  - Incrémente ou reset les compteurs selon le seuil
  - Enregistre l'historique dans la base de données
- **SendDailyAlerts** : envoie les alertes quotidiennes (matin)
  - Vérifie les toners en alerte avec compteur ≤ 3
  - Génère des notifications personnalisées par imprimante
  - Incrémente le compteur après envoi
- **SendWeeklyRecap** : récapitulatif hebdomadaire (vendredi midi)
  - Liste toutes les imprimantes avec compteur > 3
  - Vérifie que les niveaux sont toujours en alerte avant envoi

#### Interface de configuration
- **Seuil d'alerte personnalisable** (% minimal avant déclenchement)
- **Destinataires emails** : configuration libre des adresses
- **Fréquence de vérification** : ajustable (heures entre chaque check)
- **Horaires configurables** : pour alertes quotidiennes et hebdomadaires
- **Activation/désactivation** : contrôle indépendant des alertes daily/weekly

#### Gestion des imprimantes
- **Système d'exclusion** : possibilité d'exclure des imprimantes problématiques
- **Interface dédiée** : ajout/suppression via dropdown
- **Données aberrantes** : filtrage des imprimantes remontant des valeurs incorrectes

#### Notifications intelligentes
- **Balises dynamiques** disponibles :
  - `##toner.threshold##` : Seuil configuré (%)
  - `##toner.count##` : Nombre d'imprimantes en alerte
  - `##toner.alert_type##` : Type d'alerte (Quotidienne/Hebdomadaire)
  - `##printer.name##` : Nom de l'imprimante
  - `##printer.location##` : Localisation complète
  - `##printer.model##` : Modèle d'imprimante
  - `##toner.property##` : Type technique (tonerblack, tonercyan...)
  - `##toner.property_label##` : Label lisible (Toner noir, Toner cyan...)
  - `##toner.level##` : Niveau actuel (%)
  - `##toner.reference##` : Référence de cartouche associée
  - `##toner.alert_number##` : Compteur d'alerte (ex: "Alerte 2/3")
- **Boucles FOREACH** : support natif pour lister imprimantes et toners
- **Templates modifiables** : personnalisation totale via interface GLPI

#### Architecture base de données
- **glpi_plugin_snmptoneralerts_excludedprinters**
  - `id` : Identifiant unique
  - `printers_id` : Référence vers glpi_printers
  - `date_creation` : Date d'exclusion
- **glpi_plugin_snmptoneralerts_states**
  - `id` : Identifiant unique
  - `printers_cartridgeinfos_id` : Référence vers cartridge SNMP
  - `current_value` : Niveau actuel (%)
  - `is_alert` : État d'alerte (0/1)
  - `alert_count` : Nombre d'alertes envoyées
  - `first_alert_date` : Date première alerte
  - `last_alert_date` : Date dernière alerte
  - `date_mod` : Dernière modification
- **glpi_plugin_snmptoneralerts_alerts**
  - `id` : Identifiant unique
  - `printers_cartridgeinfos_id` : Référence cartridge
  - `alert_type` : daily/weekly
  - `toner_level` : Niveau au moment de l'alerte
  - `alert_number` : Numéro dans la séquence
  - `date_creation` : Date de l'alerte

#### Mapping automatique
- **Correspondance SNMP → Références cartouches** :
  - Analyse du champ `comment` de `glpi_cartridgeitems`
  - Recherche de mots-clés : black/noir/bk, cyan/c, magenta/m, yellow/jaune/y, drum
  - Association automatique via `glpi_cartridgeitems_printermodels`
  - Fallback si aucune correspondance trouvée

### 📚 Documentation
- **README.md** : présentation complète avec badges, features, architecture
- **INSTALL.md** : guide d'installation détaillé (408 lignes)
  - Prérequis détaillés
  - Instructions d'installation pas à pas
  - Configuration des CronTasks
  - Templates de notifications
  - Troubleshooting complet
- **CHANGELOG.md** : historique détaillé des versions
- **LICENSE** : Licence GPLv3 complète
- **version.json** : métadonnées structurées du plugin

### 🌍 Localisation
- **Traduction française complète** (fr_FR.po)
- **Fichier POT** pour traductions futures (snmptoneralerts.pot)
- **Compilation MO** : script automatique (compile_mo.php)

### 🔧 Technique
- **Architecture PSR-4** : autoloading moderne
- **Namespace** : `GlpiPlugin\Snmptoneralerts`
- **Classes principales** :
  - `Config` : Interface de configuration
  - `TonerMonitor` : Logique métier + CronTasks
  - `NotificationTargetTonerAlert` : Templates notifications
  - `ItemForm` : Gestion formulaires
- **Composer** : dépendances et autoload configurés
- **Compatibilité** : GLPI 11.0.0+ / PHP 8.2+

### 🔐 Licence et auteur
- **Auteur** : SpyKeeR
- **Licence** : GPL-3.0-or-later (GPLv3+)
- **Repository** : https://github.com/SpyKeeR/snmptoneralerts
- **Copyright** : © 2025 SpyKeeR

### 📊 Statistiques
- **Fichiers PHP** : 7 fichiers
- **Lignes de code** : ~1500 lignes
- **Tables BDD** : 3 tables dédiées
- **CronTasks** : 3 tâches automatiques
- **Balises notifications** : 12+ balises dynamiques
