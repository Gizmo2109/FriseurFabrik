<?php

declare(strict_types=1);

namespace FriseurFabrik\Base\Seed;

use Neos\Flow\Annotations as Flow;

/**
 * Builds the standard page structure of a salon website from a salon data file (Resources/Private/Salons/*.yaml).
 *
 * Element spec: ['type' => 'Hero', 'props' => [...], 'children' => ['items' => [element, ...]]]
 * Page spec:    ['key' => 'preise', 'props' => [...], 'content' => [element, ...]]
 * Property values "img:<name>" become seed images, "page:<key>" becomes a node:// link to that page.
 */
#[Flow\Proxy(false)]
final class SalonTemplate
{
    /**
     * @param array<string,mixed> $d salon data
     * @return array{home: list<array<string,mixed>>, pages: list<array<string,mixed>>, references: array<string,string>}
     */
    public static function build(array $d): array
    {
        $images = $d['images'] ?? [];
        $gallery = array_map(static fn (string $name) => 'img:' . $name, $images['gallery'] ?? []);
        $instagram = array_map(static fn (string $name) => 'img:' . $name, $images['instagram'] ?? array_slice($images['gallery'] ?? [], 0, 8));
        $home = $d['home'] ?? [];
        $hasTeam = !empty($d['team']['members']);
        $site = $d['site'];

        $homeContent = [
            self::el('Hero', [
                'kicker' => $home['kicker'] ?? '',
                'headline' => $home['headline'] ?? $site['salonName'],
                'text' => $home['text'] ?? '',
                'image' => 'img:' . ($images['hero'] ?? 'interior-plants'),
                'size' => 'large',
            ]),
        ];
        if (!empty($d['features'])) {
            $homeContent[] = self::el('Features', ['title' => $home['featuresTitle'] ?? 'Warum zu uns?', 'tone' => 'muted'], ['items' => array_map(
                static fn (array $f) => self::el('FeatureItem', ['icon' => $f['icon'] ?? 'scissors', 'title' => $f['title'], 'text' => $f['text'] ?? '']),
                $d['features'],
            )]);
        }
        $homeContent[] = self::el('TextImage', [
            'kicker' => $home['about']['kicker'] ?? 'Über uns',
            'title' => $home['about']['title'] ?? 'Willkommen',
            'text' => $home['about']['text'] ?? '',
            'image' => 'img:' . ($images['about'] ?? 'cutting'),
            'imagePosition' => 'right',
            'sectionId' => 'ueber-uns',
        ]);
        $homeContent[] = self::el('CallToAction', [
            'title' => 'Leistungen & Preise',
            'text' => $home['pricesTeaser'] ?? 'Transparente Preise für Schnitt, Farbe und Pflege – alle Leistungen auf einen Blick.',
            'buttonLabel' => 'Zur Preisliste',
            'link' => 'page:preise',
            'tone' => 'muted',
        ]);
        if ($gallery !== []) {
            $homeContent[] = self::el('Gallery', [
                'title' => $home['galleryTitle'] ?? 'Einblicke',
                'intro' => $home['galleryIntro'] ?? '',
                'images' => array_slice($gallery, 0, 5),
                'layout' => 'mosaic',
            ]);
        }
        if ($hasTeam) {
            $homeContent[] = self::team($d['team'], 'muted');
        }
        if (!empty($site['instagramUrl']) || !empty($images['instagram'])) {
            $homeContent[] = self::el('Instagram', ['title' => $home['instagramTitle'] ?? 'Neues von uns auf Instagram', 'images' => $instagram, 'limit' => 8]);
        }
        $homeContent[] = self::el('CallToAction', [
            'title' => $home['ctaTitle'] ?? 'Lust auf eine Veränderung?',
            'text' => $home['ctaText'] ?? 'Fragen Sie Ihren Wunschtermin online an – wir melden uns schnellstmöglich zurück.',
            'tone' => 'brand',
        ]);
        $homeContent[] = self::el('Contact', ['title' => 'So finden Sie uns', 'text' => $site['parkingText'] ?? '']);

        $prices = $d['prices'] ?? [];
        $priceContent = [
            self::el('Hero', [
                'kicker' => 'Leistungen & Preise',
                'headline' => $prices['headline'] ?? 'Unsere Preise',
                'text' => $prices['intro'] ?? '',
                'image' => 'img:' . ($images['prices'] ?? 'scissors-set'),
                'size' => 'small',
                'showPhoneButton' => false,
            ]),
        ];
        foreach ($prices['lists'] ?? [] as $index => $list) {
            $priceContent[] = self::el('PriceList', [
                'title' => $list['title'],
                'note' => $list['note'] ?? '',
                'tone' => $index % 2 === 1 ? 'muted' : 'default',
            ], ['items' => array_map(static fn (array $item) => self::el('PriceItem', [
                'name' => $item[0],
                'price' => $item[1] ?? '',
                'description' => $item[2] ?? '',
                'duration' => $item[3] ?? '',
            ]), $list['items'])]);
        }
        if (!empty($prices['note'])) {
            $priceContent[] = self::el('Text', ['text' => '<p><em>' . $prices['note'] . '</em></p>']);
        }
        if (!empty($prices['externalLink'])) {
            $priceContent[] = self::el('CallToAction', [
                'title' => $prices['externalLink']['title'],
                'text' => $prices['externalLink']['text'] ?? '',
                'buttonLabel' => $prices['externalLink']['label'],
                'link' => $prices['externalLink']['url'],
                'tone' => 'muted',
            ]);
        }
        $priceContent[] = self::el('CallToAction', ['title' => 'Termin gewünscht?', 'text' => 'Wählen Sie im Formular einfach Ihre Wunschleistung aus.', 'tone' => 'brand']);

        $salon = $d['salonPage'] ?? [];
        $salonContent = [
            self::el('Hero', [
                'kicker' => 'Unser Salon',
                'headline' => $salon['headline'] ?? 'Ambiente zum Wohlfühlen',
                'text' => $salon['intro'] ?? '',
                'image' => 'img:' . ($images['salon'] ?? 'lounge'),
                'size' => 'small',
                'showPhoneButton' => false,
            ]),
            self::el('TextImage', [
                'kicker' => $salon['kicker'] ?? 'Unsere Geschichte',
                'title' => $salon['title'] ?? 'Mit Leidenschaft für Ihr Haar',
                'text' => $salon['text'] ?? '',
                'image' => 'img:' . ($images['story'] ?? 'styling'),
                'imagePosition' => 'left',
            ]),
        ];
        if (!empty($salon['brands'])) {
            $salonContent[] = self::el('Headline', ['kicker' => 'Produkte', 'title' => 'Mit diesen Marken arbeiten wir', 'intro' => $salon['brands'], 'tone' => 'muted']);
        }
        if ($gallery !== []) {
            $salonContent[] = self::el('Gallery', ['title' => 'Galerie', 'images' => $gallery, 'layout' => 'grid']);
        }

        $booking = $d['booking'] ?? [];
        $faq = array_merge($d['faq'] ?? [], [
            ['Wie läuft die Online-Terminanfrage ab?', '<p>Sie wählen Leistung und Wunschtermin und senden die Anfrage ab. Wir melden uns telefonisch oder per E-Mail und bestätigen Ihren Termin – erst dann ist er fest gebucht.</p>'],
            ['Ich kann meinen Termin nicht wahrnehmen – was tun?', '<p>Bitte sagen Sie uns so früh wie möglich telefonisch Bescheid, dann können wir den Termin an andere Kund:innen vergeben.</p>'],
        ]);
        $bookingContent = [
            self::el('AppointmentForm', [
                'title' => $booking['title'] ?? 'Termin anfragen',
                'intro' => $booking['intro'] ?? 'Sagen Sie uns, was Sie sich wünschen – wir melden uns zeitnah mit einem Terminvorschlag.',
                'successText' => '<strong>Vielen Dank für Ihre Anfrage!</strong> Wir melden uns schnellstmöglich bei Ihnen, um den Termin zu bestätigen.',
                'tone' => 'muted',
            ]),
            self::el('Contact', ['title' => 'Kontakt & Anfahrt', 'text' => $site['parkingText'] ?? '']),
            self::el('Faq', ['title' => 'Häufige Fragen'], ['items' => array_map(
                static fn (array $qa) => self::el('FaqItem', ['question' => $qa[0], 'answer' => $qa[1]]),
                $faq,
            )]),
        ];

        $pages = [
            self::page('preise', 'Leistungen & Preise', $priceContent),
            self::page('salon', 'Unser Salon', $salonContent),
            self::page('termin', 'Termin & Kontakt', $bookingContent),
            self::page('impressum', 'Impressum', [self::el('Text', ['text' => self::impressum($d)])]),
            self::page('datenschutz', 'Datenschutz', [self::el('Text', ['text' => self::privacy($d)])]),
        ];

        if (!empty($d['showcase'])) {
            $pages[] = self::showcasePage();
        }

        // menus are built explicitly – which pages are linked is decided here, not by the pages
        $menu = [
            self::link('Start', 'page:home'),
            self::link('Leistungen & Preise', 'page:preise'),
        ];
        if ($hasTeam) {
            $menu[] = self::link('Team', 'page:home#team');
        }
        $menu[] = self::link('Unser Salon', 'page:salon');
        $menu[] = self::link('Termin & Kontakt', 'page:termin');

        return [
            'home' => $homeContent,
            'pages' => $pages,
            'references' => ['bookingPage' => 'termin', 'privacyPage' => 'datenschutz'],
            'header' => ['menu' => $menu],
            'footer' => [
                'columns' => [
                    self::el('FooterAbout', ['title' => '', 'text' => '<p>' . htmlspecialchars((string)($site['tagline'] ?? '')) . '</p>']),
                    self::el('FooterContact', ['title' => 'Kontakt']),
                    self::el('FooterHours', ['title' => 'Öffnungszeiten']),
                    self::el('FooterSocial', ['title' => !empty($site['instagramUrl']) || !empty($site['facebookUrl']) ? 'Folgen Sie uns' : 'Termin']),
                ],
                'legal' => [self::link('Impressum', 'page:impressum'), self::link('Datenschutz', 'page:datenschutz')],
            ],
            'locked' => ['impressum', 'datenschutz'],
        ];
    }

