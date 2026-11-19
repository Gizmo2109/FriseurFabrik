# FriseurFabrik

Neos 9.2 Multi-Site-Setup für Friseur-Websites. Alle Websites teilen sich **einen** Satz Node-Types,
CPX-Komponenten und Renderer im Paket `DistributionPackages/FriseurFabrik.Base`. Gerendert wird komplett
mit der [PackageFactory Component Engine](https://codeberg.org/PackageFactory/neos-component-engine)
(`.cpx`-Komponenten + `PackageFactory.Neos.ComponentView` statt Fusion).

Die Bewertung der Würzburger Friseur-Websites steht in [docs/Bewertung-Friseure-Wuerzburg.md](docs/Bewertung-Friseure-Wuerzburg.md).

## Starten

```sh
docker compose up -d --build
```

| | |
|---|---|
| Backend | http://localhost:8081/neos – Administrator `admin` / `admin` |
| Websites | http://haarmonie.localhost:8081, http://leicht.localhost:8081, http://billis.localhost:8081, http://haarwerk.localhost:8081, http://schraud.localhost:8081, http://ax-friseure.localhost:8081, http://dauer.localhost:8081, http://haircut.localhost:8081, http://malek.localhost:8081, http://haarliebe.localhost:8081 |
| E-Mails (Mailpit) | http://localhost:8025 – fängt **alle** E-Mails ab, nichts geht wirklich raus |
| Element-Vorschau | http://haircut.localhost:8081/bausteine – alle Inhaltselemente auf einer Seite (nicht verlinkt, `noindex`) |

`*.localhost` zeigt in Chrome, Edge und Firefox automatisch auf den eigenen Rechner. Im Backend wechselt man
die Website über die Domain (z.B. http://billis.localhost:8081/neos). Der Login gilt pro Domain.

Das ganze Projekt ist in den Container gemountet, der Container läuft als UID 1000 – Dateien, die `flow` oder
`composer` erzeugen, gehören also dem Host-User. Befehle immer im Container ausführen:

```sh
docker compose exec neos ./flow <befehl>
docker compose exec neos composer <befehl>
```

## Bedienung für Salons

Jeder Salon hat ein eigenes Konto (Benutzername = Kurzname aus `./flow salon:list`, z.B. `haarwerk`) und meldet
sich auf **seiner** Domain an, z.B. http://haarwerk.localhost:8081/neos.

**Inhalte:** Texte direkt auf der Seite anklicken und tippen. Neue Elemente über das „+“ im Inhaltsbaum
(links). Fotos werden im Inspector (rechts) aus der Medienverwaltung gewählt oder hochgeladen – dafür gibt es
Bild, Bildergalerie, Text mit Bild, **Vorher / Nachher** (Schieberegler), **Bilder-Slider** und **Bild-Kacheln**
(Foto + Titel + Text + optionaler Link).

**Instagram:** Element **„Instagram-Beiträge“** einfügen, darin je Beitrag den Link aus Instagram
(„Link kopieren“, `https://www.instagram.com/p/…`) eintragen, optional ein eigenes Vorschaubild. Besucher sehen
zuerst nur die Vorschau; der echte Beitrag wird erst nach einem Klick geladen (Datenschutz). Das ältere Element
„Instagram“ zeigt einen Feed per API-Token (siehe Grenzen).

**Kopf- und Fußbereich** gelten für alle Seiten und werden zentral gepflegt:
- **Menü:** Im Inhaltsbaum „Kopfbereich (alle Seiten)“ → „Menü“ → „+“ → **Menüpunkt**. Im Inspector
  „Beschriftung“ und „Ziel“ setzen (eigene Seite, Abschnitt wie `/#team`, externe Adresse, E-Mail, Telefon).
  Reihenfolge per Drag & Drop. Seiten selbst haben **keine** Einstellung „im Menü anzeigen“ – was verlinkt wird,
  entscheidet allein das Menü.
- **Fußbereich:** Spalten (Über uns, Kontakt, Öffnungszeiten, Linkliste, Social Media & Termin), die Links unter
  „Rechtliches“ und die Copyright-Zeile (leer = „© Jahr Salonname“).
- **Veröffentlichen:** Der Button „Veröffentlichen“ veröffentlicht nur die aktuelle Seite. Änderungen am
  Kopf-/Fußbereich, die auf einer Unterseite gemacht wurden, über den Pfeil daneben → **„Alles veröffentlichen“**.

**Salon-Daten** (Adresse, Telefon, Öffnungszeiten, Social Media, Design, Akzentfarbe) pflegt man auf der Startseite
im Inspector-Tab **„Salon“**. Header, Footer, Kontakt- und Formular-Elemente lesen sie von dort. Unter
„Terminanfrage“ steht die **E-Mail für neue Terminanfragen** (leer = E-Mail aus „Kontakt & Adresse“).

**Termine:** Backend-Menü → Verwaltung → **Termine** (`/neos/management/termine`).
- Reiter **Offene Anfragen** / **Kalender** (kommende bestätigte Termine nach Tagen) / **Alle**, Suche nach Name,
  Telefon, E-Mail.
- Pro Anfrage: **Bestätigen** (Datum, Uhrzeit, Dauer, Stylist:in → Kund:in bekommt eine E-Mail mit Kalenderdatei),
  **Anderen Termin vorschlagen** (Kund:in nimmt per Link an oder lehnt ab), **Absagen** mit Begründung,
  **Kund:in kontaktieren** (E-Mail; Anrufen/WhatsApp-Links), **Erledigt** / **Nicht erschienen**, interne Notiz,
  **Löschen** (DSGVO). Jede Aktion steht im Verlauf der Anfrage.
- **+ Termin eintragen** für telefonische Buchungen (optional mit Bestätigungsmail).
- Bestätigte Termine können Kund:innen über den Link in der E-Mail selbst absagen; der Salon bekommt dann eine
  E-Mail.

## Zugriffsrechte

| Konto | Darf |
|---|---|
| `admin` (Rolle `Neos.Neos:Administrator`) | alles: alle Websites, alle Termine (mit Salon-Filter), Benutzer, **Administration → Seitenschutz** |
| Salon-Konto (Rolle `FriseurFabrik.Base:Salon_<name>`) | nur die eigene Website bearbeiten und nur deren Termine sehen; Backend anderer Domains zeigt „Kein Zugriff“ |

- Umgesetzt mit Subtree-Tags im Live-Workspace: jede Site trägt `salon-<name>`, die zugehörige
  `EditNodePrivilege` hat nur die Salon-Rolle (und Admins). Basisrolle aller Salon-Konten ist
  `FriseurFabrik.Base:SalonEditor` (Neos-Editor + Termin-Modul).
- **Gesperrte Seiten:** Impressum und Datenschutz sind für Salon-Konten gesperrt (Tag `fabrik-locked`). Unter
  Administration → Seitenschutz kann der Admin jede Seite (inkl. Unterseiten) sperren oder freigeben. Kopf- und
  Fußbereich hängen an der Site und bleiben auch auf gesperrten Seiten bearbeitbar.
- `Configuration/Policy.Salons.yaml` wird von `./flow salon:policy` erzeugt – nicht von Hand ändern.
  Die Admin-Rechte darin haben String-Schlüssel, weil Flow `Policy.yaml` und `Policy.*.yaml` eines Pakets per
  Listenindex zusammenführt (numerische Schlüssel würden die Modul-Rechte aus `Policy.yaml` überschreiben).
- Neue Rechte prüfen: `./flow salon:access <benutzername>`.

## E-Mails

Entwicklung: alle Mails landen in Mailpit (http://localhost:8025), konfiguriert in
`Configuration/Development/Docker/Settings.Mail.yaml` (`smtp://mailpit:1025`).

Für den echten Betrieb `Neos.SymfonyMailer.mailer.dsn` (SMTP-Zugang) und `FriseurFabrik.Base.mail.fromAddress`
(Absender, z.B. `termine@<agentur>.de`) setzen. Antworten der Kund:innen gehen per Reply-To an den Salon.
**Achtung:** Die Salon-Daten in `Resources/Private/Salons/*.yaml` enthalten die **echten** E-Mail-Adressen der
Salons – mit einem echten Mailer würden Test-Anfragen dort ankommen.

## Befehle

| Befehl | Zweck |
|---|---|
| `./flow salon:list` | Alle Salon-Datendateien mit lokaler Adresse und Bewertung der alten Website |
| `./flow salon:seed` | Legt alle noch fehlenden Salon-Websites an (Seiten, Inhalte, Kopf-/Fußbereich, Bilder, Domain, Zugriffs-Tags) und schreibt `Policy.Salons.yaml` |
| `./flow salon:seed --salon haarwerk --force` | Eine Website neu erzeugen – **im Backend gemachte Änderungen an dieser Website gehen verloren** (Termine bleiben) |
| `./flow salon:policy` | `Policy.Salons.yaml` (eine Rolle pro Salon) neu erzeugen, danach `./flow flow:cache:flush` |
| `./flow salon:accounts [--salon haarwerk]` | Fehlende Salon-Konten anlegen; Passwörter werden **nur einmal** angezeigt |
| `./flow user:setpassword <name> <passwort>` | Passwort eines Kontos ändern (Neos-Standard) |
| `./flow salon:access <benutzername>` | Zeigt Rollen und für jede Website: bearbeitbar ja/nein, gesperrte Seiten |
| `./flow salon:appointments [--site haarwerk]` | Terminanfragen in der Konsole anzeigen |
| `./flow components:build FriseurFabrik.Base` | `.cpx`-Komponenten nach PHP transpilieren – nach **jeder** Änderung an einer `.cpx`-Datei |
| `bin/phpunit -c Build/BuildEssentials/PhpUnit/UnitTests.xml Packages/Sites/FriseurFabrik.Base/Tests/Unit` | Unit-Tests (Salon-Daten, Seitenbaum, Formular-Validierung, Termin-Status, Kalenderdatei) |

## Aufbau

```
DistributionPackages/FriseurFabrik.Base/
├── NodeTypes/            gemeinsame Node-Types
│   ├── Document/         Salon (Startseite/Site mit Kopfbereich, Inhalt, Fußbereich), Page
│   ├── Layout/           SiteHeader + Menüpunkt, SiteFooter + Footer-Spalten
│   └── Content/          Hero, Preisliste, Team, Galerie, Vorher/Nachher, Slider, Bild-Kacheln, Instagram, …
├── Components/           CPX-Komponenten (.cpx) – hier wird das Markup gepflegt
│   ├── Layout/ Content/ Atom/ Data/   Website
│   ├── Backend/          Termin-Modul und Seitenschutz-Modul
│   └── Mail/             HTML-E-Mails
├── Generated/Components/ daraus generierte PHP-Klassen (components:build, nicht von Hand ändern).
│                         Der Ort ergibt sich aus dem psr-4-Eintrag für FriseurFabrik\Base\Components\ in composer.json
├── Classes/
│   ├── NodeTypes/        ein Renderer pro Node-Type (Konvention der Component Engine:
│   │                     FriseurFabrik.Base:Content.Hero → NodeTypes\Content\HeroRenderer)
│   ├── Rendering/        PageLayout (Seitengerüst, SEO, JSON-LD), Editable, Collections, Images, SalonData …
│   ├── Controller/       AppointmentController (Formular POST /termin-anfrage, Kundenlinks /termin/antwort),
│   │                     Module/AppointmentsController (Termine), Module/PageLocksController (Seitenschutz)
│   ├── Domain/           AppointmentRequest (Status + Verlauf), Repository, AppointmentService, Validierung
│   ├── Mail/             AppointmentMails (alle E-Mails), Ics (Kalenderdatei)
│   ├── Security/         SalonAccess (Tags setzen), BackendSiteAccessAspect, RateLimiter
│   ├── Seed/             SalonTemplate (Standard-Seitenstruktur) + SalonSeeder (legt Sites über CR-Commands an)
│   └── Instagram/        InstagramFeed (Instagram API, optional per Token)
├── Resources/Private/Salons/      eine YAML-Datei pro Salon (echte Daten + Texte; showcase: true = /bausteine)
├── Resources/Private/SeedImages/  Platzhalterfotos (CC0/gemeinfrei, siehe CREDITS.md)
└── Resources/Public/              CSS (5 Design-Vorlagen), JS, selbst gehostete Schriften, Backend-Modul-CSS/JS
```

**Terminanfragen** liegen in der Tabelle `friseurfabrik_base_domain_model_appointmentrequest`
(Status: neu → vorgeschlagen/bestätigt → erledigt/nicht erschienen, oder abgesagt/storniert). Leistungen und
Wunsch-Stylist:innen im Formular kommen automatisch aus Preislisten und Team der jeweiligen Website.
Kundenlinks enthalten ein zufälliges Token; der Link zeigt nur an, geändert wird erst per Klick (POST).
Schutz: Honeypot-Feld, serverseitige Validierung (auch Ruhetage), max. 5 Anfragen pro 10 Minuten und IP,
nur relative Rücksprung-URLs. Ohne JavaScript funktioniert das Formular per normalem POST mit Weiterleitung.

## Neuen Salon anlegen

1. `Resources/Private/Salons/<name>.yaml` anlegen (eine bestehende Datei kopieren). Bilder verweisen auf
   Dateien in `SeedImages/`.
2. `docker compose exec neos ./flow salon:seed --salon <name>` (schreibt auch die Rolle in `Policy.Salons.yaml`)
3. `docker compose exec neos ./flow flow:cache:flush` und `docker compose exec neos ./flow salon:accounts --salon <name>`
4. Website unter `http://<domain>:8081` öffnen und im Backend weiter pflegen.

## Bekannte Grenzen

- **Medienverwaltung** ist für alle Konten gemeinsam: Salon-Konten sehen auch die Bilder anderer Salons.
  (Lösung bei Bedarf: eine Asset-Collection pro Site plus `ReadAssetCollectionPrivilege`.)
- **Keine Doppelbuchungs-Prüfung:** beim Bestätigen wird nicht geprüft, ob die Zeit schon belegt ist – der
  Kalender-Reiter zeigt die Termine eines Tages untereinander.
- Der **Veröffentlichen-Dialog** von Neos ist teilweise noch englisch („Yes, publish“) – Neos-Übersetzungen.
- **Kein Output-Cache:** Collections werden bewusst ohne den Component-Engine-Cache gerendert, weil die
  Elemente salonweite Daten zeigen, die dieser Cache nicht invalidieren würde. Für kleine Salon-Sites
  unkritisch; bei mehr Traffic gezielt `#[Cached]`-Einstiegspunkte ergänzen (siehe `Rendering/Collections.php`).
- **Shortcut-Seiten** und eine eigene **404-Seite** unterstützt die Component View (alpha) noch nicht.
- **Instagram-Feed** (älteres Element) braucht einen Access-Token der Instagram API (Tab „Salon“ → Social Media);
  ohne Token zeigt es die im Backend gewählten Bilder. Einzelne Beiträge zeigt „Instagram-Beiträge“ ohne Token.
- Alle Fotos sind **Platzhalter**; Impressum und Datenschutz sind **Entwürfe** und vor Livegang zu prüfen.
- **Fehlt `Generated/` komplett**, bootet Flow nicht („Required class … could not be loaded“), und damit läuft
  auch `components:build` nicht. Dann einmal ohne Flow bauen und anschließend normal:
  ```sh
  docker compose exec neos sh -c 'P=/app/DistributionPackages/FriseurFabrik.Base; php bin/cpx build --package-name FriseurFabrik.Base --source-path $P/Components --php-namespace-prefix "FriseurFabrik\\Base\\Components\\" --php-target-directory $P/Generated/Components && find $P/Generated -name "*.php" -exec sed -i "s/^final readonly class/#[\\\\Neos\\\\Flow\\\\Annotations\\\\Proxy(false)]\nfinal readonly class/" {} \;'
  docker compose exec neos ./flow components:build FriseurFabrik.Base
  ```
- Nach Änderungen am `autoload` in `FriseurFabrik.Base/composer.json` reicht `dump-autoload` nicht (Path-Repository):
  `composer update friseurfabrik/base` ausführen.
- Workspace-Konflikte nach Rechte-Änderungen (z.B. Salon hat unveröffentlichte Änderungen auf einer frisch
  gesperrten Seite): Neos zeigt dem Konto einen Hinweis; notfalls den Workspace aus `./flow workspace:list`
  verwerfen (`./flow workspace:discard <workspace>` – **löscht dessen unveröffentlichte Änderungen**) und
  `./flow workspace:rebaseoutdated` ausführen.
