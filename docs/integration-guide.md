# Integration des CANTAO Solax Add-ons

Diese Anleitung beschreibt die empfohlene Vorgehensweise, um das CANTAO Solax Add-on in einer bestehenden CANTAO-Installation nutzbar zu machen. Sie deckt sowohl die technische Vorbereitung als auch die erforderlichen Schritte in der Benutzeroberfläche ab.

## Voraussetzungen

- Zugriff auf ein Solax-Cloud-Konto mit aktivierter API-Schnittstelle
- Seriennummer (SN) des Wechselrichters sowie optional die Plant-ID/UID
- Ein generiertes Zugriffstoken für die CANTAO-Instanz mit Berechtigung zum Schreiben von Metriken
- Python 3.11 oder neuer auf der Maschine, die die Daten abrufen soll
- Netzwerkzugriff von dieser Maschine sowohl zur Solax Cloud als auch zu CANTAO

## 1. Add-on installieren

1. Repository klonen oder als Paket beziehen.
2. Auf der Zielmaschine im Projektverzeichnis den folgenden Befehl ausführen:
   ```bash
   pip install .
   ```
   Alternativ kann mit `pip install -e .` eine Entwicklungsinstallation vorgenommen werden.

## 2. Konfiguration erstellen

1. Kopieren Sie die Beispieldatei `examples/config.example.toml` in einen sicheren Speicherort, z. B. `/etc/cantao-solax/config.toml`.
2. Passen Sie im Abschnitt `[solax]` die API-Parameter an (Token, Seriennummer, Plant-ID sowie optional die API-Version).
3. Hinterlegen Sie im Abschnitt `[cantao]` die Basis-URL Ihrer CANTAO-Instanz und das API-Token. Optional können Sie das Prefix oder individuelle Mappings anpassen.
4. Speichern Sie die Datei und stellen Sie sicher, dass nur vertrauenswürdige Personen Zugriff haben, da sie Geheimnisse enthält.

## 3. Testabruf durchführen

Führen Sie einen Probeabruf aus, um sicherzugehen, dass die Solax-Kommunikation funktioniert:

```bash
cantao-solax --config /pfad/zur/config.toml fetch
```

Die Ausgabe sollte einen `raw`-Block mit den Originaldaten sowie einen `metrics`-Block mit normalisierten Kennzahlen enthalten.

## 4. Automatischen Push in CANTAO einrichten

Um Messwerte zyklisch an CANTAO zu übertragen, können Sie einen Cronjob oder einen Systemd-Timer verwenden. Beispiel für einen Cronjob im Fünf-Minuten-Takt:

```
*/5 * * * * /usr/bin/cantao-solax --config /etc/cantao-solax/config.toml push >> /var/log/cantao-solax.log 2>&1
```

Achten Sie darauf, dass der Service-Benutzer Zugriff auf die Konfigurationsdatei hat.

## 5. Integration in der CANTAO-Oberfläche

1. Melden Sie sich in Ihrer CANTAO-Instanz mit einem Konto an, das Integrationen verwalten darf.
2. Navigieren Sie zu **Einstellungen → Integrationen → Eigene Datenquellen**.
3. Klicken Sie auf **Integration hinzufügen** und wählen Sie den Typ **Externes Metrik-API** aus.
4. Vergeben Sie einen aussagekräftigen Namen, z. B. „Solax Wechselrichter“.
5. Hinterlegen Sie unter **API-Endpunkt** `/api/v1/metrics` (dies entspricht dem Standardendpunkt, den das Add-on anspricht).
6. Aktivieren Sie die Option **Authentifizierung erforderlich** und tragen Sie dasselbe Token ein, das in der `config.toml` im Abschnitt `[cantao]` hinterlegt wurde.
7. Optional: Legen Sie eine Kategorie oder Tags fest, um die Metriken später leichter zu finden.
8. Speichern Sie die Integration. CANTAO bestätigt die erfolgreiche Registrierung mit einer Systemnachricht.

## 6. Dashboards und Widgets konfigurieren

1. Öffnen Sie das gewünschte Dashboard oder erstellen Sie ein neues.
2. Fügen Sie ein **Zeitreihen-Widget** hinzu und wählen Sie als Datenquelle die zuvor angelegte Solax-Integration aus.
3. Wählen Sie die gewünschten Entitäten (siehe Abschnitt „Mögliche Entitäten“) aus und konfigurieren Sie Aggregationen und Anzeigeeinheiten.
4. Wiederholen Sie den Vorgang für weitere Widgets, z. B. Kennzahlen-Kacheln oder Energiefluss-Diagramme.

## 7. Überwachung und Fehlerbehebung

- Prüfen Sie regelmäßig die Logdatei (`cantao-solax.log`), um Fehler frühzeitig zu erkennen.
- Nutzen Sie in CANTAO die Ansicht **System → Integrationsstatus**, um einzusehen, wann zuletzt Metriken eingegangen sind.
- Bei Authentifizierungsfehlern setzen Sie das Token in CANTAO neu und aktualisieren die `config.toml` entsprechend.

Mit diesen Schritten ist die Integration vollständig eingerichtet. Weitere Anpassungen wie benutzerdefinierte Metriknamen oder zusätzliche Dashboards lassen sich jederzeit nachpflegen.