    /**
     * Unlinked, noindex page with every element type – for showing salons what is possible
     * @return array<string,mixed>
     */
    private static function showcasePage(): array
    {
        $page = self::page('bausteine', 'Bausteine (Vorschau)', [
            self::el('Hero', ['kicker' => 'Vorschau', 'headline' => 'Alle Bausteine', 'text' => 'Diese Seite zeigt die Elemente, die Sie auf Ihren Seiten einsetzen können. Sie ist in keinem Menü verlinkt.', 'image' => 'img:scissors-set', 'size' => 'small', 'showPhoneButton' => false]),
            self::el('BeforeAfter', ['title' => 'Vorher / Nachher', 'text' => 'Regler verschieben – Beispiel mit Platzhalterfotos.', 'beforeImage' => 'img:ombre', 'afterImage' => 'img:copper', 'tone' => 'muted']),
            self::el('Slider', ['title' => 'Bilder-Slider', 'images' => ['img:interior-plants', 'img:lounge', 'img:interior-long', 'img:cutting']]),
            self::el('ImageCards', ['title' => 'Bild-Kacheln', 'tone' => 'muted'], ['items' => [
                self::el('ImageCard', ['title' => 'Damen', 'text' => 'Schnitt, Farbe und Styling.', 'image' => 'img:long-brunette', 'link' => 'page:preise']),
                self::el('ImageCard', ['title' => 'Herren', 'text' => 'Klassisch oder modern.', 'image' => 'img:comb-cut', 'link' => 'page:preise']),
                self::el('ImageCard', ['title' => 'Hochsteckfrisuren', 'text' => 'Für Hochzeit und besondere Anlässe.', 'image' => 'img:updo', 'link' => 'page:termin']),
            ]]),
            self::el('InstagramPosts', ['title' => 'Instagram-Beiträge'], ['items' => [
                self::el('InstagramPost', ['postUrl' => 'https://www.instagram.com/p/CqN3lWpNMpI/', 'image' => 'img:hairflip', 'caption' => 'Beispiel-Beitrag mit eigenem Vorschaubild']),
                self::el('InstagramPost', ['postUrl' => 'https://www.instagram.com/p/CqN3lWpNMpI/', 'caption' => 'Beispiel ohne Vorschaubild']),
            ]]),
            self::el('Gallery', ['title' => 'Bildergalerie', 'images' => ['img:braid', 'img:bob', 'img:curly', 'img:short-platinum', 'img:color-brush'], 'layout' => 'mosaic', 'tone' => 'muted']),
            self::el('TextImage', ['kicker' => 'Text mit Bild', 'title' => 'Bild links oder rechts', 'text' => '<p>Text und Foto nebeneinander – die Bildposition stellen Sie im Inspector ein.</p>', 'image' => 'img:styling', 'imagePosition' => 'left']),
        ]);
        $page['props']['metaRobotsNoindex'] = true;
        return $page;
    }

