> **WordPress.org-Kandidat:** Dieser Branch verwendet native Verzeichnis-Updates. Paketbau, Prüfergebnisse und Wechselhinweise: [WordPress.org-Vorbereitung](docs/WORDPRESS-ORG.md). Die nachfolgende bisherige Dokumentation beschreibt auch die separate GitHub-Ausgabe.

## Verzeichnisfassung 0.1.3

Die verbliebenen statischen Hinweise wurden gezielt nachgearbeitet. [Änderungen und 41 Integrationstests](docs/wordpress-org/ERGEBNIS.md) sowie [offengelegte Einzel-Ausnahmen](docs/wordpress-org/GEPRUEFTE-AUSNAHMEN.md) dokumentieren den neuen Stand.

![Plugin-Banner](wordpress-org/assets/banner-1544x500.png)

Für das WordPress.org-Profil [michaelgahndesign](https://profiles.wordpress.org/michaelgahndesign/) vorbereitet. Vorgeschlagener Slug: `mgd-share`; der endgültige Name wird bei der Aufnahme bestätigt.

- [Einreichungs-Readme](mgd-share-wordpress/readme.txt) mit Funktionen, Installation, Datenschutz und Screenshots.
- [Prüfbericht](docs/wordpress-org/ERGEBNIS.md) mit Einordnung aller verbleibenden lokalen Plugin-Check-Hinweise.
- [Paketbau, Updatekanal und sicherer Wechsel](docs/WORDPRESS-ORG.md).

`python3 scripts/build-wordpress-org.py` baut das installierbare ZIP mit Prüfsumme. `php tests/wordpress-org-contract.php` prüft Metadaten, Version, Grafiken und den nativen Updatekanal. Verzeichnisgrafiken und echte Backend-Screenshots liegen getrennt unter `wordpress-org/assets`; sie gehören später in den obersten SVN-assets-Ordner.

**Noch nicht eingereicht oder von WordPress.org zugelassen.** Diese Fassung lädt keinen externen Plugin-Katalog und installiert keine GitHub-Updatepakete. Die unten folgende Projektdokumentation enthält zusätzlich die Geschichte und Bedienung der eigenständigen GitHub-Ausgabe auf main. Nicht beide Ausgaben gleichzeitig aktivieren.

---

<div align="center">

<img src="docs/images/share-title.svg" alt="MGD Share – datensparsam teilen" width="100%">

# MGD Share

**Eine konfigurierbare Teilen-Funktion für WordPress – ohne vorab geladene Social-Media-Skripte.**

[![WordPress](https://img.shields.io/badge/WordPress-6.5%2B-21759B?logo=wordpress&logoColor=white)](https://wordpress.org/)
[![PHP](https://img.shields.io/badge/PHP-8.1%2B-777BB4?logo=php&logoColor=white)](https://www.php.net/)
[![Lizenz](https://img.shields.io/badge/Lizenz-GPL--2.0--or--later-4CAF50)](mgd-share-wordpress/LICENSE)

[Repository](https://github.com/MichaelGahnDESIGN/MGD-Share-WordPress) · [Issues](https://github.com/MichaelGahnDESIGN/MGD-Share-WordPress/issues) · [Sicherheitsrichtlinie](SECURITY.md)

</div>

---

MGD Share bringt einen Share-Button mit **Link kopieren**, System-Teilen, E-Mail und optionalen Plattform-Links in WordPress. Anders als eingebettete Social-Share-Widgets lädt das Plugin beim Seitenaufruf keine Ressourcen von WhatsApp, LinkedIn, X oder anderen Plattformen. Erst nach einem Klick öffnet sich das gewählte Ziel.

> **Status:** Erste Testversion. Die Datenschutz-Hinweise sind technische Orientierung für DE und EU und ersetzen keine Rechtsberatung.

## Funktionen

| Bereich | Enthalten |
| --- | --- |
| Link teilen | Kopieren in die Zwischenablage mit zugänglicher Rückmeldung |
| System-Funktion | Web Share API auf unterstützten Geräten |
| Externe Ziele | E-Mail, WhatsApp, LinkedIn und X – erst nach Klick |
| Darstellung | Vor/Nach Inhalt oder frei über Shortcode, drei Stile und Akzentfarbe |
| Dashboard | Datenschutz-Check und Erkennung bekannter Datenschutz-Plugins |
| Datenschutz | Keine Social-Media-Skripte, Cookies oder Tracking-Aufrufe durch MGD Share |

## Installation

1. Lade das Repository als ZIP herunter oder erzeuge lokal ein Paket.
2. Öffne in WordPress **Plugins → Installieren → Plugin hochladen**.
3. Aktiviere **MGD Share**.
4. Konfiguriere unter **MGD Share → Einstellungen** Position, Funktionen und Design.

Die automatische Position kann vor oder nach dem Inhalt erfolgen. Für Block-Editor, Page Builder und Theme-Templates steht der Shortcode bereit:

```text
[mgd_share]
```

## Datenschutz-Dashboard

Unter **MGD Share → Dashboard** findest du:

- eine konkrete technische Checkliste für Datenschutzerklärung und Tracking-Parameter,
- erkannte Datenschutz-Plugins wie Complianz, Real Cookie Banner und Borlabs Cookie,
- den Hinweis, dass für die lokale Share-Leiste selbst kein Consent erforderlich ist, weil sie keine externen Ressourcen vor dem Klick lädt.

Das Plugin verändert keine Optionen anderer Datenschutz-Plugins automatisch. Solche Optionen sind installations- und rechtskontextabhängig; blindes Umschreiben könnte eine bestehende Einwilligungs-Konfiguration beschädigen. Sobald ein Anbieter eine stabile, dokumentierte API für eine passende Einstellung bereitstellt, kann ein gezielter Adapter ergänzt werden.

## Technische Datenschutzregeln

1. Auf Seiten mit MGD Share werden keine Social-Media-Skripte, Pixel oder Iframes eingebunden.
2. Der Link in die Zwischenablage bleibt im Browser und wird nicht an den Server gesendet.
3. Externe Plattformen werden nur nach einer aktiven Auswahl geöffnet.
4. Tracking- oder personenbezogene URL-Parameter gehören nicht in Share-Links ohne eigene rechtliche Prüfung.

Prüfe vor dem Livegang Datenschutzerklärung, eingesetzte Tracking-Tools und den tatsächlichen Seitenaufruf. Bei rechtlichen Zweifeln ist fachliche Beratung nötig.

## Entwicklung und Paketprüfung

```bash
find mgd-share-wordpress -name '*.php' -exec php -l {} \;
node --check mgd-share-wordpress/assets/share.js
python3 scripts/check-docs.py
python3 scripts/build-package.py
```

Das installierbare Paket wird unter `dist/mgd-share-wordpress-0.1.0.zip` erzeugt.

## Sicherheit

Bitte keine Sicherheitslücken, Zugangsdaten oder personenbezogenen Testdaten in öffentlichen Issues posten. Hinweise stehen in [SECURITY.md](SECURITY.md).

## Lizenz

[GPL-2.0-or-later](mgd-share-wordpress/LICENSE)

## 0.1.3 – Plugin-Verwaltung

Separate Update-Prüfung, Plugin-Links und ergänzte lokale Details. [Anleitung](docs/WORDPRESS-VERWALTUNG.md).
