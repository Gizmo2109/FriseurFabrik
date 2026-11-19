<?php

declare(strict_types=1);

namespace FriseurFabrik\Base\Rendering;

use FriseurFabrik\Base\Components\Atom\HoursRow;
use FriseurFabrik\Base\Components\Atom\SocialLink;
use FriseurFabrik\Base\Components\Data\IconName;
use Neos\ContentRepository\Core\Projection\ContentGraph\Filter\FindReferencesFilter;
use Neos\ContentRepository\Core\Projection\ContentGraph\Node;
use Neos\Flow\Annotations as Flow;
use PackageFactory\ComponentEngine\ComponentList;
use PackageFactory\ComponentEngine\ComponentListInterface;
use PackageFactory\Neos\ComponentEngine\NeosContext;

/**
 * Salon wide data and links needed by the layout and several content elements
 */
#[Flow\Scope('singleton')]
class SalonContext
{
    /** anchor of the appointment form section */
    public const BOOKING_ANCHOR = 'termin';

    /** @var array<string,SalonData> */
    private array $dataBySite = [];

    public function data(NeosContext $context): SalonData
    {
        $key = $context->siteNode->aggregateId->value . '@' . $context->siteNode->workspaceName->value;
        return $this->dataBySite[$key] ??= SalonData::fromSiteNode($context->siteNode);
    }

    public function bookingHref(NeosContext $context): string
    {
        $data = $this->data($context);
        if ($data->externalBookingUrl !== null) {
            return $data->externalBookingUrl;
        }
        $page = $this->reference($context, 'bookingPage') ?? $context->siteNode;
        return (string)$context->neos->getNodeUri($page)->withFragment(self::BOOKING_ANCHOR);
    }

    public function privacyHref(NeosContext $context): ?string
    {
        $page = $this->reference($context, 'privacyPage');
        return $page ? (string)$context->neos->getNodeUri($page) : null;
    }

    /** @return ComponentListInterface<HoursRow> */
    public function hourRows(NeosContext $context): ComponentListInterface
    {
        return ComponentList::list(...array_map(
            static fn (array $row) => HoursRow::create(day: $row['label'], time: $row['time'], closed: $row['closed'], today: $row['today']),
            $this->data($context)->hourRows(self::now()),
        ));
    }

    /** @return ComponentListInterface<SocialLink> */
    public function socialLinks(NeosContext $context): ComponentListInterface
    {
        $data = $this->data($context);
        $links = [];
        foreach ([
            [IconName::INSTAGRAM, 'Instagram', $data->instagramUrl],
            [IconName::FACEBOOK, 'Facebook', $data->facebookUrl],
            [IconName::TIKTOK, 'TikTok', $data->tiktokUrl],
            [IconName::STAR, 'Google-Bewertungen', $data->googleReviewsUrl],
        ] as [$icon, $label, $href]) {
            if ($href !== null) {
                $links[] = SocialLink::create(icon: $icon, label: $label, href: $href);
            }
        }
        return ComponentList::list(...$links);
    }

    public static function now(): \DateTimeImmutable
    {
        return new \DateTimeImmutable('now', new \DateTimeZone('Europe/Berlin'));
    }

    private function reference(NeosContext $context, string $name): ?Node
    {
        return $context->subgraph->findReferences(
            $context->siteNode->aggregateId,
            FindReferencesFilter::create(referenceName: $name),
        )->getNodes()->first();
    }
}
