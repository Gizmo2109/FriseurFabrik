<?php

declare(strict_types=1);

namespace FriseurFabrik\Base\Components\Layout;

use PackageFactory\ComponentEngine as _;

#[\Neos\Flow\Annotations\Proxy(false)]
final readonly class Document implements _\ComponentInterface
{
    private function __construct(
        private string $lang,
        private string $title,
        private ?string $description,
        private ?string $keywords,
        private ?string $canonical,
        private ?string $robots,
        private string $siteName,
        private string $ogType,
        private string $ogTitle,
        private ?string $ogDescription,
        private ?string $ogImage,
        private ?string $googleVerification,
        private string $theme,
        private bool $editMode,
        private ?string $styleAttr,
        private string $stylesheet,
        private string $script,
        private ?string $favicon,
        private ?_\ComponentInterface $head,
        private ?_\ComponentInterface $header,
        private ?_\ComponentInterface $main,
        private ?_\ComponentInterface $footer,
        private ?_\ComponentInterface $bodyEnd,
    ) {
    }

    public static function create(
        string $lang,
        string $title,
        ?string $description,
        ?string $keywords,
        ?string $canonical,
        ?string $robots,
        string $siteName,
        string $ogType,
        string $ogTitle,
        ?string $ogDescription,
        ?string $ogImage,
        ?string $googleVerification,
        string $theme,
        bool $editMode,
        ?string $styleAttr,
        string $stylesheet,
        string $script,
        ?string $favicon,
        _\ComponentInterface|string|null $head,
        _\ComponentInterface|string|null $header,
        _\ComponentInterface|string|null $main,
        _\ComponentInterface|string|null $footer,
        _\ComponentInterface|string|null $bodyEnd,
    ): self {
        return new self(
            lang: $lang,
            title: $title,
            description: $description,
            keywords: $keywords,
            canonical: $canonical,
            robots: $robots,
            siteName: $siteName,
            ogType: $ogType,
            ogTitle: $ogTitle,
            ogDescription: $ogDescription,
            ogImage: $ogImage,
            googleVerification: $googleVerification,
            theme: $theme,
            editMode: $editMode,
            styleAttr: $styleAttr,
            stylesheet: $stylesheet,
            script: $script,
            favicon: $favicon,
            head: is_string($head) ? _\StringComponent::fromString($head) : $head,
            header: is_string($header) ? _\StringComponent::fromString($header) : $header,
            main: is_string($main) ? _\StringComponent::fromString($main) : $main,
            footer: is_string($footer) ? _\StringComponent::fromString($footer) : $footer,
            bodyEnd: is_string($bodyEnd) ? _\StringComponent::fromString($bodyEnd) : $bodyEnd,
        );
    }

    #[\Override]
    public function render(): string
    {
        return '<html lang="' . _\Util::escapeAttributeValue($this->lang) . '" data-theme="' . _\Util::escapeAttributeValue($this->theme) . '"' . ($this->editMode ? ' data-edit-mode="true"' : '') . '><head><meta charset="UTF-8" /><meta name="viewport" content="width=device-width, initial-scale=1" /><title>' . _\Util::escapeText($this->title) . '</title>' . (($this->description !== null) ? '<meta name="description"' . (($this->description) !== null ? (((($temp = $this->description) === null) ? '' : ' content="' . _\Util::escapeAttributeValue($temp) . '"')) : (' content=""')) . ' />' : '') . (($this->keywords !== null) ? '<meta name="keywords"' . (($this->keywords) !== null ? (((($temp = $this->keywords) === null) ? '' : ' content="' . _\Util::escapeAttributeValue($temp) . '"')) : (' content=""')) . ' />' : '') . (($this->robots !== null) ? '<meta name="robots"' . (($this->robots) !== null ? (((($temp = $this->robots) === null) ? '' : ' content="' . _\Util::escapeAttributeValue($temp) . '"')) : (' content=""')) . ' />' : '') . (($this->canonical !== null) ? '<link rel="canonical"' . (($this->canonical) !== null ? (((($temp = $this->canonical) === null) ? '' : ' href="' . _\Util::escapeAttributeValue($temp) . '"')) : (' href=""')) . ' />' : '') . (($this->googleVerification !== null) ? '<meta name="google-site-verification"' . (($this->googleVerification) !== null ? (((($temp = $this->googleVerification) === null) ? '' : ' content="' . _\Util::escapeAttributeValue($temp) . '"')) : (' content=""')) . ' />' : '') . '<meta property="og:type" content="' . _\Util::escapeAttributeValue($this->ogType) . '" /><meta property="og:site_name" content="' . _\Util::escapeAttributeValue($this->siteName) . '" /><meta property="og:locale" content="de_DE" /><meta property="og:title" content="' . _\Util::escapeAttributeValue($this->ogTitle) . '" />' . (($this->canonical !== null) ? '<meta property="og:url"' . (($this->canonical) !== null ? (((($temp = $this->canonical) === null) ? '' : ' content="' . _\Util::escapeAttributeValue($temp) . '"')) : (' content=""')) . ' />' : '') . (($this->ogDescription !== null) ? '<meta property="og:description"' . (($this->ogDescription) !== null ? (((($temp = $this->ogDescription) === null) ? '' : ' content="' . _\Util::escapeAttributeValue($temp) . '"')) : (' content=""')) . ' />' : '') . (($this->ogImage !== null) ? '<meta property="og:image"' . (($this->ogImage) !== null ? (((($temp = $this->ogImage) === null) ? '' : ' content="' . _\Util::escapeAttributeValue($temp) . '"')) : (' content=""')) . ' />' : '') . (($this->ogImage !== null) ? '<meta name="twitter:card" content="summary_large_image" />' : '') . (($this->favicon !== null) ? '<link rel="icon"' . (($this->favicon) !== null ? (((($temp = $this->favicon) === null) ? '' : ' href="' . _\Util::escapeAttributeValue($temp) . '"')) : (' href=""')) . ' />' : '') . '<link rel="stylesheet" href="' . _\Util::escapeAttributeValue($this->stylesheet) . '" /><script src="' . _\Util::escapeAttributeValue($this->script) . '" defer></script>' . ((($temp = $this->head) === null) ? '' : $temp->render()) . '</head><body' . ((($temp = $this->styleAttr) === null) ? '' : ' style="' . _\Util::escapeAttributeValue($temp) . '"') . '><a class="skip-link" href="#main">Zum Inhalt springen</a>' . ((($temp = $this->header) === null) ? '' : $temp->render()) . '<main id="main">' . ((($temp = $this->main) === null) ? '' : $temp->render()) . '</main>' . ((($temp = $this->footer) === null) ? '' : $temp->render()) . ((($temp = $this->bodyEnd) === null) ? '' : $temp->render()) . '</body></html>';
    }
}