    /** @param array<string,mixed> $team */
    private static function team(array $team, string $tone): array
    {
        return self::el('Team', ['title' => $team['title'] ?? 'Unser Team', 'intro' => $team['intro'] ?? '', 'tone' => $tone, 'sectionId' => 'team'], ['members' => array_map(
            static fn (array $m) => self::el('TeamMember', array_filter([
                'name' => $m['name'],
                'role' => $m['role'] ?? '',
                'bio' => $m['bio'] ?? '',
                'photo' => isset($m['photo']) ? 'img:' . $m['photo'] : null,
                'instagramUrl' => $m['instagram'] ?? null,
            ], static fn ($value) => $value !== null)),
            $team['members'],
        )]);
    }

    /** @return array<string,mixed> */
    private static function link(string $label, string $target): array
    {
        return self::el('MenuItem', ['label' => $label, 'link' => $target]);
    }

    /**
     * @param array<string,mixed> $props
     * @param array<string,list<array<string,mixed>>> $children
     * @return array<string,mixed>
     */
    private static function el(string $type, array $props, array $children = []): array
    {
        return ['type' => 'FriseurFabrik.Base:Content.' . $type, 'props' => $props, 'children' => $children];
    }

    /**
     * @param list<array<string,mixed>> $content
     * @return array<string,mixed>
     */
    private static function page(string $key, string $title, array $content): array
    {
        return [
            'key' => $key,
            'props' => [
                'title' => $title,
                'uriPathSegment' => $key,
            ],
            'content' => $content,
        ];
    }

