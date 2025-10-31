# Guide d'installation - SNMP Toner Alerts

## Table des matières

1. [Prérequis](#prérequis)
2. [Installation](#installation)
3. [Configuration](#configuration)
4. [Actions automatiques](#actions-automatiques)
5. [Templates de notifications](#templates-de-notifications)
6. [Dépannage](#dépannage)

## Prérequis

### Logiciels requis

- **GLPI**: Version 11.0.0 minimum
- **PHP**: Version 8.2 ou supérieure
- **Base de données**: MySQL/MariaDB compatible avec GLPI

### Configuration GLPI

- Plugin NetInventory installé et configuré
- NetDiscovery actif pour la découverte des imprimantes
- Remontée SNMP des imprimantes configurée
- Système de notifications GLPI fonctionnel

### Vérifications préalables

1. Vérifier que les imprimantes remontent des données SNMP:
   ```sql
   SELECT COUNT(*) FROM glpi_printers_cartridgeinfos;
   ```
   Ce nombre doit être > 0

2. Vérifier la version de PHP:
   ```bash
   php -v
   ```

## Installation

### Méthode 1: Téléchargement manuel

1. Télécharger la dernière version depuis [GitHub Releases](https://github.com/SpyKeeR/snmptoneralerts/releases)

2. Extraire l'archive dans le répertoire plugins de GLPI:
   ```bash
   cd /var/www/html/glpi/plugins
   unzip snmptoneralerts-1.0.2.zip
   mv snmptoneralerts-1.0.2 snmptoneralerts
   ```

3. Ajuster les permissions:
   ```bash
   chown -R www-data:www-data snmptoneralerts
   chmod -R 755 snmptoneralerts
   ```

### Méthode 2: Git

1. Cloner le dépôt:
   ```bash
   cd /var/www/html/glpi/plugins
   git clone https://github.com/SpyKeeR/snmptoneralerts.git
   ```

2. Installer les dépendances Composer (optionnel, pour développement):
   ```bash
   cd snmptoneralerts
   composer install
   ```

### Activation dans GLPI

1. Se connecter à GLPI en tant qu'administrateur

2. Aller dans **Configuration > Plugins**

3. Localiser "SNMP Toner Alerts" dans la liste

4. Cliquer sur **Installer**

5. Cliquer sur **Activer**

### Vérification de l'installation

Après activation, 3 nouvelles tables doivent être créées:
- `glpi_plugin_snmptoneralerts_excludedprinters`
- `glpi_plugin_snmptoneralerts_states`
- `glpi_plugin_snmptoneralerts_alerts`

Vérification:
```sql
SHOW TABLES LIKE 'glpi_plugin_snmptoneralerts%';
```

## Configuration

### Configuration globale

1. Aller dans **Configuration > SNMP Toner Alerts**

2. **Seuil d'alerte (%)**
   - Valeur par défaut: 20%
   - Définit le niveau en dessous duquel une alerte est déclenchée
   - Recommandé: entre 15% et 25%

3. **Destinataires emails**
   - Entrer les adresses séparées par des virgules
   - Exemple: `admin@example.com, technique@example.com`

4. **Fréquence de vérification**
   - Valeur par défaut: 6 heures (4 fois par jour)
   - Plage recommandée: 4 à 12 heures

5. **Nombre maximum d'alertes quotidiennes**
   - Valeur par défaut: 3
   - Après ce nombre, passage en mode récapitulatif hebdomadaire

6. **Horaires**
   - Alerte journalière: 08:00 (configurable)
   - Récapitulatif hebdomadaire: Vendredi 12:00

7. Cliquer sur **Enregistrer**

### Exclusion d'imprimantes

Certaines imprimantes peuvent remonter des données SNMP incorrectes. Pour les exclure:

1. Dans la page de configuration, section "Gestion des imprimantes exclues"

2. Sélectionner l'imprimante dans la liste déroulante

3. Entrer une raison (ex: "Données SNMP aberrantes")

4. Cliquer sur **Ajouter**

### Gestion des cartouches

Pour que le plugin puisse afficher les références de cartouches:

1. Aller dans **Parc > Cartouches**

2. Pour chaque modèle de cartouche, remplir le champ **Commentaire** avec la couleur:
   - "black" ou "noir" pour les toners noirs
   - "cyan" pour les cyan
   - "magenta" pour les magenta
   - "yellow" ou "jaune" pour les jaunes
   - "drum black" pour les blocs image noirs

3. Associer les cartouches aux modèles d'imprimantes

## Actions automatiques

### Configuration des CronTasks

1. Aller dans **Configuration > Actions automatiques**

2. Rechercher les 3 tâches du plugin:

#### CheckTonerLevels
- **Fréquence**: Toutes les 6 heures
- **Statut**: Activé
- **Mode**: Externe (CLI recommandé)
- **Action**: Vérifie les niveaux et met à jour les états

#### SendDailyAlerts
- **Fréquence**: 1 fois par jour
- **Statut**: Activé
- **Mode**: Externe
- **Heure**: 08:00 (via planificateur système)
- **Action**: Envoie les alertes quotidiennes

#### SendWeeklyRecap
- **Fréquence**: 1 fois par semaine
- **Statut**: Activé
- **Mode**: Externe
- **Jour**: Vendredi à 12:00
- **Action**: Envoie le récapitulatif hebdomadaire

### Configuration Cron système

Pour une exécution optimale, configurer les tâches cron système:

```bash
# Éditer le crontab
crontab -e

# Ajouter les lignes suivantes
# Vérification des niveaux toutes les 6 heures
0 */6 * * * /usr/bin/php /var/www/html/glpi/front/cron.php --force CheckTonerLevels

# Alertes journalières à 8h
0 8 * * * /usr/bin/php /var/www/html/glpi/front/cron.php --force SendDailyAlerts

# Récap hebdomadaire vendredi à 12h
0 12 * * 5 /usr/bin/php /var/www/html/glpi/front/cron.php --force SendWeeklyRecap
```

## Templates de notifications

### Fonctionnement des templates GLPI

Le plugin utilise le système de notifications intégré de GLPI. Les templates sont **entièrement modifiables** via l'interface GLPI sans toucher au code.

### Accès aux templates

1. Aller dans **Configuration > Notifications > Templates de notifications**

2. Rechercher les templates créés par le plugin:
   - "SNMP Toner Alert - Daily" (Alerte journalière)
   - "SNMP Toner Alert - Weekly" (Récapitulatif hebdomadaire)

### Balises disponibles

Le plugin fournit les balises suivantes pour personnaliser les emails:

| Balise | Description | Exemple |
|--------|-------------|---------|
| `##toner.threshold##` | Seuil d'alerte configuré | 20 |
| `##toner.count##` | Nombre d'imprimantes en alerte | 5 |
| `##toner.alert_type##` | Type d'alerte | Journalière / Hebdomadaire |
| `##PRINTERS##` | Liste formatée des imprimantes | Bloc de texte détaillé |

### Structure de la balise ##PRINTERS##

Cette balise contient pour chaque imprimante:
```
Imprimante: [Nom de l'imprimante]
Localisation: [Lieu]
Modèle: [Modèle]
  - [Couleur] ([Référence]): [X]% (Alerte [N]/[Max])
  - ...
```

### Modification des templates

#### Dans l'interface GLPI

1. Aller dans **Configuration > Notifications > Templates de notifications**

2. Cliquer sur le template à modifier (ex: "SNMP Toner Alert - Daily")

3. Onglet **Traductions**:
   - Sélectionner la langue (Français, Anglais, etc.)
   - Modifier le **Sujet de l'email**
   - Modifier le **Corps de l'email** (HTML et/ou Texte)

4. **Exemple de template HTML personnalisé**:
```html
<!DOCTYPE html>
<html>
<head>
    <style>
        body { font-family: Arial, sans-serif; }
        .alert-box { 
            background-color: #fff3cd; 
            border-left: 4px solid #ffc107;
            padding: 15px;
            margin: 20px 0;
        }
        .printer-info {
            background-color: #f8f9fa;
            padding: 10px;
            margin: 10px 0;
            border-radius: 5px;
        }
    </style>
</head>
<body>
    <h2>🖨️ Alerte SNMP Toner - ##toner.alert_type##</h2>
    
    <div class="alert-box">
        <p><strong>Seuil configuré:</strong> ##toner.threshold##%</p>
        <p><strong>Nombre d'imprimantes concernées:</strong> ##toner.count##</p>
    </div>

    <h3>Détails des alertes:</h3>
    <div class="printer-info">
        <pre>##PRINTERS##</pre>
    </div>

    <p>Merci de vérifier les niveaux et de commander les cartouches nécessaires.</p>
    
    <hr>
    <p style="color: #666; font-size: 12px;">
        Ce message est envoyé automatiquement par le plugin SNMP Toner Alerts.
    </p>
</body>
</html>
```

5. Sauvegarder

#### Templates différents selon le type d'alerte

Le plugin envoie deux types d'alertes avec des templates différents:

**Alerte journalière (Daily)**:
- Événement: `toner_alert_daily`
- Envoyée chaque matin (8h par défaut)
- Pour les imprimantes n'ayant pas atteint le maximum d'alertes

**Récapitulatif hebdomadaire (Weekly)**:
- Événement: `toner_alert_weekly`
- Envoyée le vendredi midi
- Pour les imprimantes ayant atteint le maximum d'alertes quotidiennes

### Association des templates aux notifications

1. Aller dans **Configuration > Notifications > Notifications**

2. Rechercher "SNMP Toner Alert"

3. Vérifier que les événements sont bien associés:
   - `Alerte toner journalière` → Template "Daily"
   - `Récapitulatif toner hebdomadaire` → Template "Weekly"

4. Configurer les destinataires (ou utiliser ceux de la configuration du plugin)

### Test des notifications

Pour tester les notifications sans attendre le cron:

1. Aller dans **Configuration > Actions automatiques**

2. Cliquer sur "SendDailyAlerts" ou "SendWeeklyRecap"

3. Cliquer sur **Exécuter** (bouton en haut à droite)

4. Vérifier la réception de l'email

### Personnalisation avancée

Pour une personnalisation plus poussée, vous pouvez:

1. **Créer plusieurs templates** pour différents destinataires
2. **Ajouter des conditions** dans les notifications GLPI
3. **Utiliser des variables CSS** pour adapter le style
4. **Inclure des images** (logos) via URLs absolues

## Dépannage

### Aucune alerte envoyée

1. Vérifier que les emails sont configurés dans GLPI
2. Vérifier que les CronTasks sont actives
3. Consulter les logs GLPI: `/var/log/glpi/glpi.log`
4. Vérifier manuellement les niveaux:
   ```sql
   SELECT * FROM glpi_plugin_snmptoneralerts_states WHERE is_alert = 1;
   ```

### Données SNMP manquantes

1. Vérifier NetInventory:
   ```sql
   SELECT COUNT(*) FROM glpi_printers_cartridgeinfos;
   ```

2. Relancer un inventaire SNMP sur une imprimante test

3. Vérifier la configuration SNMP de l'imprimante (communauté SNMP)

### Alertes en double

1. Vérifier qu'il n'y a pas de cron en double (système + GLPI)
2. Consulter le compteur d'alertes:
   ```sql
   SELECT * FROM glpi_plugin_snmptoneralerts_states;
   ```

### Templates non modifiables

1. Vérifier les droits utilisateur dans GLPI
2. S'assurer d'être en profil "Super-Admin"
3. Vérifier que les templates ne sont pas verrouillés

### Erreurs PHP

Vérifier les logs d'erreurs PHP:
```bash
tail -f /var/log/php-fpm/error.log
# ou
tail -f /var/log/apache2/error.log
```

### Désinstallation

Si besoin de réinstaller:

1. Désactiver le plugin
2. Désinstaller (supprime les tables et données)
3. Supprimer le dossier du plugin
4. Réinstaller en suivant la procédure

**⚠️ Attention**: La désinstallation supprime toutes les données (historique des alertes, exclusions, etc.)

## Support

- **Documentation**: [README.md](README.md)
- **Issues**: [GitHub Issues](https://github.com/SpyKeeR/snmptoneralerts/issues)
- **Discussions**: [GitHub Discussions](https://github.com/SpyKeeR/snmptoneralerts/discussions)

---

**Plugin développé par SpyKeeR** | [GitHub](https://github.com/SpyKeeR/snmptoneralerts) | Licence GPL-3.0
