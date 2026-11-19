<?php

declare(strict_types=1);

namespace FriseurFabrik\Base\Tests\Unit;

use FriseurFabrik\Base\Rendering\SalonData;
use FriseurFabrik\Base\Rendering\Seo;
use FriseurFabrik\Base\Seed\SalonTemplate;
use Neos\Flow\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\Yaml\Yaml;

class SalonDataAndTemplateTest extends UnitTestCase
{
    private function salon(array $properties): SalonData
    {
        return SalonData::fromProperties(static fn (string $name) => $properties[$name] ?? null);
    }

    #[Test]
    public function linksAreDerivedAndUnsafeValuesAreRejected(): void
    {
        $salon = $this->salon([
            'salonName' => 'Test', 'phone' => '0931 / 123 45-6', 'email' => 'kein-mail', 'whatsapp' => '+49 931 123',
            'instagramUrl' => 'https://www.instagram.com/salon_test/', 'mapsUrl' => 'javascript:alert(1)',
            'accentColor' => 'red; background:url(x)', 'theme' => 'unbekannt',
        ]);
        self::assertSame('tel:0931123456', $salon->phoneHref());
        self::assertNull($salon->emailHref());
        self::assertSame('https://wa.me/49931123', $salon->whatsappHref());
        self::assertSame('@salon_test', $salon->instagramHandle());
        self::assertNull($salon->mapsUrl);
        self::assertNull($salon->accentColor);
        self::assertSame('classic', $salon->theme);
    }

    #[Test]
    public function hourRowsMarkClosedDaysAndToday(): void
    {
        $salon = $this->salon(['hoursTuesday' => '09:00 – 18:00', 'hoursMonday' => '  ']);
        $rows = $salon->hourRows(new \DateTimeImmutable('2026-11-10')); // a Tuesday
        self::assertCount(7, $rows);
        self::assertSame(['Montag', 'geschlossen', true, false], [$rows[0]['label'], $rows[0]['time'], $rows[0]['closed'], $rows[0]['today']]);
        self::assertSame(['09:00 – 18:00', false, true], [$rows[1]['time'], $rows[1]['closed'], $rows[1]['today']]);
    }

    #[Test]
    public function everySalonFileBuildsAValidPageTree(): void
    {
        $files = glob(__DIR__ . '/../../Resources/Private/Salons/*.yaml') ?: [];
        self::assertNotEmpty($files);
        foreach ($files as $file) {
            $data = Yaml::parseFile($file);
            $tree = SalonTemplate::build($data);
            $keys = array_column($tree['pages'], 'key');
            $expected = ['preise', 'salon', 'termin', 'impressum', 'datenschutz', ...(!empty($data['showcase']) ? ['bausteine'] : [])];
            self::assertSame($expected, $keys, basename($file));
            foreach ($tree['references'] as $pageKey) {
                self::assertContains($pageKey, $keys);
            }
            foreach ($tree['locked'] as $pageKey) {
                self::assertContains($pageKey, $keys);
            }
            // every "page:<key>#fragment" link in menus, footer and content must resolve (the seeder throws otherwise)
            array_walk_recursive($tree, static function (mixed $value) use ($keys, $file): void {
                if (is_string($value) && str_starts_with($value, 'page:')) {
                    self::assertContains(explode('#', substr($value, 5))[0], [...$keys, 'home'], basename($file) . ': ' . $value);
                }
            });
            self::assertNotEmpty($tree['header']['menu'], basename($file));
            self::assertSame('FriseurFabrik.Base:Content.Hero', $tree['home'][0]['type'], basename($file));
            $images = array_merge(...array_map(static fn ($v) => (array)$v, array_values($data['images'] ?? [])));
            foreach ($images as $image) {
                self::assertFileExists(__DIR__ . '/../../Resources/Private/SeedImages/' . $image . '.jpg', basename($file));
            }
        }
    }

    #[Test]
    public function seoDefaultsCarryTheCityAndDifferPerPage(): void
    {
        $salon = $this->salon(['salonName' => 'Haarwerk', 'tagline' => 'Wir machen Köpfe.', 'city' => 'Würzburg', 'district' => 'Altstadt']);
        self::assertSame('Haarwerk – Wir machen Köpfe. | Friseur Würzburg', Seo::title($salon, null));
        self::assertSame('Preise | Haarwerk | Friseur Würzburg', Seo::title($salon, 'Preise'));
        self::assertSame('Friseur X – Ihr Friseur in Würzburg', Seo::title($this->salon(['salonName' => 'Friseur X', 'tagline' => 'Ihr Friseur in Würzburg', 'city' => 'Würzburg']), null));
        self::assertNotSame(Seo::description($salon, null), Seo::description($salon, 'Preise'));
        self::assertStringContainsString('in Würzburg-Altstadt', Seo::description($salon, null));
        self::assertStringContainsString('in Sanderau', Seo::description($this->salon(['salonName' => 'X', 'district' => 'Sanderau']), null));
    }

    #[Test]
    public function openingHoursSplitIntoRangesAndSkipFreeText(): void
    {
        $specs = Seo::openingHours(['Monday' => '9.00 - 12:30, 14:00 – 18:00', 'Tuesday' => 'nach Vereinbarung', 'Wednesday' => null]);
        self::assertSame([['Monday', '09:00', '12:30'], ['Monday', '14:00', '18:00']], array_map(
            static fn (array $spec) => [$spec['dayOfWeek'], $spec['opens'], $spec['closes']],
            $specs,
        ));
    }

    #[Test]
    public function firstSectionHeadingBecomesH1OnlyWhenThereIsNone(): void
    {
        self::assertSame('<h1 class="t">A</h1><h2>B</h2>', Seo::ensureH1('<h2 class="t">A</h2><h2>B</h2>'));
        self::assertSame('<h1>Hero</h1><h2>B</h2>', Seo::ensureH1('<h1>Hero</h1><h2>B</h2>'));
        self::assertSame('<p>nur Text</p>', Seo::ensureH1('<p>nur Text</p>'));
    }
}
