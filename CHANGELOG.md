# Changelog - SNMP Toner Alerts

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.0.2] - 2025-10-31

### Changed
- Updated author from 'SNMP Toner Alerts Team' to 'SpyKeeR'
- Changed license from GPLv2+ to GPLv3+
- Updated repository links to https://github.com/SpyKeeR/snmptoneralerts
- Updated copyright headers in all PHP files
- Updated composer.json with new author and license information

### Added
- Complete French localization (fr_FR.po)
- version.json file for version tracking
- Comprehensive README.md with full documentation
- INSTALL.md with detailed installation instructions
- LICENSE file with GPL-3.0 full text

## [1.0.1] - 2025-10-31

### Fixed
- Minor bug fixes and improvements
- Code cleanup and optimization

## [1.0.0] - 2025-10-31

### Added
- Initial release of SNMP Toner Alerts plugin
- Automatic monitoring of printer toner levels via SNMP (NetInventory)
- Progressive alert system (daily then weekly)
- Alert management per toner (not per printer)
- Flexible configuration interface
  - Customizable alert threshold (percentage)
  - Email recipients configuration
  - Check frequency adjustment
  - Enable/disable daily and weekly alerts
- Printer exclusion management for devices with aberrant SNMP data
- Intelligent notification system via GLPI
  - Dynamic tags for detailed information
  - Automatic cartridge references display
  - Alert counter indication (e.g., "Alert 2/3")
- Three automatic actions (CronTasks):
  - CheckTonerLevels: Checks toner levels 4 times per day
  - SendDailyAlerts: Sends daily alerts in the morning
  - SendWeeklyRecap: Sends weekly recap on Friday at noon
- Database tables:
  - glpi_plugin_snmptoneralerts_excludedprinters: Excluded printers
  - glpi_plugin_snmptoneralerts_states: Current toner states with alert counters
  - glpi_plugin_snmptoneralerts_alerts: Complete alert history
- Support for various consumables:
  - Black, cyan, magenta, yellow toners
  - Drum units (black, cyan, magenta, yellow)
- Automatic mapping between SNMP properties and cartridge references
- Automatic alert counter reset when levels return to normal
- Real-time display of alert states in configuration page

### Features
- Compatible with GLPI >= 11.0.0
- Requires PHP >= 8.2
- PSR-4 autoloading
- Full integration with GLPI notification system
- Support for multi-brand printers with non-linear OIDs
