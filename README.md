[![Latest Version on Packagist](http://img.shields.io/packagist/v/diversworld/contao-editorial-workflow.svg?style=flat)](https://packagist.org/packages/diversworld/contao-editorial-workflow)
![Dynamic JSON Badge](https://img.shields.io/badge/dynamic/json?url=https%3A%2F%2Fraw.githubusercontent.com%2Fdiversworld%2Fcontao-editorial-workflow%2Fmain%2Fcomposer.json&query=%24.require%5B%22contao%2Fcore-bundle%22%5D&label=Contao%20Version)
[![Installations via composer per month](http://img.shields.io/packagist/dm/diversworld/contao-editorial-workflow.svg?style=flat)](https://packagist.org/packages/diversworld/contao-editorial-workflow)
[![Installations via composer total](http://img.shields.io/packagist/dt/diversworld/contao-editorial-workflow.svg?style=flat)](https://packagist.org/packages/diversworld/contao-editorial-workflow)
![Packagist License](https://img.shields.io/packagist/l/diversworld/contao-editorial-workflow)

![Diversworld](docs/dw-logo-k.png "Diversworld Logo")

# Contao Editorial Workflow

Dieses Modul erweitert Contao um einen strukturierten Redaktions- und Freigabeprozess für Inhalte. Es ermöglicht die
Umsetzung des Vier-Augen-Prinzips und stellt sicher, dass Änderungen an Inhalten vor der Veröffentlichung geprüft und
freigegeben werden.

## Funktionen

- **Workflow-Status**: Unterstützung verschiedener Status (Entwurf, In Prüfung, Freigegeben, Abgelehnt, Veröffentlicht,
  Archiviert).
- **Vier-Augen-Prinzip**: Optionale Prüfung durch eine zweite Instanz.
- **Protokollierung**: Revisionssichere Historie aller Statusänderungen und Kommentare in der
  `tl_editorial_workflow_log`.
- **Kommentarfunktion**: Review-Hinweise und Begründungen für Statusänderungen direkt am Inhalt.
- **Dashboard**: Übersicht ausstehender Prüfungen im Backend.

## Unterstützte Inhalte

Das Modul integriert sich generisch in folgende Contao-Tabellen:

- Seiten (`tl_page`)
- Artikel (`tl_article`)
- Inhaltselemente (`tl_content`)
- Nachrichten (`tl_news`)
- Events (`tl_calendar_events`)
- FAQ (`tl_faq`)

## Technische Anforderungen

- Contao 5.x
- PHP 8.1+
- Symfony Security Bundle

## Installation

1. Installation via Composer:
   ```bash
   composer require diversworld/contao-editorial-workflow
   ```
2. Contao Installtool aufrufen oder Datenbank via Manager aktualisieren.

## Konfiguration

### Workflow-Einstellungen (config/config.yaml)

Das Verhalten des Workflows kann über die Symfony-Konfiguration angepasst werden:

```yaml
diversworld_contao_editorial_workflow:
  four_eyes_principle: true  # Autoren dürfen ihre eigenen Inhalte nicht freigeben
  enabled_tables: # Tabellen, für die der Workflow aktiv ist
    - tl_page
    - tl_article
    - tl_news
```

### Berechtigungen im Backend

Nach der Installation stehen in den **Benutzergruppen** und **Benutzern** neue Felder unter "Workflow-Berechtigungen"
zur Verfügung:

1. **Prüfer (Reviewer)**: Darf Inhalte auf "Freigegeben" oder "Abgelehnt" setzen.
2. **Publisher**: Darf Inhalte auf "Veröffentlicht" setzen.

Ohne diese Berechtigungen können Redakteure Inhalte lediglich als "Entwurf" speichern oder "In Prüfung" geben.

## Lizenz

LGPL-3.0-or-later
