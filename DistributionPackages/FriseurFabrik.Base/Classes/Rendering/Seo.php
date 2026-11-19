<?php

declare(strict_types=1);

namespace FriseurFabrik\Base\Rendering;

use Neos\Flow\Annotations as Flow;

/** Default titles, descriptions and structured data for search engines (used when the SEO tab is left empty) */
#[Flow\Proxy(false)]
final class Seo
{
    /** @param string|null $pageTitle null for the home page */
    public static function title(SalonData $data, ?string $pageTitle): string
    {
        $title = $pageTitle === null
            ? $data->name . ($data->tagline ? ' – ' . $data->tagline : '')
            : $pageTitle . ' | ' . $data->name;
        // the city is what people search for ("Friseur Würzburg")
        if ($data->city === null || str_contains($title, $data->city)) {
            return $title;
        }
        return $title . ' | ' . (str_contains($title, 'Friseur') ? '' : 'Friseur ') . $data->city;
    }

    /** @param string|null $pageTitle null for the home page; subpages get their own text to avoid duplicate descriptions */
    public static function description(SalonData $data, ?string $pageTitle): string
    {
        $where = trim(($data->city ?? '') . '-' . ($data->district ?? ''), '-');
        $salon = $data->name . ' – Ihr Friseur' . ($where !== '' ? ' in ' . $where : '');
        return ($pageTitle === null
                ? $salon . '. Preise, Team, Öffnungszeiten und Terminanfrage online.'
                : $pageTitle . ': ' . $salon . '. Jetzt Termin online anfragen.')
            . ($data->phone ? ' Telefon ' . $data->phone . '.' : '');
    }

    /**
     * schema.org opening hours; free text like "09:00 – 12:00, 14:00 – 18:00" yields one entry per time range
     *
     * @param array<string,string|null> $hours english day name => text
     * @return list<array<string,string>>
     */
    public static function openingHours(array $hours): array
    {
        $specs = [];
        foreach ($hours as $day => $text) {
            preg_match_all('/(\d{1,2})[:.](\d{2})\s*(?:-|–|—|bis)\s*(\d{1,2})[:.](\d{2})/u', (string)$text, $ranges, PREG_SET_ORDER);
            foreach ($ranges as $m) {
                $specs[] = [
                    '@type' => 'OpeningHoursSpecification',
                    'dayOfWeek' => $day,
                    'opens' => sprintf('%02d:%s', $m[1], $m[2]),
                    'closes' => sprintf('%02d:%s', $m[3], $m[4]),
                ];
            }
        }
        return $specs;
    }

    /** schema.org HairSalon (local business) plus WebSite (site name in Google results), for the home page */
    public static function jsonLd(SalonData $data, ?string $url, ?string $image, ?string $logo): string
    {
        $salon = array_filter([
            '@type' => 'HairSalon',
            'name' => $data->name,
            'url' => $url,
            'image' => $image ?? $logo,
            'logo' => $logo,
            'telephone' => $data->phone,
            'email' => $data->email,
            'address' => array_filter([
                '@type' => 'PostalAddress',
                'streetAddress' => $data->street,
                'postalCode' => $data->postalCode,
                'addressLocality' => $data->city,
                'addressCountry' => 'DE',
            ]),
            'hasMap' => $data->mapsUrl,
            'openingHoursSpecification' => self::openingHours($data->hours) ?: null,
            'sameAs' => array_values(array_filter([$data->instagramUrl, $data->facebookUrl, $data->tiktokUrl])) ?: null,
        ], static fn ($value) => $value !== null);
        $website = array_filter(['@type' => 'WebSite', 'name' => $data->name, 'url' => $url]);

        return '<script type="application/ld+json">'
            . json_encode(['@context' => 'https://schema.org', '@graph' => [$salon, $website]], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_THROW_ON_ERROR)
            . '</script>';
    }

    /** every page needs exactly one h1: pages without a hero promote their first section heading */
    public static function ensureH1(string $html): string
    {
        return str_contains($html, '<h1')
            ? $html
            : (preg_replace('#<h2(\s[^>]*)?>(.*?)</h2>#s', '<h1$1>$2</h1>', $html, 1) ?? $html);
    }
}