    /** @param array<string,mixed> $d */
    private static function impressum(array $d): string
    {
        $s = $d['site'];
        $e = static fn (?string $value) => htmlspecialchars((string)$value);
        $lines = [
            '<h2>Impressum</h2>',
            '<p><strong>Angaben gemäß § 5 DDG</strong></p>',
            '<p>' . $e($d['legal']['company'] ?? $s['salonName']) . '<br>'
                . (!empty($s['ownerName']) ? 'Inhaber/in: ' . $e($s['ownerName']) . '<br>' : '')
                . $e($s['street'] ?? '') . '<br>' . $e(($s['postalCode'] ?? '') . ' ' . ($s['city'] ?? '')) . '</p>',
            '<p><strong>Kontakt</strong><br>Telefon: ' . $e($s['phone'] ?? '') . (!empty($s['email']) ? '<br>E-Mail: ' . $e($s['email']) : '') . '</p>',
        ];
        if (!empty($d['legal']['register'])) {
            $lines[] = '<p><strong>Registereintrag</strong><br>' . $e($d['legal']['register']) . '</p>';
        }
        $lines[] = '<p><strong>Berufsrechtliche Angaben</strong><br>Berufsbezeichnung: Friseur/in (verliehen in der Bundesrepublik Deutschland)<br>'
            . 'Zuständige Kammer: Handwerkskammer für Unterfranken, Rennweger Ring 3, 97070 Würzburg<br>'
            . 'Es gilt die Handwerksordnung (HwO).</p>';
        $lines[] = '<p><strong>Umsatzsteuer-ID</strong><br>' . $e($d['legal']['vatId'] ?? '[bitte ergänzen]') . '</p>';
        $lines[] = '<p><strong>Verbraucherstreitbeilegung</strong><br>Wir sind nicht bereit oder verpflichtet, an Streitbeilegungsverfahren vor einer Verbraucherschlichtungsstelle teilzunehmen.</p>';
        $lines[] = '<p><strong>Bildnachweis</strong><br>Platzhalterfotos: Wikimedia Commons (CC0 / gemeinfrei).</p>';
        $lines[] = '<p><em>Entwurf: Die Angaben stammen aus öffentlich zugänglichen Quellen und müssen vor der Veröffentlichung vom Salon geprüft werden.</em></p>';
        return implode("\n", $lines);
    }

