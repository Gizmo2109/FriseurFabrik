<?php

declare(strict_types=1);

namespace FriseurFabrik\Base\Components\Data;

enum IconName : string
{
    case SCISSORS = 'scissors';
    case COLOR = 'color';
    case LEAF = 'leaf';
    case HEART = 'heart';
    case STAR = 'star';
    case COFFEE = 'coffee';
    case CALENDAR = 'calendar';
    case PARKING = 'parking';
    case CHILD = 'child';
    case BEARD = 'beard';
    case GIFT = 'gift';
    case RING = 'ring';
    case PHONE = 'phone';
    case MAIL = 'mail';
    case PIN = 'pin';
    case CLOCK = 'clock';
    case INSTAGRAM = 'instagram';
    case FACEBOOK = 'facebook';
    case TIKTOK = 'tiktok';
    case WHATSAPP = 'whatsapp';
    case MENU = 'menu';
    case CLOSE = 'close';
    case ARROW = 'arrow';
    case CHECK = 'check';
    case PLAY = 'play';

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
