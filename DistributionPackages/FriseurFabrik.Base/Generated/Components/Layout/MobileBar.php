<?php

declare(strict_types=1);

namespace FriseurFabrik\Base\Components\Layout;

use FriseurFabrik\Base\Components\Atom\Icon;
use FriseurFabrik\Base\Components\Data\IconName;
use PackageFactory\ComponentEngine as _;

#[\Neos\Flow\Annotations\Proxy(false)]
final readonly class MobileBar implements _\ComponentInterface
{
    private function __construct(
        private ?string $phoneHref,
        private ?string $whatsappHref,
        private string $ctaLabel,
        private string $ctaHref,
        private Icon $_10_77_Icon,
        private Icon $_11_114_Icon,
        private Icon $_12_73_Icon,
    ) {
    }

    public static function create(
        ?string $phoneHref,
        ?string $whatsappHref,
        string $ctaLabel,
        string $ctaHref,
    ): self {
        return new self(
            phoneHref: $phoneHref,
            whatsappHref: $whatsappHref,
            ctaLabel: $ctaLabel,
            ctaHref: $ctaHref,
            _10_77_Icon: Icon::create(
                name: IconName::PHONE,
            ),
            _11_114_Icon: Icon::create(
                name: IconName::WHATSAPP,
            ),
            _12_73_Icon: Icon::create(
                name: IconName::CALENDAR,
            ),
        );
    }

    #[\Override]
    public function render(): string
    {
        return '<div class="mobile-bar">' . (($this->phoneHref !== null) ? '<a class="mobile-bar__item"' . (($this->phoneHref) !== null ? (((($temp = $this->phoneHref) === null) ? '' : ' href="' . _\Util::escapeAttributeValue($temp) . '"')) : (' href=""')) . '>' . $this->_10_77_Icon->render() . '<span>Anrufen</span></a>' : '') . (($this->whatsappHref !== null) ? '<a class="mobile-bar__item"' . (($this->whatsappHref) !== null ? (((($temp = $this->whatsappHref) === null) ? '' : ' href="' . _\Util::escapeAttributeValue($temp) . '"')) : (' href=""')) . ' target="_blank" rel="noopener">' . $this->_11_114_Icon->render() . '<span>WhatsApp</span></a>' : '') . '<a class="mobile-bar__item mobile-bar__item--primary" href="' . _\Util::escapeAttributeValue($this->ctaHref) . '">' . $this->_12_73_Icon->render() . '<span>' . _\Util::escapeText($this->ctaLabel) . '</span></a></div>';
    }
}
