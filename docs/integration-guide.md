# Integration des CANTAO Solax Bundles

Diese Anleitung beschreibt, wie das Bundle in einem bestehenden Contao/CANTAO-Projekt eingerichtet wird. Alle Schritte erfolgen
innerhalb des PHP- und Contao-Stacks; zusätzliche Python-Tools sind nicht erforderlich.

## Voraussetzungen

- Contao 5 mit aktiviertem CANTAO-Modul
- Zugriff auf die Solax-Cloud inklusive API-Key, Seriennummer und ggf. Plant-ID/UID
- Systembenutzer mit Berechtigungen für `composer` und die Contao-Konsole
- Netzwerkverbindung von Ihrem Server zur Solax-Cloud

## 1. Bundle installieren

1. Ergänzen Sie Ihr `composer.json`-Projekt um das Repository (falls nicht bereits vorhanden):
   ```bash
   composer config repositories.cantao-solax vcs https://github.com/404GamerNotFound/cantao_solax_add_on.git
   ```
2. Installieren Sie das Bundle:
   ```bash
   composer require cantao/solax-bundle:dev-main
   ```
3. Führen Sie anschließend die Contao-Migrationen aus, damit die Tabelle `tl_solax_metric` angelegt wird:
   ```bash
   vendor/bin/contao-console contao:migrate --no-interaction
   ```

> Tipp: Das Skript `scripts/install-contao.sh` automatisiert die obigen Schritte und ergänzt eine Basiskonfiguration.

## 2. Konfiguration hinterlegen

1. Öffnen oder erstellen Sie `config/config.yml` in Ihrem Contao-Projekt.
2. Ergänzen Sie den Abschnitt `cantao_solax` mit Ihren Solax-Zugangsdaten:
   ```yaml
   cantao_solax:
     solax:
       base_url: 'https://www.solaxcloud.com:9443'
       api_version: 'v1'
       api_key: '%env(SOLAX_API_KEY)%'
       serial_number: '%env(SOLAX_SERIAL)%'
       site_id: '%env(string:SOLAX_SITE_ID)%'
       timeout: 10
       retry_count: 2
       retry_delay: 1000
     cantao:
       metric_prefix: 'solax'
       metric_mapping:
         yieldtoday: 'energy.today'
         yieldtotal: 'energy.total'
       ignore_fields:
         - inverterSN
       decimal_precision: 2
     storage:
       table: 'tl_solax_metric'
     cron:
       interval: 'hourly'
   ```
3. Hinterlegen Sie die referenzierten Umgebungsvariablen z. B. in Ihrer `.env.local` oder im Hosting-Panel.
4. Räumen Sie Schreibzugriff nur vertrauenswürdigen Personen ein, da API-Schlüssel im Klartext vorliegen können.

### Einstellungen im Contao-Backend

- In **System → Einstellungen → Solax Fake-Daten** aktivieren Sie bei Bedarf den Fake-Data-Modus und passen Standort,
  Peakleistung sowie Wolkenvariabilität an. Die simulierten Werte orientieren sich an Sonnenauf- und -untergang sowie
  einer einfachen Wolkensimulation.
- Unter **System → Einstellungen → Solax Zugangsdaten** tragen Sie die echten API-Zugangsdaten ein. Sobald diese vollständig
  sind und der Fake-Data-Modus deaktiviert ist, verwendet der Cronjob automatisch Live-Daten der Solax-Cloud.
- Sind weder Zugangsdaten hinterlegt noch der Fake-Data-Modus aktiv, protokolliert der Cronjob einen entsprechenden Hinweis und
  überspringt den Lauf.

## 3. Cronjob prüfen

- Im Contao-Backend erscheint unter **System → Cron** der Job **SolaxSyncCron**.
- Stellen Sie sicher, dass die Contao-Cron-Infrastruktur korrekt eingerichtet ist (z. B. via Contao-Manager, System-Cron oder
  Aufruf von `vendor/bin/contao-console contao:cron`).
- Das konfigurierten Intervall (`hourly`, `minutely`, …) bestimmt die Abruffrequenz.

## 4. Daten validieren

1. Nach dem ersten Durchlauf sollten Datensätze in `System → Daten → Solax-Metriken` sichtbar sein.
2. Prüfen Sie dort, ob Werte wie `solax.yieldtoday` oder `solax.acpower` erscheinen.
3. Bei Fehlern finden Sie Hinweise im Contao/System-Log. Das Bundle protokolliert fehlgeschlagene Abrufe mit dem Symfony-Logger.

## 5. Dashboards konfigurieren

1. Öffnen Sie CANTAO und navigieren Sie zu Ihrem gewünschten Dashboard.
2. Fügen Sie ein neues Widget hinzu (z. B. Zeitreihe oder Kennzahl) und wählen Sie als Datenquelle die Solax-Integration.
3. Wählen Sie die gewünschten Metriken aus. Standardmäßig stehen u. a. AC-Leistung, Tages-/Gesamtertrag, Einspeisung,
   Verbrauch, Ladezustand und PV-String-Leistungen bereit.
4. Passen Sie Einheiten, Aggregationen und Visualisierung gemäß Ihren Anforderungen an.

## 6. Erweiterte Anpassungen

- Nutzen Sie `cantao_solax.cantao.metric_mapping`, um Rohschlüssel (z. B. `yieldtoday`) auf bestehende CANTAO-Entitäten abzubilden.
- Blenden Sie nicht benötigte Rohwerte mit `cantao_solax.cantao.ignore_fields` aus.
- Über `cantao_solax.cantao.decimal_precision` steuern Sie die Rundung von Fließkommazahlen.
- Das Konfigurationsfeld `cantao_solax.cron.interval` legt die Abruffrequenz fest.
- Passen Sie bei instabiler Verbindung `cantao_solax.solax.retry_count` sowie `cantao_solax.solax.retry_delay` an.
- Für zusätzliche Normalisierungslogik können Sie den Service `Cantao\SolaxBundle\Service\MetricNormalizer` erweitern
  (z. B. via Symfony-Dekoration).

## 7. Fehlerbehebung

- **Authentifizierung fehlgeschlagen:** Prüfen Sie API-Key, Seriennummer und Plant-ID. Für API v2 ist die UID erforderlich.
- **Keine Daten in CANTAO:** Stellen Sie sicher, dass der Cronjob ausgeführt wurde und die Tabelle `tl_solax_metric` Daten enthält.
- **Zeitüberschreitungen:** Erhöhen Sie `cantao_solax.solax.timeout` oder prüfen Sie die Netzwerkverbindung.

Mit diesen Schritten ist das Bundle vollständig in Contao eingebunden und liefert kontinuierlich Messwerte an CANTAO.
