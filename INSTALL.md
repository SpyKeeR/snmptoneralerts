# 📦 Guide d'installation et de configuration - SNMP Toner Alerts

> **Documentation complète pour l'installation, la configuration avancée et le dépannage du plugin**

---

## 📋 Table des matières

1. [⚠️ Nouveautés v1.1.0](#️-nouveautés-v110)
2. [Prérequis](#-prérequis)
3. [Installation](#-installation)
4. [Configuration](#️-configuration)
5. [Actions automatiques](#-actions-automatiques)
6. [Templates de notifications](#-templates-de-notifications)
7. [Architecture technique](#️-architecture-technique)
8. [Dépannage](#-dépannage)

---

## ⚠️ Nouveautés v1.1.0

> **Version initiale avec architecture complète**

### ✨ Fonctionnalités principales

1. **Message-ID RFC-compliant** 
   - Les Message-ID des emails utilisent maintenant des points au lieu de backslashes
   - Format : `GlpiPlugin.Snmptoneralerts.TonerAlert.{id}.{timestamp}@{domain}`
   - Compatible avec tous les serveurs de messagerie

2. **Affichage nom + référence des cartouches**
   - Les notifications affichent maintenant : `HP 305A Black (Ref: CE410A)`
   - Au lieu de seulement : `Ref: CE410A`
   - Plus facile pour identifier et commander les bonnes cartouches

3. **Support des cartouches tri-color**
   - Fallback automatique pour les cartouches 3 couleurs en 1
   - Si `cyan`, `magenta` ou `yellow` non trouvés → cherche `tri-color`
   - Simplifie la configuration pour les imprimantes avec cartouche multicolore

4. **Liens de configuration rapide**
   - 3 boutons d'accès direct depuis la page de configuration :
     - ⚙️ Activer/Configurer les CronTasks
     - 📧 Configurer les notifications
     - 📋 Modèles de notifications

### 🎨 Améliorations

1. **Configuration simplifiée**
   - Seulement 2 paramètres métier essentiels (seuil, max_alerts)
   - Configuration claire sans duplication avec GLPI
   - Liens de configuration rapide intégrés

2. **CronTasks activés par défaut**
   - Les 3 CronTasks sont activés automatiquement à l'installation
   - Plus besoin d'activation manuelle
   - Gain de temps lors de la première configuration

3. **Nettoyage complet à la désinstallation**
   - Supprime toutes les traces dans `glpi_configs`
   - Supprime les notifications et templates associés
   - Aucun résidu dans la base de données

### 🐛 Corrections de bugs

1. **Double symbole % corrigé**
   - Les notifications affichaient `20%%` au lieu de `20%`
   - Maintenant corrigé dans le code

2. **Nombre d'imprimantes corrigé**
   - Affichage correct du nombre d'imprimantes concernées dans les logs

3. **Liaison template/notification**
   - Association automatique entre templates et notifications
   - Notifications fonctionnelles dès l'installation

### 📖 Documentation

- **CHANGELOG.md** : Historique complet des versions
- **README.md** : Configuration de base mise à jour avec nouveau workflow
- **INSTALL.md** : Ce guide, entièrement revu pour la v1.1.0
- **version.json** : Métadonnées enrichies avec changelog structuré

---

## 🔧 Prérequis

### Version minimale requise

| Composant | Version | Vérification |
|-----------|---------|--------------|
| **GLPI** | 11.0.0+ | Administration → À propos |
| **PHP** | 8.2+ | `php -v` |
| **MySQL / MariaDB** | - | Via GLPI |

### Extensions PHP requises

```bash
# Vérifier les extensions installées
php -m | grep -E 'mysqli|pdo_mysql|mbstring|json'

# Si manquantes, installer:
sudo apt-get install php8.2-mysql php8.2-mbstring
sudo systemctl restart apache2  # ou php-fpm
```

### Plugins GLPI requis

| Plugin | Rôle | État |
|--------|------|------|
| **NetDiscovery** | Découverte réseau | ✅ Actif |
| **NetInventory** | Inventaire SNMP | ✅ Actif |

**Vérifier l'inventaire SNMP** :

```sql
-- Doit retourner des enregistrements
SELECT p.name, c.property, c.value
FROM glpi_printers p
JOIN glpi_printers_cartridgeinfos c ON c.printers_id = p.id
LIMIT 10;
```

Si aucun résultat :
1. Vérifier que NetInventory est configuré avec les credentials SNMP
2. Relancer un inventaire sur une imprimante test
3. Vérifier que l'imprimante supporte SNMP v1/v2c/v3

### Permissions système

```bash
# Le serveur Web doit pouvoir lire le plugin
chown -R www-data:www-data /var/www/html/glpi/plugins/snmptoneralerts
chmod -R 755 /var/www/html/glpi/plugins/snmptoneralerts

# Vérifier l'utilisateur du serveur Web
ps aux | grep -E 'apache|nginx|php-fpm' | head -1
```

---

## 📥 Installation

### Méthode 1 : Téléchargement manuel (recommandé)

**Depuis l'interface GitHub :**

1. Aller sur [https://github.com/SpyKeeR/snmptoneralerts](https://github.com/SpyKeeR/snmptoneralerts)
2. Cliquer sur le bouton **Code** (vert)
3. Sélectionner **Download ZIP**
4. Sauvegarder `snmptoneralerts-main.zip` sur votre ordinateur

**Installation sur le serveur :**

```bash
# 1. Transférer le fichier ZIP sur le serveur (via SCP, FTP, ou autre)
# Exemple avec SCP :
scp snmptoneralerts-main.zip user@serveur:/tmp/

# 2. Se connecter au serveur et extraire
cd /var/www/html/glpi/plugins
unzip /tmp/snmptoneralerts-main.zip

# 3. Renommer le dossier (retirer le suffixe -main ou -master)
mv snmptoneralerts-main snmptoneralerts

# 4. Permissions
chown -R www-data:www-data snmptoneralerts
chmod -R 755 snmptoneralerts

# 5. Vérifier la structure
ls -la snmptoneralerts/
# Doit contenir: setup.php, hook.php, src/, front/, locales/
```

**Alternative : Téléchargement direct depuis le serveur**

```bash
# Télécharger directement l'archive ZIP depuis GitHub
cd /var/www/html/glpi/plugins
wget https://github.com/SpyKeeR/snmptoneralerts/archive/refs/heads/main.zip -O snmptoneralerts.zip

# Extraire et renommer
unzip snmptoneralerts.zip
mv snmptoneralerts-main snmptoneralerts

# Permissions
chown -R www-data:www-data snmptoneralerts
chmod -R 755 snmptoneralerts
```

### Méthode 2 : Git (recommandé pour les développeurs)

```bash
# 1. Cloner le dépôt
cd /var/www/html/glpi/plugins
git clone https://github.com/SpyKeeR/snmptoneralerts.git

# 2. Installer les dépendances (si Composer)
cd snmptoneralerts
composer install --no-dev --optimize-autoloader

# 3. Permissions
cd ..
chown -R www-data:www-data snmptoneralerts
chmod -R 755 snmptoneralerts
```

### Activation du plugin

1. Aller dans **Configuration → Plugins**
2. Localiser "SNMP Toner Alerts" dans la liste
3. **Statut** : Nouveau → Cliquer sur **Installer**
4. **Statut** : Non actif → Cliquer sur **Activer**

### Vérification de l'installation

**Vérifier les tables créées** :

```sql
-- Doit retourner 4 tables
SHOW TABLES LIKE 'glpi_plugin_snmptoneralerts%';

-- Structure attendue:
-- glpi_plugin_snmptoneralerts_alerts
-- glpi_plugin_snmptoneralerts_configs
-- glpi_plugin_snmptoneralerts_excludedprinters
-- glpi_plugin_snmptoneralerts_states
```

**Vérifier les CronTasks** :

```sql
SELECT name, state FROM glpi_crontasks WHERE itemtype = 'PluginSnmptonealertsTonerMonitor';
```

**Vérifier l'accès à la configuration** :

1. Menu GLPI → **Configuration**
2. Vérifier présence de **SNMP Toner Alerts** dans la section "Plugins"

---

## ⚙️ Configuration

### Configuration de base (v1.1.0 - Simplifiée)

Le plugin a été **grandement simplifié** dans la version 1.1.0. La configuration se fait maintenant en **2 paramètres** + accès rapide aux Actions automatiques.

#### Étape 1 : Configuration des paramètres

1. Aller dans **Configuration → SNMP Toner Alerts**

2. **Seuil d'alerte (%)** :
   - Valeur par défaut : `20%`
   - Plage recommandée : `15%` à `25%`
   - ⚠️ Trop bas = trop d'alertes / Trop haut = risque de panne

3. **Nombre maximum d'alertes quotidiennes** :
   - Valeur par défaut : `3`
   - Après dépassement → passage en récapitulatif hebdomadaire
   - Recommandé : `3` à `5`

4. Cliquer sur **Enregistrer**

#### Étape 2 : Utiliser les liens de configuration rapide

La page de configuration affiche maintenant **3 boutons d'accès rapide** :

1. **⚙️ Activer/Configurer les CronTasks**
   - Ouvre directement la liste des Actions automatiques du plugin
   - Les 3 CronTasks (CheckTonerLevels, SendDailyAlerts, SendWeeklyRecap) sont **activés par défaut**
   - Vous pouvez ajuster les fréquences et horaires si besoin

2. **📧 Configurer les notifications**
   - Ouvre la liste des notifications actives
   - Permet d'ajouter/modifier les destinataires des emails

3. **📋 Modèles de notifications**
   - Ouvre les templates d'emails
   - Permet de personnaliser le contenu des alertes

#### Philosophie de configuration

Le plugin se concentre sur les **paramètres métier spécifiques** au monitoring des toners :
- ✅ **Seuil d'alerte (%)** : Seuil métier pour déclencher les alertes
- ✅ **Nombre max d'alertes quotidiennes** : Logique de basculement journalier/hebdomadaire

Les autres paramètres (destinataires, horaires, activation) sont gérés par les **fonctionnalités natives de GLPI** :
- 📧 Destinataires → **Configuration → Notifications → Notifications**
- ⏰ Horaires → **Configuration → Actions automatiques** (CronTasks)
- ✅ Activation → CronTasks activés par défaut

**💡 Avantage** : Configuration claire, sans duplication avec GLPI

### Vérification de la configuration

```sql
-- Voir la configuration active
SELECT * FROM glpi_plugin_snmptoneralerts_configs ORDER BY id DESC LIMIT 1;
```

### Exclusion d'imprimantes

Certaines imprimantes remontent des données SNMP incorrectes (100% constant, valeurs négatives, etc.).

**Via l'interface** :

1. **Configuration → SNMP Toner Alerts** → Section "Gestion des imprimantes exclues"
2. **Imprimante** : Sélectionner dans la liste déroulante
3. **Raison** : Exemple "Données SNMP aberrantes" ou "Imprimante hors service"
4. Cliquer sur **Ajouter**

**Via SQL (si besoin)** :

```sql
-- Lister les imprimantes avec données SNMP
SELECT p.id, p.name
FROM glpi_printers p
JOIN glpi_printers_cartridgeinfos c ON c.printers_id = p.id
GROUP BY p.id;

-- Exclure une imprimante (ID 42)
INSERT INTO glpi_plugin_snmptoneralerts_excludedprinters (printers_id, reason)
VALUES (42, 'Données SNMP incorrectes');

-- Voir les exclusions
SELECT e.id, p.name, e.reason, e.excluded_at
FROM glpi_plugin_snmptoneralerts_excludedprinters e
JOIN glpi_printers p ON p.id = e.printers_id;
```

### Gestion des cartouches et références (v1.1.0 - Amélioré)

Le plugin affiche automatiquement les **noms et références de cartouches** dans les notifications en les associant aux propriétés SNMP.

**📋 Format d'affichage dans les notifications** :

```
- Toner noir: 19% (HP 305A Black (Ref: CE410A)) [Alerte 2/3]
- Toner cyan: 10% (HP 305 Tri-color (Ref: CE411A)) [Alerte 1/3]
- Toner magenta: 8% (Non défini) [Alerte 3/3]
```

#### Configuration des références

1. Aller dans **Gestion → Modèles d'imprimantes**
2. Sélectionner le modèle (ex: "HP LaserJet Pro 400")
3. Onglet **Cartouches compatibles** → Ajouter les références

4. Éditer chaque cartouche dans **Gestion → Cartouches** :
   - Champ **Référence** : `CF400X`
   - Champ **Nom** : `HP 305A Black`
   - Champ **Commentaire** : Ajouter la couleur pour le mapping :
     * `black` ou `noir` → Toner noir
     * `cyan` → Toner cyan
     * `magenta` → Toner magenta
     * `yellow` ou `jaune` → Toner jaune
     * `tri-color` ou `tricolor` ou `couleur` → Cartouche multicolore (fallback)
     * `drum black` → Bloc image noir

#### Mapping SNMP → Cartouches (avec fallback tri-color)

Le plugin utilise la correspondance suivante :

| Propriété SNMP | Mots-clés recherchés (priorité) | Fallback |
|----------------|----------------------------------|----------|
| `tonerblack` | black, noir, bk | - |
| `tonercyan` | cyan, c | tri-color, tricolor, couleur |
| `tonermagenta` | magenta, m | tri-color, tricolor, couleur |
| `toneryellow` | yellow, jaune, y | tri-color, tricolor, couleur |
| `drumblack` | drum black, drum noir | - |
| `drumcyan` | drum cyan | drum tri-color |
| `drummagenta` | drum magenta | drum tri-color |
| `drumyellow` | drum yellow, drum jaune | drum tri-color |

**💡 Astuce** : Si votre imprimante utilise une cartouche **tri-color** (3 couleurs en 1), ajoutez `tri-color` dans le commentaire. Le plugin l'utilisera automatiquement pour les toners cyan, magenta et yellow.

#### Vérifier les associations

```sql
-- Voir les cartouches compatibles avec références
SELECT 
    pm.name AS modele,
    ci.name AS cartouche,
    ci.ref AS reference,
    ci.comment AS mapping
FROM glpi_printermodels pm
JOIN glpi_cartridgeitems_printermodels cpm ON cpm.printermodels_id = pm.id
JOIN glpi_cartridgeitems ci ON ci.id = cpm.cartridgeitems_id
WHERE ci.comment IS NOT NULL AND ci.comment != ''
ORDER BY pm.name, ci.name;

-- Voir les propriétés SNMP remontées pour une imprimante
SELECT p.name, c.property, c.value
FROM glpi_printers p
JOIN glpi_printers_cartridgeinfos c ON c.printers_id = p.id
WHERE p.id = 42;  -- Remplacer par l'ID de votre imprimante
```

---

## 🔄 Actions automatiques

### Vue d'ensemble des CronTasks

Le plugin utilise **3 tâches automatiques** qui sont **activées par défaut** lors de l'installation :

| CronTask | Rôle | État initial | Fréquence par défaut | Horaire recommandé |
|----------|------|--------------|---------------------|-------------------|
| **CheckTonerLevels** | Vérifie les niveaux SNMP et met à jour les états/compteurs | ✅ Actif | Toutes les 6h (21600s) | 00:00, 06:00, 12:00, 18:00 |
| **SendDailyAlerts** | Envoie alertes pour toners avec compteur ≤ 3 | ✅ Actif | Quotidien (86400s) | 08:00 |
| **SendWeeklyRecap** | Envoie récap pour toners avec compteur > 3 | ✅ Actif | Hebdomadaire (604800s) | Vendredi 12:00 |

> **💡 Nouveauté v1.1.0** : Les CronTasks sont maintenant activés automatiquement à l'installation. Vous n'avez plus besoin de les activer manuellement, seulement de les configurer si vous souhaitez modifier les horaires.

### Configuration dans GLPI (optionnelle)

Les CronTasks fonctionnent immédiatement avec les paramètres par défaut. Si vous souhaitez **personnaliser les horaires** :

1. Aller dans **Configuration → Actions automatiques** (ou utiliser le bouton "⚙️ Activer/Configurer les CronTasks" depuis la page de configuration du plugin)

2. Rechercher "Toner" ou filtrer par plugin "SNMP Toner Alerts"

3. Pour chaque tâche, vous pouvez modifier :

#### CheckTonerLevels

- **État** : ✅ Actif (par défaut)
- **Mode d'exécution** : CLI (recommandé) ou GLPI
- **Fréquence** : `21600` secondes (6h) - Ajustable selon vos besoins
- **État de l'exécution** : À planifier

💡 **Recommandation** : Conserver 6h pour un bon équilibre entre réactivité et charge serveur.

#### SendDailyAlerts

- **État** : ✅ Actif (par défaut)
- **Mode d'exécution** : CLI (via cron système recommandé)
- **Fréquence** : `86400` secondes (24h)
- **État de l'exécution** : À planifier

💡 **Recommandation** : Configurer un horaire précis via crontab (ex: 08:00) pour garantir l'envoi à heure fixe.

#### SendWeeklyRecap

- **État** : ✅ Actif (par défaut)
- **Mode d'exécution** : CLI (via cron système recommandé)
- **Fréquence** : `604800` secondes (7 jours)
- **État de l'exécution** : À planifier

💡 **Recommandation** : Configurer via crontab pour envoi le vendredi à 12:00.

### Configuration Cron système (recommandé)

Pour une exécution **précise et fiable**, utiliser le crontab système.

**Éditer le crontab** :

```bash
# En tant que root ou avec sudo
crontab -e
```

**Ajouter les lignes suivantes** :

```bash
# SNMP Toner Alerts - Vérification des niveaux toutes les 6 heures
0 */6 * * * /usr/bin/php /var/www/html/glpi/front/cron.php --force CheckTonerLevels >> /var/log/glpi/cron.log 2>&1

# SNMP Toner Alerts - Alertes journalières à 8h00
0 8 * * * /usr/bin/php /var/www/html/glpi/front/cron.php --force SendDailyAlerts >> /var/log/glpi/cron.log 2>&1

# SNMP Toner Alerts - Récapitulatif hebdomadaire vendredi à 12h00
0 12 * * 5 /usr/bin/php /var/www/html/glpi/front/cron.php --force SendWeeklyRecap >> /var/log/glpi/cron.log 2>&1
```

**Variantes** :

```bash
# Avec le binaire GLPI CLI (si disponible)
0 */6 * * * /usr/bin/php /var/www/html/glpi/bin/console glpi:cron:task CheckTonerLevels

# Avec authentification utilisateur spécifique
0 8 * * * /usr/bin/php /var/www/html/glpi/front/cron.php --force SendDailyAlerts --uid=2

# Avec verbosité pour debug
0 12 * * 5 /usr/bin/php /var/www/html/glpi/front/cron.php --force SendWeeklyRecap -vvv
```

**Vérifier le crontab** :

```bash
# Lister les tâches cron
crontab -l | grep -i snmp

# Tester l'exécution manuelle
/usr/bin/php /var/www/html/glpi/front/cron.php --force CheckTonerLevels

# Vérifier les logs
tail -f /var/log/glpi/cron.log
```

### Exécution manuelle (test)

**Via l'interface GLPI** :

1. **Configuration → Actions automatiques**
2. Cliquer sur la tâche (ex: "SendDailyAlerts")
3. Bouton **Exécuter** en haut à droite
4. Vérifier le résultat dans l'historique

**Via CLI** :

```bash
# Forcer l'exécution immédiate
php /var/www/html/glpi/front/cron.php --force CheckTonerLevels

# Verbose pour debug
php /var/www/html/glpi/front/cron.php --force SendDailyAlerts -vvv

# Toutes les tâches en attente
php /var/www/html/glpi/front/cron.php
```

### Surveillance des CronTasks

**Vérifier l'historique** :

```sql
-- Dernières exécutions
SELECT 
    c.name,
    c.lastrun,
    c.state,
    cl.date AS dernier_log,
    cl.state AS etat_log
FROM glpi_crontasks c
LEFT JOIN glpi_crontasklogs cl ON cl.crontasks_id = c.id
WHERE c.itemtype = 'PluginSnmptonealertsTonerMonitor'
ORDER BY cl.date DESC
LIMIT 10;
```

**Logs GLPI** :

```bash
# Erreurs générales
tail -f /var/log/glpi/php-errors.log

# Logs cron
tail -f /var/log/glpi/cron.log

# SQL en cas d'erreur
tail -f /var/log/glpi/sql-errors.log
```

---

## 📧 Templates de notifications

### Fonctionnement

Le plugin utilise le **système de notifications natif de GLPI** :

1. **Événements** déclenchés par le plugin :
   - `toner_alert_daily` : Alerte journalière
   - `toner_alert_weekly` : Récapitulatif hebdomadaire

2. **Notifications GLPI** associent événements → templates → destinataires

3. **Templates** sont modifiables via l'interface sans toucher au code

### Accès aux templates

**Configuration → Notifications → Templates de notifications**

Rechercher :
- `SNMP Toner Alert - Daily`
- `SNMP Toner Alert - Weekly`

### Balises disponibles

Le plugin injecte les balises suivantes dans les templates :

| Balise | Type | Description | Exemple |
|--------|------|-------------|---------|
| `##toner.threshold##` | Scalaire | Seuil d'alerte configuré (%) | `20` |
| `##toner.count##` | Scalaire | Nombre d'imprimantes en alerte | `5` |
| `##toner.alert_type##` | Scalaire | Type d'alerte | `Journalière` / `Hebdomadaire` |
| `##PRINTERS##` | Bloc | Liste détaillée des imprimantes et toners | Voir structure ci-dessous |

### Structure de la balise ##PRINTERS## (v1.1.0 - Format amélioré)

Cette balise contient un **bloc de texte formaté** avec toutes les imprimantes en alerte :

```
Imprimante: HP-LaserJet-Pro-400-RDC
Localisation: Bâtiment A > RDC > Accueil
Modèle: HP LaserJet Pro 400 color M451dn

Toners concernés:
  - Toner noir: 19% (HP 305A Black (Ref: CE410A)) [Alerte 2/3]
  - Toner cyan: 15% (HP 305 Tri-color (Ref: CE411A)) [Alerte 2/3]
  - Toner magenta: 18% (Non défini) [Alerte 1/3]

─────────────────────────────────────

Imprimante: Xerox-WorkCentre-5335-Etage1
Localisation: Bâtiment B > Étage 1 > Bureau
Modèle: Xerox WorkCentre 5335

Toners concernés:
  - Toner noir: 8% (Xerox 006R01606 Black (Ref: 006R01606)) [Alerte 5/3 - Récap hebdomadaire]

─────────────────────────────────────
```

**Détails de la structure (nouveauté v1.1.0)** :

- **Nom imprimante** : `glpi_printers.name`
- **Localisation** : Chemin complet (Entité > Lieu > Sous-lieu)
- **Modèle** : `glpi_printermodels.name`
- **Toners** : Format amélioré → `Couleur: Niveau% (Nom cartouche (Ref: Référence)) [Compteur]`
  - **Nom de la cartouche** : `glpi_cartridgeitems.name` (ex: "HP 305A Black")
  - **Référence** : `glpi_cartridgeitems.ref` (ex: "CE410A")
  - **Fallback** : Si non défini → affiche "Non défini"
  - **Tri-color** : Support des cartouches multicolores (cyan/magenta/yellow partagent la même ref)
- **Séparateurs** : Lignes de tirets pour lisibilité

> **💡 Nouveauté v1.1.0** : Les notifications affichent maintenant **à la fois le nom et la référence** des cartouches pour faciliter la commande. Si une cartouche n'est pas associée au modèle dans GLPI, "Non défini" sera affiché.

### Exemple de template simple (texte)

**Sujet** :
```
[GLPI] Alertes toners - ##toner.alert_type##
```

**Corps (texte brut)** :
```
Bonjour,

##toner.count## imprimante(s) ont des toners en dessous du seuil d'alerte (##toner.threshold##%).

Type d'alerte: ##toner.alert_type##

──────────────────────────────────────
##PRINTERS##
──────────────────────────────────────

Merci de vérifier les niveaux et de commander les cartouches nécessaires.

---
SNMP Toner Alerts pour GLPI
Ce message est envoyé automatiquement.
```

### Exemple de template avancé (HTML)

**Corps (HTML)** :

```html
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f4f4f4;
            margin: 0;
            padding: 20px;
        }
        .container {
            max-width: 800px;
            margin: 0 auto;
            background-color: #ffffff;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            text-align: center;
        }
        .header h1 {
            margin: 0;
            font-size: 28px;
        }
        .alert-info {
            background-color: #fff3cd;
            border-left: 4px solid #ffc107;
            padding: 20px;
            margin: 20px 30px;
            border-radius: 4px;
        }
        .alert-info p {
            margin: 5px 0;
            font-size: 16px;
        }
        .content {
            padding: 30px;
        }
        .printer-block {
            background-color: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 6px;
            padding: 20px;
            margin-bottom: 20px;
        }
        .printer-name {
            font-size: 20px;
            font-weight: bold;
            color: #495057;
            margin-bottom: 10px;
        }
        .printer-details {
            color: #6c757d;
            margin: 5px 0;
            font-size: 14px;
        }
        .toner-list {
            margin-top: 15px;
            padding-left: 20px;
        }
        .toner-item {
            background-color: #ffffff;
            border-left: 3px solid #dc3545;
            padding: 10px;
            margin: 8px 0;
            border-radius: 4px;
        }
        .footer {
            background-color: #343a40;
            color: #ffffff;
            text-align: center;
            padding: 20px;
            font-size: 14px;
        }
        .footer a {
            color: #80bdff;
            text-decoration: none;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🖨️ SNMP Toner Alerts</h1>
            <p>Notification automatique - ##toner.alert_type##</p>
        </div>

        <div class="alert-info">
            <p><strong>📊 Seuil configuré:</strong> ##toner.threshold##%</p>
            <p><strong>🚨 Nombre d'imprimantes concernées:</strong> ##toner.count##</p>
        </div>

        <div class="content">
            <h2>Détails des alertes</h2>
            <pre style="font-family: inherit; white-space: pre-wrap; background: #f8f9fa; padding: 15px; border-radius: 4px;">##PRINTERS##</pre>

            <p style="margin-top: 30px; padding: 15px; background-color: #e9ecef; border-radius: 4px;">
                <strong>Actions recommandées:</strong>
                <ul>
                    <li>Vérifier les niveaux réels sur les imprimantes</li>
                    <li>Commander les cartouches nécessaires</li>
                    <li>Prévoir le remplacement selon les délais de livraison</li>
                </ul>
            </p>
        </div>

        <div class="footer">
            <p>Ce message est envoyé automatiquement par le plugin <strong>SNMP Toner Alerts</strong></p>
            <p>Ne pas répondre à cet email</p>
        </div>
    </div>
</body>
</html>
```

### Modification des templates

**Via l'interface** :

1. **Configuration → Notifications → Templates de notifications**
2. Cliquer sur le template (ex: "SNMP Toner Alert - Daily")
3. Onglet **Traductions**
4. Sélectionner la langue (Français / Anglais)
5. Modifier :
   - **Sujet de l'email**
   - **Corps de l'email** (Texte et/ou HTML)
6. **Sauvegarder**

**Prévisualisation** :

Utiliser **Exécuter** sur le CronTask pour recevoir un email de test.

### Association notifications → templates

**Configuration → Notifications → Notifications**

Rechercher "SNMP Toner Alert" :

| Notification | Événement | Template |
|--------------|-----------|----------|
| SNMP Toner Alert - Daily | `toner_alert_daily` | SNMP Toner Alert - Daily |
| SNMP Toner Alert - Weekly | `toner_alert_weekly` | SNMP Toner Alert - Weekly |

**Vérifier** :
- Statut : **Actif** ✅
- Destinataires : Utilise emails de la config plugin ou **ajouter manuellement** ici
- Mode : Courrier électronique

### Test des notifications

**Depuis l'interface** :

1. **Configuration → Actions automatiques**
2. Cliquer sur "SendDailyAlerts"
3. Bouton **Exécuter**
4. Vérifier réception email

**Depuis CLI** :

```bash
# Forcer envoi des alertes
php /var/www/html/glpi/front/cron.php --force SendDailyAlerts

# Vérifier les logs
tail -f /var/log/glpi/php-errors.log
```

**Vérification SQL** :

```sql
-- Voir les notifications en file d'attente
SELECT * FROM glpi_queuednotifications WHERE itemtype = 'PluginSnmptonealertsNotificationTargetTonerAlert';

-- Historique des envois
SELECT * FROM glpi_notificationemails ORDER BY id DESC LIMIT 10;
```

### Personnalisation avancée

**Créer plusieurs templates pour différents destinataires** :

1. Dupliquer un template existant
2. Modifier le contenu (ex: version courte pour SMS, version longue pour email)
3. Créer plusieurs notifications associées au même événement
4. Ajouter des conditions (ex: par entité)

**Utiliser des conditions** :

Dans **Configuration → Notifications → Notifications** :
- Ajouter des critères de destination (Entité, Profil, Groupe)
- Permet d'envoyer des templates différents selon le contexte

---

## 🏗️ Architecture technique

### Schéma de la base de données

```
┌──────────────────────┐
│  glpi_printers       │
│  (table native GLPI) │
└──────────┬───────────┘
           │
           │ 1:N
           ▼
┌──────────────────────────────────┐
│ glpi_printers_cartridgeinfos     │
│ (NetInventory SNMP)              │
│ ─────────────────────────────── │
│ printers_id                      │
│ property (tonerblack, cyan...)   │
│ value (15%)                      │
└──────────┬───────────────────────┘
           │
           │ Lecture par CheckTonerLevels
           ▼
┌────────────────────────────────────────┐
│ glpi_plugin_snmptoneralerts_states     │
│ (États et compteurs)                   │
│ ──────────────────────────────────────│
│ printers_id, property_name             │
│ current_level, alert_count             │
│ is_alert, last_checked, last_alerted   │
└──────────┬─────────────────────────────┘
           │
           │ Historique
           ▼
┌────────────────────────────────────────┐
│ glpi_plugin_snmptoneralerts_alerts     │
│ (Historique des alertes)               │
│ ──────────────────────────────────────│
│ printers_id, property_name             │
│ alert_level, alert_type, notified_at   │
└────────────────────────────────────────┘

┌────────────────────────────────────────────┐
│ glpi_plugin_snmptoneralerts_configs        │
│ (Configuration)                            │
│ ───────────────────────────────────────── │
│ threshold, recipients, max_daily_alerts    │
└────────────────────────────────────────────┘

┌─────────────────────────────────────────────────┐
│ glpi_plugin_snmptoneralerts_excludedprinters    │
│ (Imprimantes exclues)                           │
│ ────────────────────────────────────────────── │
│ printers_id, reason, excluded_at                │
└─────────────────────────────────────────────────┘
```

### Flux de données

```
1️⃣ NetInventory (SNMP)
   ↓
   Collecte données → glpi_printers_cartridgeinfos
   ↓
2️⃣ CheckTonerLevels (CronTask toutes les 6h)
   ↓
   Lecture cartridgeinfos
   ↓
   Comparaison avec seuil
   ↓
   Mise à jour states (is_alert, alert_count)
   ↓
   Insertion historique alerts
   ↓
3️⃣ SendDailyAlerts (CronTask quotidien 08h)
   ↓
   Lecture states WHERE is_alert=1 AND alert_count<=3
   ↓
   Génération ##PRINTERS##
   ↓
   QueuedNotification → Email
   ↓
4️⃣ SendWeeklyRecap (CronTask vendredi 12h)
   ↓
   Lecture states WHERE is_alert=1 AND alert_count>3
   ↓
   Génération ##PRINTERS##
   ↓
   QueuedNotification → Email
```

### Classe principale : TonerMonitor

**Fichier** : `src/TonerMonitor.php`

**Méthodes clés** :

| Méthode | Rôle |
|---------|------|
| `checkTonerLevels()` | Vérifie tous les toners, met à jour états et compteurs |
| `sendDailyAlerts()` | Envoie alertes pour toners avec compteur ≤ max |
| `sendWeeklyRecapitulation()` | Envoie récap pour toners avec compteur > max |
| `getTonersInAlert($type)` | Récupère liste imprimantes en alerte |
| `getCartridgeReference()` | Mapping SNMP → Référence cartouche |

### Classe de notification : NotificationTargetTonerAlert

**Fichier** : `src/NotificationTargetTonerAlert.php`

**Rôle** : Génère contenu des notifications

**Méthodes clés** :

| Méthode | Rôle |
|---------|------|
| `addDataForTemplate()` | Injecte balises ##toner.*## et ##PRINTERS## |
| `formatPrintersBlock()` | Formate le bloc texte avec imprimantes et toners |
| `getEvents()` | Définit événements (toner_alert_daily, weekly) |

### PSR-4 Autoloading

**composer.json** :

```json
{
    "autoload": {
        "psr-4": {
            "GlpiPlugin\\Snmptoneralerts\\": "src/"
        }
    }
}
```

**Namespace** : `GlpiPlugin\Snmptoneralerts\`

**Classes** :
- `Config`
- `ItemForm`
- `NotificationTargetTonerAlert`
- `TonerMonitor`

### Hooks GLPI

**Fichier** : `hook.php`

| Hook | Action |
|------|--------|
| `plugin_snmptoneralerts_install()` | Création tables, notifications, crontasks |
| `plugin_snmptoneralerts_uninstall()` | Suppression tables et données |

---

## 🔍 Dépannage

### 1. Aucune alerte envoyée

**Symptômes** : Pas d'emails reçus malgré toners faibles

**Diagnostics** :

```bash
# 1. Vérifier configuration email GLPI
# Administration → Configuration → Notifications → Email
# Test: Envoyer un email de test

# 2. Vérifier CronTasks actives
mysql -u glpi -p glpi -e "SELECT name, state, lastrun FROM glpi_crontasks WHERE itemtype = 'PluginSnmptonealertsTonerMonitor';"

# 3. Vérifier états en alerte
mysql -u glpi -p glpi -e "SELECT COUNT(*) FROM glpi_plugin_snmptoneralerts_states WHERE is_alert = 1;"

# 4. Vérifier données SNMP
mysql -u glpi -p glpi -e "SELECT COUNT(*) FROM glpi_printers_cartridgeinfos;"

# 5. Consulter logs
tail -f /var/log/glpi/php-errors.log
tail -f /var/log/glpi/cron.log
```

**Solutions** :

- ✅ Activer notifications email dans GLPI
- ✅ Vérifier destinataires configurés dans plugin
- ✅ Activer et exécuter CronTasks
- ✅ Vérifier que des toners sont réellement sous seuil
- ✅ Relancer un inventaire SNMP

### 2. Données SNMP manquantes

**Symptômes** : `glpi_printers_cartridgeinfos` vide

**Diagnostics** :

```sql
-- Vérifier table vide
SELECT COUNT(*) FROM glpi_printers_cartridgeinfos;

-- Vérifier imprimantes inventoriées
SELECT COUNT(*) FROM glpi_printers WHERE is_deleted = 0;
```

**Solutions** :

1. **Vérifier NetInventory actif** :
   - Configuration → Plugins → NetInventory → Actif ✅

2. **Vérifier credentials SNMP** :
   - Configuration → Inventaire → Équipements réseau
   - Community SNMP correcte (ex: `public`)

3. **Relancer inventaire manuel** :
   ```bash
   # Via CLI NetInventory
   php /var/www/html/glpi/plugins/fusioninventory/scripts/inventory.php --snmp=192.168.1.100
   ```

4. **Vérifier SNMP sur l'imprimante** :
   ```bash
   # Tester SNMP depuis serveur GLPI
   snmpwalk -v2c -c public 192.168.1.100 1.3.6.1.2.1.43.11.1.1.9
   # Doit retourner niveaux toners
   ```

### 3. Références cartouches manquantes

**Symptômes** : Emails affichent "Réf: N/A"

**Diagnostic** :

```sql
-- Vérifier associations modèle → cartouches
SELECT 
    pm.name AS modele,
    ci.ref AS reference,
    ci.comment
FROM glpi_printermodels pm
JOIN glpi_cartridgeitems_printermodels cpm ON cpm.printermodels_id = pm.id
JOIN glpi_cartridgeitems ci ON ci.id = cpm.cartridgeitems_id;
```

**Solutions** :

1. **Ajouter cartouches au modèle** :
   - Gestion → Modèles d'imprimantes
   - Sélectionner modèle
   - Onglet "Cartouches compatibles" → Ajouter

2. **Renseigner couleur dans commentaire** :
   - Gestion → Cartouches
   - Éditer chaque cartouche
   - Champ "Commentaire" : `black`, `cyan`, `magenta`, `yellow`

### 4. Alertes en double

**Symptômes** : Plusieurs emails pour la même imprimante en quelques minutes

**Diagnostic** :

```sql
-- Voir compteurs d'alertes
SELECT p.name, s.property_name, s.alert_count, s.last_alerted
FROM glpi_plugin_snmptoneralerts_states s
JOIN glpi_printers p ON p.id = s.printers_id
WHERE s.is_alert = 1;

-- Historique récent
SELECT * FROM glpi_plugin_snmptoneralerts_alerts
WHERE notified_at > NOW() - INTERVAL 1 HOUR;
```

**Solutions** :

- ❌ Supprimer cron en double : `crontab -l | grep -i snmp`
- ❌ Désactiver mode interne GLPI si cron système utilisé
- ✅ Vérifier `last_alerted` met à jour correctement

### 5. Templates non modifiables

**Symptômes** : Bouton "Enregistrer" grisé ou erreur permissions

**Solutions** :

- ✅ Se connecter en profil **Super-Admin**
- ✅ Vérifier droits : Configuration → Profils → Super-Admin → Notifications (Lecture/Écriture)
- ✅ Vérifier que template n'est pas verrouillé (champ `is_recursive`)

### 6. Erreurs PHP

**Diagnostic** :

```bash
# Logs Apache
tail -f /var/log/apache2/error.log

# Logs PHP-FPM
tail -f /var/log/php8.2-fpm.log

# Logs GLPI
tail -f /var/log/glpi/php-errors.log
tail -f /var/log/glpi/sql-errors.log
```

**Erreurs courantes** :

| Erreur | Cause | Solution |
|--------|-------|----------|
| `Class not found` | Autoload PSR-4 | `composer dump-autoload` |
| `Table doesn't exist` | Plugin non installé | Réinstaller plugin |
| `Call to undefined method` | Version GLPI incompatible | Vérifier prérequis ≥11.0 |
| `Memory exhausted` | Trop d'imprimantes | Augmenter `memory_limit` PHP |

### 7. CronTasks ne s'exécutent pas

**Diagnostic** :

```sql
-- Voir état des tâches
SELECT name, state, frequency, lastrun FROM glpi_crontasks 
WHERE itemtype = 'PluginSnmptonealertsTonerMonitor';
```

**Solutions** :

1. **Mode CLI non configuré** :
   - Ajouter tâches cron système (voir section Actions automatiques)

2. **Mode GLPI interne** :
   - Vérifier que `php -f /var/www/html/glpi/front/cron.php` s'exécute
   - Ajouter dans crontab : `*/5 * * * * php /var/www/html/glpi/front/cron.php`

3. **Permissions** :
   ```bash
   chown www-data:www-data /var/www/html/glpi/front/cron.php
   chmod 755 /var/www/html/glpi/front/cron.php
   ```

### 8. Imprimantes exclues par erreur

**Diagnostic** :

```sql
-- Lister exclusions
SELECT e.id, p.name, e.reason, e.excluded_at
FROM glpi_plugin_snmptoneralerts_excludedprinters e
JOIN glpi_printers p ON p.id = e.printers_id;
```

**Solution** :

```sql
-- Supprimer une exclusion (ID 5)
DELETE FROM glpi_plugin_snmptoneralerts_excludedprinters WHERE id = 5;
```

Ou via interface : Configuration → SNMP Toner Alerts → Section Exclusions → Supprimer

### 9. Désinstallation / Réinstallation

**En cas de corruption** :

```bash
# 1. Désactiver le plugin
# Interface: Configuration → Plugins → SNMP Toner Alerts → Désactiver

# 2. Désinstaller (supprime tables)
# Interface: Configuration → Plugins → SNMP Toner Alerts → Désinstaller

# 3. Supprimer dossier
rm -rf /var/www/html/glpi/plugins/snmptoneralerts

# 4. Réinstaller (voir section Installation)
```

**⚠️ Attention** : Désinstaller supprime **toutes les données** (historique, exclusions, configuration)

**Sauvegarde avant désinstallation** :

```bash
mysqldump -u root -p glpi \
  glpi_plugin_snmptoneralerts_alerts \
  glpi_plugin_snmptoneralerts_configs \
  glpi_plugin_snmptoneralerts_excludedprinters \
  glpi_plugin_snmptoneralerts_states \
  > snmptoneralerts_backup_$(date +%F).sql
```

---

## 📞 Support et ressources

### Documentation

| Ressource | Lien |
|-----------|------|
| 📖 **README** | [README.md](README.md) |
| 📝 **CHANGELOG** | [CHANGELOG.md](CHANGELOG.md) |
| ⚖️ **LICENCE** | [LICENSE](LICENSE) |

### Communauté et support

| Canal | Lien |
|-------|------|
| 🐛 **Signaler un bug** | [GitHub Issues](https://github.com/SpyKeeR/snmptoneralerts/issues) |
| 💬 **Poser une question** | [GitHub Discussions](https://github.com/SpyKeeR/snmptoneralerts/discussions) |
| 🌟 **Proposer une fonctionnalité** | [GitHub Issues (Feature Request)](https://github.com/SpyKeeR/snmptoneralerts/issues/new?labels=enhancement) |

### Avant de signaler un bug

1. ✅ Vérifier que vous utilisez la dernière version
2. ✅ Consulter cette documentation (section Dépannage)
3. ✅ Rechercher dans les issues existantes
4. ✅ Préparer :
   - Version GLPI
   - Version PHP
   - Logs d'erreur (`/var/log/glpi/php-errors.log`)
   - Capture d'écran si erreur interface

---

## 📜 Licence

**GPL-3.0-or-later** - Voir fichier [LICENSE](LICENSE)

**Résumé des libertés** :

✅ **Utilisation commerciale** : Vous pouvez utiliser ce plugin dans un environnement commercial  
✅ **Modification** : Vous pouvez modifier le code source  
✅ **Distribution** : Vous pouvez redistribuer le plugin  
✅ **Usage privé** : Vous pouvez utiliser en privé

**Obligations** :

⚠️ **Divulgation de la source** : Code source doit rester accessible  
⚠️ **Licence et copyright** : Conserver les mentions de licence  
⚠️ **Indiquer les modifications** : Mentionner si le code a été modifié  
⚠️ **Même licence** : Distribuer sous GPL-3.0

---

**🎉 Merci d'utiliser SNMP Toner Alerts !**

**Développé avec ❤️ par [SpyKeeR](https://github.com/SpyKeeR)**

📅 Dernière mise à jour : 31 octobre 2025
