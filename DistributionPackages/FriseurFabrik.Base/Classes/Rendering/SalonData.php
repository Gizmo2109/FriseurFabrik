<?php

declare(strict_types=1);

namespace FriseurFabrik\Base\Rendering;

use Neos\ContentRepository\Core\Projection\ContentGraph\Node;
use Neos\Flow\Annotations as Flow;
use Neos\Media\Domain\Model\ImageInterface;

/**
 * Salon wide master data, maintained on the site node (inspector tab "Salon")
 */
#[Flow\Proxy(false)]
final readonly class SalonData
{
    public const DAYS = [
        'Monday' => 'Montag',
        'Tuesday' => 'Dienstag',
        'Wednesday' => 'Mittwoch',
        'Thursday' => 'Donnerstag',
        'Friday' => 'Freitag',
        'Saturday' => 'Samstag',
        'Sunday' => 'Sonntag',
    ];

    private const THEMES = ['classic', 'modern', 'natur', 'barber', 'pastell'];

    /**
     * @param array<string,string|null> $hours english day name => opening hours (null = closed)
     */
    private function __construct(
        public string $name,
        public ?string $tagline,
        public ?ImageInterface $logo,
        public ?string $owner,
        public ?string $street,
        public ?string $postalCode,
        public ?string $city,
        public ?string $district,
        public ?string $phone,
        public ?string $email,
        public ?string $notificationEmail,
        public ?string $whatsapp,
        public ?string $mapsUrl,
        public ?string $parkingInfo,
        public array $hours,
        public ?string $hoursNote,
        public ?string $instagramUrl,
        public ?string $facebookUrl,
        public ?string $tiktokUrl,
        public ?string $googleReviewsUrl,
        public ?string $instagramAccessToken,
        public string $theme,
        public ?string $accentColor,
        public string $bookingLabel,
        public ?string $externalBookingUrl,
    ) {
    }

    public static function fromSiteNode(Node $site): self
    {
        return self::fromProperties(static fn (string $name): mixed => $site->getProperty($name));
    }

    /** @param \Closure(string): mixed $property property reader, e.g. of the site node */
    public static function fromProperties(\Closure $property): self
    {
        $string = static function (string $name) use ($property): ?string {
            $value = $property($name);
            return is_string($value) && trim($value) !== '' ? trim($value) : null;
        };
        $hours = [];
        foreach (array_keys(self::DAYS) as $day) {
            $hours[$day] = $string('hours' . $day);
        }
        $logo = $property('logo');
        $theme = $string('theme');
        $accent = $string('accentColor');

        return new self(
            name: $string('salonName') ?? $string('title') ?? 'Friseursalon',
            tagline: $string('tagline'),
            logo: $logo instanceof ImageInterface ? $logo : null,
            owner: $string('ownerName'),
            street: $string('street'),
            postalCode: $string('postalCode'),
            city: $string('city'),
            district: $string('district'),
            phone: $string('phone'),
            email: $string('email'),
            notificationEmail: filter_var($string('notificationEmail'), FILTER_VALIDATE_EMAIL) ?: null,
            whatsapp: $string('whatsapp'),
            mapsUrl: self::safeUrl($string('mapsUrl')),
            parkingInfo: $string('parkingInfo'),
            hours: $hours,
            hoursNote: $string('hoursNote'),
            instagramUrl: self::safeUrl($string('instagramUrl')),
            facebookUrl: self::safeUrl($string('facebookUrl')),
            tiktokUrl: self::safeUrl($string('tiktokUrl')),
            googleReviewsUrl: self::safeUrl($string('googleReviewsUrl')),
            instagramAccessToken: $string('instagramAccessToken'),
            theme: in_array($theme, self::THEMES, true) ? $theme : 'classic',
            // validated again here as it ends up in a style attribute
            accentColor: $accent !== null && preg_match('/^#[0-9a-fA-F]{6}$/', $accent) ? $accent : null,
            bookingLabel: $string('bookingButtonLabel') ?? 'Termin anfragen',
            externalBookingUrl: self::safeUrl($string('externalBookingUrl')),
        );
    }

    /** the custom accent or the default accent of the theme, e.g. for e-mails */
    public function accent(): string
    {
        return $this->accentColor ?? match ($this->theme) {
            'modern' => '#c0675a',
            'natur' => '#5f8065',
            'barber' => '#c49a3a',
            'pastell' => '#a2687a',
            default => '#a8834f',
        };
    }

    public function zipCity(): ?string
    {
        $value = trim(($this->postalCode ?? '') . ' ' . ($this->city ?? ''));
        return $value !== '' ? $value : null;
    }

    public function phoneHref(): ?string
    {
        if ($this->phone === null) {
            return null;
        }
        $digits = preg_replace('/[^\d+]/', '', $this->phone) ?? '';
        return $digits !== '' ? 'tel:' . $digits : null;
    }

    public function emailHref(): ?string
    {
        return $this->email !== null && filter_var($this->email, FILTER_VALIDATE_EMAIL) ? 'mailto:' . $this->email : null;
    }

    public function whatsappHref(): ?string
    {
        $digits = preg_replace('/\D/', '', $this->whatsapp ?? '') ?? '';
        return $digits !== '' ? 'https://wa.me/' . $digits : null;
    }

    public function instagramHandle(): ?string
    {
        if ($this->instagramUrl === null) {
            return null;
        }
        $path = trim((string)parse_url($this->instagramUrl, PHP_URL_PATH), '/');
        return $path !== '' ? '@' . explode('/', $path)[0] : null;
    }

    /**
     * @return list<array{day: string, label: string, time: string, closed: bool, today: bool}>
     */
    public function hourRows(\DateTimeImmutable $now): array
    {
        $today = $now->format('l');
        $rows = [];
        foreach (self::DAYS as $day => $label) {
            $rows[] = [
                'day' => $day,
                'label' => $label,
                'time' => $this->hours[$day] ?? 'geschlossen',
                'closed' => $this->hours[$day] === null,
                'today' => $day === $today,
            ];
        }
        return $rows;
    }

    public function hasHours(): bool
    {
        return array_filter($this->hours) !== [];
    }

    private static function safeUrl(?string $url): ?string
    {
        return $url !== null && preg_match('#^https?://#i', $url) ? $url : null;
    }
}
