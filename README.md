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

## Aktivierung und Nutzung

Um das Modul erfolgreich zu nutzen, sind folgende Schritte erforderlich:

### 1. Installation und Datenbank-Update

Nach der Installation via Composer müssen die Datenbankfelder angelegt werden. Dies kann über das **Contao Installtool**
oder den **Contao Manager** erfolgen. Es werden Felder in den Tabellen der unterstützten Inhaltstypen sowie die neue
Tabelle `tl_editorial_workflow_log` für die Historie erstellt.

### 2. Konfiguration (Optional)

Standardmäßig ist das Modul für alle unterstützten Tabellen aktiv und das Vier-Augen-Prinzip ist eingeschaltet. Falls
gewünscht, kann dies in der `config/config.yaml` angepasst werden:

```yaml
# config/config.yaml
diversworld_contao_editorial_workflow:
  four_eyes_principle: true  # Autoren dürfen eigene Änderungen nicht selbst freigeben
  enabled_tables: # Nur für diese Tabellen aktivieren
    - tl_page
    - tl_article
    - tl_news
```

### 3. Rollen und Berechtigungen vergeben

Die Steuerung des Workflows erfolgt über das Contao-Berechtigungssystem. Editieren Sie eine **Benutzergruppe** oder
einen **Benutzer** und vergeben Sie folgende Berechtigungen:

1. **Erlaubte Felder**: Aktivieren Sie unter "Erlaubte Felder" für jede genutzte Tabelle (z.B. `tl_news`, `tl_page`) die
   Felder `workflow_status` und `workflow_comment`.
   *Hinweis: Benutzer mit den Rollen "Prüfer" oder "Publisher" sehen die Felder automatisch, auch wenn diese nicht
   explizit freigeschaltet wurden.*
2. **Workflow-Berechtigungen**: Wählen Sie die entsprechenden Rollen aus:
   * **Prüfer (Reviewer)**: Darf Inhalte auf "Freigegeben" oder "Abgelehnt" setzen.
   * **Publisher**: Darf Inhalte auf "Veröffentlicht" setzen.

Redakteure ohne diese Rollen können Inhalte erstellen, bearbeiten und den Status auf "In Prüfung" setzen, sofern sie
Zugriff auf die Felder haben.

### 4. Wichtige Hinweise zur Fehlerbehebung

Sollte es im Backend zu einer Fehlermeldung kommen (z.B. `Unknown column 'workflow_status'`), stellen Sie sicher, dass
Sie das **Contao Installtool** ausgeführt haben. Das Modul fängt diese Fehler zwar im Dashboard ab, aber für die volle
Funktionalität müssen die Spalten in der Datenbank vorhanden sein.

### 5. Benachrichtigungen (Notification Center)

Das Modul integriert sich vollständig in
das [Notification Center](https://github.com/terminal42/contao-notification-center).

1. **Benachrichtigungstyp**: Erstellen Sie im Backend eine neue Benachrichtigung. Wählen Sie als Typ einen der unter "
   Editorial Workflow" gelisteten Typen (z.B. "In Prüfung").
2. **Tokens**: In den Nachrichtentexten können Sie folgende Tokens verwenden:
   * `##workflow_table##`: Die betroffene Tabelle (z.B. `tl_news`).
   * `##workflow_id##`: Die ID des Datensatzes.
   * `##workflow_status##`: Der neue Status.
   * `##workflow_comment##`: Der hinterlegte Kommentar.
   * `##workflow_user##`: Der Benutzer, der die Änderung vorgenommen hat.
   * `##workflow_data_*##`: Alle Felder des betroffenen Datensatzes (z.B. `##workflow_data_headline##`).
3. **Versand**: Sobald ein Redakteur oder Prüfer den Status ändert, werden alle für diesen Typ konfigurierten
   Benachrichtigungen automatisch versendet.

### 6. Workflow im Redaktionsalltag

1. **Erstellung/Bearbeitung**: Ein Redakteur bearbeitet einen Inhalt (z.B. eine Nachricht). Er speichert diesen zunächst
   als "Entwurf".
2. **Prüfung anfordern**: Sobald die Bearbeitung abgeschlossen ist, setzt der Redakteur den Status auf **"In Prüfung"**.
   Optional kann ein Kommentar für den Prüfer hinterlassen werden.
3. **Freigabe durch Prüfer**: Ein Benutzer mit der Rolle **Prüfer** sieht den Inhalt (z.B. über das Dashboard oder in
   der Listenansicht) und kann ihn nach Sichtung auf **"Freigegeben"** oder **"Abgelehnt"** setzen. Bei einer Ablehnung
   sollte ein Grund im Kommentarfeld angegeben werden.
4. **Veröffentlichung**: Ein Benutzer mit der Rolle **Publisher** kann freigegebene Inhalte auf **"Veröffentlicht"**
   setzen. Erst in diesem Status (und wenn das Contao-Feld "Veröffentlichen" aktiv ist) wird der Inhalt im Frontend
   sichtbar.

### 7. Historie einsehen

Jede Statusänderung wird revisionssicher protokolliert. In der Listenansicht der jeweiligen Module (z.B.
Nachrichten-Liste) kann über das Info-Icon des Datensatzes die Historie der Workflow-Änderungen eingesehen werden.

## Lizenz
LGPL-3.0-or-later

## Donation

If you like this extension and think it's worth a little donation: You can support me via Paypal.Me:

[Donation for Diversworld EditorialWorkflow](https://paypal.me/EckhardBecker615)

Thank You!