    /** @param array<string,mixed> $d */
    private static function privacy(array $d): string
    {
        $s = $d['site'];
        $e = static fn (?string $value) => htmlspecialchars((string)$value);
        $responsible = $e($s['salonName']) . (!empty($s['ownerName']) ? ', Inh. ' . $e($s['ownerName']) : '') . ', '
            . $e($s['street'] ?? '') . ', ' . $e(($s['postalCode'] ?? '') . ' ' . ($s['city'] ?? ''))
            . ', Telefon ' . $e($s['phone'] ?? '') . (!empty($s['email']) ? ', E-Mail ' . $e($s['email']) : '');
        return implode("\n", [
            '<h2>Datenschutzerklärung</h2>',
            '<h3>1. Verantwortliche Stelle</h3>',
            '<p>' . $responsible . '</p>',
            '<h3>2. Aufruf der Website</h3>',
            '<p>Beim Besuch dieser Website verarbeitet unser Hosting-Anbieter technisch notwendige Daten (z.B. IP-Adresse, Datum und Uhrzeit, aufgerufene Seite, Browsertyp) in Server-Logfiles, um die Website sicher und stabil bereitzustellen (Art. 6 Abs. 1 lit. f DSGVO). Die Logfiles werden nach kurzer Zeit gelöscht.</p>',
            '<h3>3. Cookies und Tracking</h3>',
            '<p>Diese Website verwendet keine Tracking- oder Marketing-Cookies. Links zu Instagram, Facebook oder Google Maps werden erst beim Anklicken geöffnet; es gelten dann die Datenschutzbestimmungen des jeweiligen Anbieters.</p>',
            '<h3>3a. Instagram-Beiträge</h3>',
            '<p>Einzelne Instagram-Beiträge zeigen wir zunächst nur als Vorschau. Erst wenn Sie auf „Beitrag laden“ klicken, wird der Beitrag von Instagram (Meta Platforms Ireland Ltd.) geladen; dabei werden Daten wie Ihre IP-Adresse an Meta übertragen (Art. 6 Abs. 1 lit. a DSGVO – Ihre Einwilligung durch den Klick).</p>',
            '<h3>4. Terminanfrage</h3>',
            '<p>Wenn Sie das Terminformular nutzen, verarbeiten wir Ihre Angaben (Name, Telefonnummer, optional E-Mail, Wunschleistung, Wunschtermin und Nachricht), um Ihre Anfrage zu bearbeiten und mit Ihnen einen Termin zu vereinbaren (Art. 6 Abs. 1 lit. b DSGVO). Wenn Sie eine E-Mail-Adresse angeben, senden wir Ihnen Eingangsbestätigung, Terminbestätigung oder Terminvorschläge per E-Mail. Die Daten werden gelöscht, sobald sie für diesen Zweck nicht mehr erforderlich sind und keine gesetzlichen Aufbewahrungspflichten bestehen.</p>',
            '<h3>5. Ihre Rechte</h3>',
            '<p>Sie haben das Recht auf Auskunft, Berichtigung, Löschung und Einschränkung der Verarbeitung Ihrer Daten, auf Datenübertragbarkeit sowie auf Widerspruch. Außerdem können Sie sich bei einer Datenschutz-Aufsichtsbehörde beschweren, in Bayern beim Bayerischen Landesamt für Datenschutzaufsicht (BayLDA).</p>',
            '<p><em>Muster-Datenschutzerklärung – vor Veröffentlichung rechtlich prüfen lassen.</em></p>',
        ]);
    }
}
