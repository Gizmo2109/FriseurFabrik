<?php

declare(strict_types=1);

namespace FriseurFabrik\Base\Components\Content;

use FriseurFabrik\Base\Components\Atom\Icon;
use FriseurFabrik\Base\Components\Data\IconName;
use FriseurFabrik\Base\Components\Data\Img;
use PackageFactory\ComponentEngine as _;

#[\Neos\Flow\Annotations\Proxy(false)]
final readonly class TeamMember implements _\ComponentInterface
{
    private function __construct(
        private ?Img $photo,
        private string $initials,
        private ?_\ComponentInterface $name,
        private ?_\ComponentInterface $role,
        private ?_\ComponentInterface $bio,
        private ?string $instagramHref,
        private Icon $_21_137_Icon,
    ) {
    }

    public static function create(
        ?Img $photo,
        string $initials,
        _\ComponentInterface|string|null $name,
        _\ComponentInterface|string|null $role,
        _\ComponentInterface|string|null $bio,
        ?string $instagramHref,
    ): self {
        return new self(
            photo: $photo,
            initials: $initials,
            name: is_string($name) ? _\StringComponent::fromString($name) : $name,
            role: is_string($role) ? _\StringComponent::fromString($role) : $role,
            bio: is_string($bio) ? _\StringComponent::fromString($bio) : $bio,
            instagramHref: $instagramHref,
            _21_137_Icon: Icon::create(
                name: IconName::INSTAGRAM,
            ),
        );
    }

    #[\Override]
    public function render(): string
    {
        return '<article class="member"><div class="member__photo">' . (($this->photo !== null) ? '<img' . (($this->photo?->src) !== null ? (((($temp = $this->photo?->src) === null) ? '' : ' src="' . _\Util::escapeAttributeValue($temp) . '"')) : (' src=""')) . ((($temp = $this->photo?->srcset) === null) ? '' : ' srcset="' . _\Util::escapeAttributeValue($temp) . '"') . ' sizes="(min-width: 900px) 25vw, 50vw"' . (($this->photo?->alt) !== null ? (((($temp = $this->photo?->alt) === null) ? '' : ' alt="' . _\Util::escapeAttributeValue($temp) . '"')) : (' alt=""')) . ((($temp = $this->photo?->width) === null) ? '' : ' width="' . $temp . '"') . ((($temp = $this->photo?->height) === null) ? '' : ' height="' . $temp . '"') . ' loading="lazy" />' : '<span class="member__initials" aria-hidden="true">' . _\Util::escapeText($this->initials) . '</span>') . '</div><h3 class="member__name">' . ((($temp = $this->name) === null) ? '' : $temp->render()) . '</h3><p class="member__role">' . ((($temp = $this->role) === null) ? '' : $temp->render()) . '</p><p class="member__bio">' . ((($temp = $this->bio) === null) ? '' : $temp->render()) . '</p>' . (($this->instagramHref !== null) ? '<a class="member__social"' . (($this->instagramHref) !== null ? (((($temp = $this->instagramHref) === null) ? '' : ' href="' . _\Util::escapeAttributeValue($temp) . '"')) : (' href=""')) . ' target="_blank" rel="noopener" aria-label="Instagram">' . $this->_21_137_Icon->render() . '</a>' : '') . '</article>';
    }
}
