<?php

declare(strict_types=1);

namespace FriseurFabrik\Base\Rendering;

use FriseurFabrik\Base\Components\Layout\Document;
use FriseurFabrik\Base\Components\Layout\MobileBar;
use Neos\ContentRepository\Core\Projection\ContentGraph\Node;
use Neos\ContentRepository\Core\SharedModel\Node\NodeName;
use Neos\Neos\Domain\NodeMapping\NodeMapperInterface;
use PackageFactory\Neos\ComponentEngine\Integration\ContentRenderer;
use PackageFactory\Neos\ComponentEngine\Integration\RenderingUseCase;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\Package\PackageManager;
use PackageFactory\ComponentEngine\ComponentInterface;
use PackageFactory\ComponentEngine\ComponentList;
use PackageFactory\ComponentEngine\StringComponent;
use PackageFactory\Neos\ComponentEngine\Integration\NeosStuffFactory;
use PackageFactory\Neos\ComponentEngine\NeosContext;

/**
 * Renders a complete page (document skeleton, shared header and footer, main content) for any salon document
 */
#[Flow\Scope('singleton')]
class PageLayout
{
    private const PACKAGE = 'FriseurFabrik.Base';

    public function __construct(
        private readonly Collections $collections,
        private readonly NeosStuffFactory $neosStuffFactory,
        private readonly SalonContext $salon,
        private readonly Images $images,
        private readonly PackageManager $packageManager,
        private readonly ContentRenderer $contentRenderer,
        private readonly NodeMapperInterface $nodeMapper,
    ) {
    }

    /**
     * @param ComponentInterface|null $main content instead of the document's main collection (pages without a node, e.g. appointment answers)
     */
    public function render(NeosContext $context, ?ComponentInterface $main = null, ?string $title = null): ComponentInterface
    {
        $data = $this->salon->data($context);
        $site = $context->siteNode;
        $document = $context->documentNode;
        $isCustomPage = $main !== null;
        $isHome = !$isCustomPage && $document->aggregateId->equals($site->aggregateId);
        $bookingHref = $this->salon->bookingHref($context);
        $request = $context->request;

        $main ??= $this->collections->render($context, $document, 'main', 'main-content');
        $header = $this->sharedElement($context, 'header');
        $footer = $this->sharedElement($context, 'footer');

        $head = [];
        if ($isHome) {
            $head[] = StringComponent::fromHtmlString($this->jsonLd($context, $data));
        }
        if ($neosHead = $this->neosStuffFactory->tryGetHeadStuff($context)) {
            $head[] = $neosHead;
        }
        $bodyEnd = [MobileBar::create(
            phoneHref: $data->phoneHref(),
            whatsappHref: $data->whatsappHref(),
            ctaLabel: $data->bookingLabel,
            ctaHref: $bookingHref,
        )];
        if ($neosBody = $this->neosStuffFactory->tryGetBodyStuff($context->renderingMode->isEdit)) {
            $bodyEnd[] = $neosBody;
        }

        $title ??= $this->string($document, 'titleOverride') ?? ($isHome
            ? $data->name . ($data->tagline ? ' – ' . $data->tagline : '')
            : $this->title($document) . ' | ' . $data->name);

        return new HtmlDocument(Document::create(
            lang: 'de',
            title: $title,
            description: $this->string($document, 'metaDescription') ?? $this->defaultDescription($data),
            canonical: $context->renderingMode->isEdit || $isCustomPage ? null : (string)$context->neos->getNodeUri($document, true),
            ogImage: $data->logo ? $this->images->uri($data->logo, $request, 1200) : null,
            robots: $isCustomPage || $document->getProperty('metaRobotsNoindex') === true ? 'noindex' : null,
            theme: $data->theme,
            editMode: $context->renderingMode->isEdit,
            styleAttr: $data->accentColor ? '--accent: ' . $data->accentColor : null,
            stylesheet: $this->asset($context, 'Styles/main.css'),
            script: $this->asset($context, 'Scripts/main.js'),
            favicon: $this->asset($context, 'Images/favicon.svg'),
            head: ComponentList::list(...$head),
            header: $header,
            main: $main,
            footer: $footer,
            bodyEnd: ComponentList::list(...$bodyEnd),
        ));
    }

    private function title(Node $node): string
    {
        return $this->string($node, 'title') ?? '';
    }

    private function string(Node $node, string $property): ?string
    {
        $value = $node->getProperty($property);
        return is_string($value) && trim($value) !== '' ? trim($value) : null;
    }

    /**
     * Header and footer are tethered nodes of the site node: rendered (and editable) on every page
     */
    private function sharedElement(NeosContext $context, string $name): ComponentInterface
    {
        $node = $context->subgraph->findNodeByPath(NodeName::fromString($name), $context->siteNode->aggregateId);
        if ($node === null) {
            return StringComponent::fromString('');
        }
        return $this->contentRenderer->render(
            $context->with(current: $this->nodeMapper->map($node, $context->subgraph)),
            RenderingUseCase::CONTENT,
        );
    }

    private function defaultDescription(SalonData $data): string
    {
        $where = trim(($data->city ?? '') . ($data->district ? '-' . $data->district : ''));
        return $data->name . ' – Ihr Friseur' . ($where !== '' ? ' in ' . $where : '')
            . '. Preise, Team, Öffnungszeiten und Terminanfrage online.'
            . ($data->phone ? ' Telefon ' . $data->phone . '.' : '');
    }

    /** schema.org HairSalon data for search engines */
    private function jsonLd(NeosContext $context, SalonData $data): string
    {
        $openingHours = [];
        foreach ($data->hours as $day => $hours) {
            if ($hours !== null && preg_match('/(\d{1,2})[:.](\d{2})\s*[-–—]\s*(\d{1,2})[:.](\d{2})/u', $hours, $m)) {
                $openingHours[] = [
                    '@type' => 'OpeningHoursSpecification',
                    'dayOfWeek' => $day,
                    'opens' => sprintf('%02d:%s', $m[1], $m[2]),
                    'closes' => sprintf('%02d:%s', $m[3], $m[4]),
                ];
            }
        }
        $json = array_filter([
            '@context' => 'https://schema.org',
            '@type' => 'HairSalon',
            'name' => $data->name,
            'url' => $context->renderingMode->isEdit ? null : (string)$context->neos->getNodeUri($context->siteNode, true),
            'telephone' => $data->phone,
            'email' => $data->email,
            'address' => array_filter([
                '@type' => 'PostalAddress',
                'streetAddress' => $data->street,
                'postalCode' => $data->postalCode,
                'addressLocality' => $data->city,
                'addressCountry' => 'DE',
            ]),
            'openingHoursSpecification' => $openingHours ?: null,
            'sameAs' => array_values(array_filter([$data->instagramUrl, $data->facebookUrl, $data->tiktokUrl])) ?: null,
        ], static fn ($value) => $value !== null);

        return '<script type="application/ld+json">'
            . json_encode($json, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_THROW_ON_ERROR)
            . '</script>';
    }

    private function asset(NeosContext $context, string $path): string
    {
        $file = $this->packageManager->getPackage(self::PACKAGE)->getResourcesPath() . 'Public/' . $path;
        $version = is_file($file) ? substr(md5((string)filemtime($file)), 0, 8) : '0';
        return $context->neos->getStaticResourceUri(self::PACKAGE, $path) . '?v=' . $version;
    }
}
