# 🖨️ SNMP Toner Alerts pour GLPI

<div align="center">

[![License: GPL v3](https://img.shields.io/badge/License-GPLv3-blue.svg)](https://www.gnu.org/licenses/gpl-3.0)
[![GLPI](https://img.shields.io/badge/GLPI-≥11.0-green.svg)](https://glpi-project.org/)
[![PHP](https://img.shields.io/badge/PHP-≥8.2-777BB4.svg)](https://www.php.net/)
[![Version](https://img.shields.io/badge/version-1.1.1-orange.svg)](https://github.com/SpyKeeR/snmptoneralerts/releases)

**Plugin GLPI pour la surveillance automatique des niveaux de toners via SNMP**

[Installation](#-installation) • [Fonctionnalités](#-fonctionnalités) • [Documentation](#-documentation) • [Support](#-support)

</div>

---

## 🎯 Présentation

**SNMP Toner Alerts** automatise la surveillance des consommables d'imprimantes dans GLPI en exploitant les données SNMP déjà collectées par NetInventory.

### Le problème

- 📝 Mise à jour **manuelle** du stock par les techniciens
- 🚫 Données SNMP **non exploitées** par GLPI natif
- ⏰ Découverte des pannes **trop tardive**

### La solution

- ✅ Surveillance **automatique** 24/7
- ✅ Alertes **progressives** (quotidiennes → hebdomadaires)
- ✅ **Zéro intervention** manuelle

---

## ✨ Fonctionnalités

### 🔍 Surveillance intelligente

- **Vérification automatique** : 4 fois par jour
- **Gestion par toner** : chaque consommable suivi individuellement
- **Multi-marques** : compatible toutes imprimantes SNMP
- **Temps réel** : affichage live des états

### 🔔 Système d'alertes progressives

```
📊 Niveau < Seuil (5%)
    ↓
📧 3 alertes quotidiennes (08h00)
    ↓
📋 Récapitulatif hebdomadaire (Vendredi 12h00)
    ↓
✅ Reset automatique si niveau OK
```

**Caractéristiques :**
- Compteur intelligent ("Alerte 2/3")
- Références de cartouches automatiques
- Historique complet en base de données

### ⚙️ Configuration flexible

| Paramètre | Défaut | Personnalisable |
|-----------|--------|-----------------|
| Seuil d'alerte | 5% | ✅ |
| Destinataires emails | - | ✅ |
| Fréquence vérification | 6h | ✅ |
| Horaires alertes | 08h00 / Ven 12h00 | ✅ |

### 📧 Notifications personnalisables

- **12+ balises dynamiques** (nom, lieu, modèle, niveau, référence...)
- **Boucles FOREACH** pour listes imprimantes/toners
- **Templates HTML/Texte** modifiables dans GLPI

---

## 📋 Prérequis

| Composant | Version minimale |
|-----------|------------------|
| **GLPI** | 11.0.0+ |
| **PHP** | 8.2+ |
| **NetInventory** | Requis |
| **NetDiscovery** | Requis |

> ⚠️ **Important** : NetInventory doit remonter les données SNMP des imprimantes

---

## 🚀 Installation

### Installation rapide

**Option 1 : Téléchargement manuel**

1. Télécharger le dépôt depuis [GitHub](https://github.com/SpyKeeR/snmptoneralerts)
   - Cliquer sur **Code** → **Download ZIP**
2. Extraire l'archive dans `glpi/plugins/`
3. Renommer le dossier en `snmptoneralerts` (retirer le `-main` ou `-master`)

```bash
# Exemple sous Linux
cd /var/www/html/glpi/plugins
# Après avoir téléchargé snmptoneralerts-main.zip
unzip snmptoneralerts-main.zip
mv snmptoneralerts-main snmptoneralerts

# Permissions
chown -R www-data:www-data snmptoneralerts
chmod -R 755 snmptoneralerts
```

**Option 2 : Clonage Git**

```bash
cd /var/www/html/glpi/plugins
git clone https://github.com/SpyKeeR/snmptoneralerts.git
chown -R www-data:www-data snmptoneralerts
chmod -R 755 snmptoneralerts
```

### Activation dans GLPI

1. **Configuration** → **Plugins**
2. Localiser **"SNMP Toner Alerts"**
3. Cliquer **Installer** puis **Activer**

### Vérification

```sql
-- Vérifier présence des données SNMP
SELECT COUNT(*) FROM glpi_printers_cartridgeinfos;
-- Doit retourner > 0
```

> 📖 **Installation détaillée** : consultez [INSTALL.md](INSTALL.md) pour plus d'options (Git, Composer, troubleshooting...)

---

## ⚙️ Configuration de base

### 1. Gestion des exclusions (v1.1.1)

**Configuration** → **Configuration** → **Onglet "Alertes toners SNMP"** → Section **Gestion des imprimantes exclues**

Permet d'exclure des imprimantes du monitoring (données SNMP incorrectes, imprimante hors service, etc.) :

- **Tableau** : Liste des imprimantes déjà exclues avec nom, raison, utilisateur, date
- **Ajout** : Sélectionner imprimante + raison → Bouton "Ajouter une exclusion"
- **Suppression** : Bouton "Retirer" sur chaque ligne (avec confirmation)

> 💡 Les imprimantes **en corbeille** (`is_deleted=1`) sont automatiquement ignorées

### 2. Paramètres du plugin

**Configuration** → **Configuration** → **Onglet "Alertes toners SNMP"**

| Paramètre | Valeur par défaut | Recommandations |
|-----------|-------------------|-----------------|
| **Seuil d'alerte (%)** | 5% | Configurer selon délais d'approvisionnement |
| **Max alertes quotidiennes** | 3 | Nombre d'alertes avant basculement hebdomadaire |

**Liens de configuration rapide** (dans le formulaire, avec filtres automatiques) :
- ⏰ **Planification & Fréquence** → Ouvre directement les CronTasks du plugin
- 🔔 **Destinataires Email** → Ouvre directement les notifications du plugin
- ✉️ **Modèles d'Email** → Ouvre directement les templates du plugin

### 3. Destinataires des notifications

**Configuration** → **Notifications** → **Notifications**

Rechercher **"SNMP Toner Alert - Daily"** et **"SNMP Toner Alert - Weekly"** :
- Ajouter des utilisateurs, groupes ou profils dans l'onglet **Destinataires**
- Possibilité d'ajouter des emails externes

### 3. Actions automatiques (CronTasks)

**Configuration** → **Actions automatiques**

| CronTask | Fréquence | Activation | Rôle |
|----------|-----------|------------|------|
| **CheckTonerLevels** | 6h | ✅ Auto | Vérifie les niveaux de toners |
| **SendDailyAlerts** | Quotidien | ✅ Auto | Envoie alertes (compteur ≤3) |
| **SendWeeklyRecap** | Hebdomadaire | ✅ Auto | Envoie récaps (compteur >3) |

> ℹ️ Les CronTasks sont **activés par défaut** lors de l'installation

**Pour personnaliser les horaires** :
- Cliquer sur la CronTask
- Modifier la fréquence d'exécution
- Pour horaires précis (ex: 08h00), configurer un cron système (voir [INSTALL.md](INSTALL.md))

### 4. Personnalisation des templates

**Configuration** → **Notifications** → **Modèles de notifications**

Rechercher **"SNMP Toner Alert"** pour modifier les templates email.

> 📖 **Configuration avancée** : voir [INSTALL.md](INSTALL.md) pour exclusions, templates, troubleshooting...

---

## 🗄️ Architecture

### Tables créées

```
glpi_printers → glpi_printers_cartridgeinfos (SNMP)
                         ↓
        glpi_plugin_snmptoneralerts_states (États + compteurs)
                         ↓
        glpi_plugin_snmptoneralerts_alerts (Historique)

glpi_plugin_snmptoneralerts_excludedprinters (Exclusions)
```

### Affichage automatique des références

Le plugin affiche automatiquement les **noms et références** des cartouches dans les notifications en associant :
- Les propriétés SNMP (tonerblack, tonercyan, etc.)
- Les modèles d'imprimantes (`glpi_printermodels`)
- Les types de cartouches (`glpi_cartridgeitems`)

**Mapping automatique** :

| Propriété SNMP | Mots-clés recherchés | Fallback |
|----------------|---------------------|----------|
| tonerblack | black, noir, bk | - |
| tonercyan | cyan, c | tri-color, tricolor, couleur |
| tonermagenta | magenta, m | tri-color, tricolor, couleur |
| toneryellow | yellow, jaune, y | tri-color, tricolor, couleur |
| drumblack/cyan/magenta/yellow | drum + couleur | - |

**Format dans les notifications** :
```
- Toner noir: 19% (HP 305A Black (Ref: CE410A)) [Alerte 2/3]
- Toner cyan: 10% (HP 305 Tri-color (Ref: CE411A)) [Alerte 1/3]
```

> 💡 Le système cherche d'abord la couleur spécifique, puis fallback vers tri-color pour les cartouches multicolores

---

## 📧 Exemple de notification

### Template simple

```
Bonjour,

5 imprimante(s) ont des toners en dessous de 5%.

Type d'alerte : Quotidienne

──────────────────────────────
📍 Xerox-RDC-Accueil
   Localisation : Bat A > RDC > Accueil
   Modèle : WorkCentre 5335

   Toners concernés :
   • Toner cyan : 15%
     → Référence : 006R01603
     → État : Alerte 2/3

──────────────────────────────

Merci de commander les cartouches nécessaires.
```

> 📖 **Templates avancés** : voir [INSTALL.md](INSTALL.md) pour liste complète des balises et exemples HTML

---

## 🔧 Dépannage rapide

### Aucune alerte reçue ?

1. ✅ Vérifier CronTasks actives : **Configuration** → **Actions automatiques**
2. ✅ Vérifier données SNMP : `SELECT COUNT(*) FROM glpi_printers_cartridgeinfos`
3. ✅ Vérifier notifications GLPI : **Configuration** → **Notifications**
4. ✅ Vérifier emails configurés : **Configuration** → **SNMP Toner Alerts**

### Références cartouches manquantes ?

1. Aller dans **Gestion** → **Modèles d'imprimantes**
2. Onglet **Cartouches compatibles** → Ajouter relations
3. Éditer les cartouches et renseigner le champ **Commentaire** avec la couleur

> 📖 **Dépannage complet** : voir [INSTALL.md](INSTALL.md) pour diagnostics SQL et solutions détaillées

---

## 📚 Documentation

| Document | Contenu |
|----------|---------|
| 📖 [INSTALL.md](INSTALL.md) | Installation détaillée, configuration avancée, templates, troubleshooting |
| 📝 [CHANGELOG.md](CHANGELOG.md) | Historique des versions |
| ⚖️ [LICENSE](LICENSE) | Licence GPL-3.0-or-later |

---

## 🤝 Contribution

Les contributions sont bienvenues ! 🎉

1. Fork le projet
2. Créer une branche : `git checkout -b feature/SuperFeature`
3. Commit : `git commit -m '✨ Add: Super Feature'`
4. Push : `git push origin feature/SuperFeature`
5. Ouvrir une Pull Request

**Signaler un bug** : [GitHub Issues](https://github.com/SpyKeeR/snmptoneralerts/issues)

---

## 💬 Support

| Canal | Lien |
|-------|------|
| 🐛 Bugs | [GitHub Issues](https://github.com/SpyKeeR/snmptoneralerts/issues) |
| 💡 Questions | [GitHub Discussions](https://github.com/SpyKeeR/snmptoneralerts/discussions) |

---

## 📝 Licence

GPL-3.0-or-later - Voir [LICENSE](LICENSE)

**Vous êtes libre de** : utiliser, modifier, distribuer (commercialement ou non)

**À condition de** : conserver la licence, divulguer la source, mentionner les modifications

---

## 🌟 Remerciements

- **GLPI Team** - CMS ITSM open-source
- **NetInventory** - Collecte SNMP
- **Communauté GLPI** - Support et retours

---

<div align="center">

## ⭐ Star le projet !

**Si ce plugin vous aide, donnez-lui une étoile sur GitHub !**

[![GitHub stars](https://img.shields.io/github/stars/SpyKeeR/snmptoneralerts?style=social)](https://github.com/SpyKeeR/snmptoneralerts/stargazers)

---

**Développé avec ❤️ par [SpyKeeR](https://github.com/SpyKeeR)**

📅 Dernière mise à jour : 31 octobre 2025

</div>
