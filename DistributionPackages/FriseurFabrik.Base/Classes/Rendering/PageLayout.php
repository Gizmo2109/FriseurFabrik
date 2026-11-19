<?php

declare(strict_types=1);

namespace FriseurFabrik\Base\Rendering;

use FriseurFabrik\Base\Components\Layout\Document;
use FriseurFabrik\Base\Components\Layout\MobileBar;
use Neos\ContentRepository\Core\Projection\ContentGraph\Filter\FindChildNodesFilter;
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

        $isEdit = $context->renderingMode->isEdit;
        $pageTitle = $isHome ? null : $this->title($document);
        $title ??= $this->string($document, 'titleOverride') ?? Seo::title($data, $pageTitle);
        $description = $this->string($document, 'metaDescription') ?? Seo::description($data, $pageTitle);
        $canonical = $isEdit || $isCustomPage ? null : $this->canonical($context, $document);
        $logo = $this->images->uri($data->logo, $request, 1200);
        $ogImage = $this->shareImage($context, $document) ?? $this->shareImage($context, $site) ?? $logo;
        $robots = implode(', ', array_filter([
            $isCustomPage || $document->getProperty('metaRobotsNoindex') === true ? 'noindex' : null,
            $document->getProperty('metaRobotsNofollow') === true ? 'nofollow' : null,
        ]));
        if (!$isEdit) {
            // not in edit mode: the inline editors must keep the stored markup
            $main = StringComponent::fromHtmlString(Seo::ensureH1($main->render()));
        }

        $head = [];
        if ($isHome) {
            $head[] = StringComponent::fromHtmlString(Seo::jsonLd($data, $isEdit ? null : (string)$context->neos->getNodeUri($site, true), $ogImage, $logo));
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
        if ($neosBody = $this->neosStuffFactory->tryGetBodyStuff($isEdit)) {
            $bodyEnd[] = $neosBody;
        }

        return new HtmlDocument(Document::create(
            lang: 'de',
            title: $title,
            description: $description,
            keywords: $this->string($document, 'metaKeywords'),
            canonical: $canonical,
            robots: $robots !== '' ? $robots : null,
            siteName: $data->name,
            ogType: $this->string($document, 'openGraphType') ?? 'website',
            ogTitle: $this->string($document, 'openGraphTitle') ?? $title,
            ogDescription: $this->string($document, 'openGraphDescription') ?? $description,
            ogImage: $ogImage,
            googleVerification: $isHome ? $this->string($site, 'metaGoogleSiteVerification') : null,
            theme: $data->theme,
            editMode: $isEdit,
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

    /** the SEO tab's canonical link (another page or URL) wins over the page's own URL */
    private function canonical(NeosContext $context, Node $document): string
    {
        $link = $this->string($document, 'canonicalLink');
        $uri = $link !== null ? $context->neos->tryResolveLinkUri($link, $context->subgraph, true) : null;
        return (string)($uri ?? $context->neos->getNodeUri($document, true));
    }

    /** image for link previews (WhatsApp, Facebook …): the SEO tab's image, else the photo of the page's first hero */
    private function shareImage(NeosContext $context, Node $document): ?string
    {
        $image = $this->images->uri($document->getProperty('openGraphImage'), $context->request, 1200);
        if ($image !== null) {
            return $image;
        }
        $main = $context->subgraph->findNodeByPath(NodeName::fromString('main'), $document->aggregateId);
        $hero = $main ? $context->subgraph->findChildNodes(
            $main->aggregateId,
            FindChildNodesFilter::create(nodeTypes: 'FriseurFabrik.Base:Content.Hero', pagination: ['limit' => 1]),
        )->first() : null;
        // 1200 × 630 is the preview format of Facebook and WhatsApp
        return $hero ? $this->images->uri($hero->getProperty('image'), $context->request, 1200, 630 / 1200) : null;
    }

    private function asset(NeosContext $context, string $path): string
    {
        $file = $this->packageManager->getPackage(self::PACKAGE)->getResourcesPath() . 'Public/' . $path;
        $version = is_file($file) ? substr(md5((string)filemtime($file)), 0, 8) : '0';
        return $context->neos->getStaticResourceUri(self::PACKAGE, $path) . '?v=' . $version;
    }
}
