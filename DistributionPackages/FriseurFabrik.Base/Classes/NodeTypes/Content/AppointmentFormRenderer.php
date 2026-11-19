<?php

declare(strict_types=1);

namespace FriseurFabrik\Base\NodeTypes\Content;

use FriseurFabrik\Base\Components\Atom\SelectGroup;
use FriseurFabrik\Base\Components\Atom\SelectOption;
use FriseurFabrik\Base\Components\Content\AppointmentForm;
use FriseurFabrik\Base\Rendering\Editable;
use FriseurFabrik\Base\Rendering\SalonContext;
use FriseurFabrik\Base\Rendering\SalonData;
use FriseurFabrik\Base\Rendering\Sections;
use Neos\ContentRepository\Core\Projection\ContentGraph\Filter\FindChildNodesFilter;
use Neos\ContentRepository\Core\Projection\ContentGraph\Filter\FindDescendantNodesFilter;
use Neos\ContentRepository\Core\SharedModel\Node\NodeName;
use PackageFactory\ComponentEngine\ComponentInterface;
use PackageFactory\ComponentEngine\ComponentList;
use PackageFactory\Neos\ComponentEngine\Integration\ContentNodeRendererInterface;
use PackageFactory\Neos\ComponentEngine\NeosContext;

/**
 * Appointment request form. Services and stylists are collected from the price lists and team members of the whole site.
 */
final class AppointmentFormRenderer implements ContentNodeRendererInterface
{
    /** JavaScript Date.getDay() numbers */
    private const WEEKDAYS = ['Sunday' => 0, 'Monday' => 1, 'Tuesday' => 2, 'Wednesday' => 3, 'Thursday' => 4, 'Friday' => 5, 'Saturday' => 6];

    public function __construct(private readonly SalonContext $salon)
    {
    }

    public function renderAsContent(NeosContext $context): ComponentInterface
    {
        $data = $this->salon->data($context);
        $siteId = $context->siteNode->aggregateId;

        $groups = [];
        foreach ($context->subgraph->findDescendantNodes($siteId, FindDescendantNodesFilter::create(nodeTypes: 'FriseurFabrik.Base:Content.PriceList')) as $priceList) {
            $category = Editable::plain($priceList, 'title');
            $collection = $context->subgraph->findNodeByPath(NodeName::fromString('items'), $priceList->aggregateId);
            $options = [];
            foreach ($collection ? $context->subgraph->findChildNodes($collection->aggregateId, FindChildNodesFilter::create()) : [] as $item) {
                $name = Editable::plain($item, 'name');
                if ($name !== '') {
                    $value = $category !== '' ? $category . ': ' . $name : $name;
                    $options[$value] = SelectOption::create(value: $value, label: $name);
                }
            }
            if ($options !== []) {
                $groups[] = SelectGroup::create(label: $category !== '' ? $category : 'Leistungen', options: ComponentList::list(...array_values($options)));
            }
        }

        $stylists = [];
        foreach ($context->subgraph->findDescendantNodes($siteId, FindDescendantNodesFilter::create(nodeTypes: 'FriseurFabrik.Base:Content.TeamMember')) as $member) {
            $name = Editable::plain($member, 'name');
            if ($name !== '' && $member->getProperty('bookable') !== false) {
                $stylists[$name] = SelectOption::create(value: $name, label: $name);
            }
        }

        $siteName = $context->siteNode->name?->value ?? '';

        return Sections::wrap($context, AppointmentForm::create(
            title: Editable::of($context, 'title'),
            intro: Editable::of($context, 'intro'),
            successText: Editable::of($context, 'successText'),
            action: (string)$context->neos->getActionUri('FriseurFabrik.Base', 'Appointment', 'submit'),
            site: $siteName,
            returnUri: (string)$context->neos->getNodeUri($context->documentNode),
            services: ComponentList::list(...$groups),
            stylists: ComponentList::list(...array_values($stylists)),
            minDate: SalonContext::now()->format('Y-m-d'),
            closedDays: $this->closedDays($data),
            privacyHref: $this->salon->privacyHref($context),
            phoneLabel: $data->phone,
            phoneHref: $data->phoneHref(),
        ), 'appointment', SalonContext::BOOKING_ANCHOR);
    }

    private function closedDays(SalonData $data): string
    {
        if (!$data->hasHours()) {
            return '';
        }
        return implode(',', array_map(
            static fn (string $day) => self::WEEKDAYS[$day],
            array_keys(array_filter($data->hours, static fn (?string $hours) => $hours === null)),
        ));
    }
}
