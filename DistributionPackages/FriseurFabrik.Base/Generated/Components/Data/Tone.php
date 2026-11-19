<?php

declare(strict_types=1);

namespace FriseurFabrik\Base\Components\Data;

enum Tone : string
{
    case DEFAULT = 'default';
    case MUTED = 'muted';
    case BRAND = 'brand';
    case DARK = 'dark';

    public function asString(): string
    {
        return $this->value;
    }

    public function asText(): string
    {
        return $this->value;
    }

    public function asAttributeValue(): string
    {
        return $this->value;
    }
}
