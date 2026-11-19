<?php

declare(strict_types=1);

namespace FriseurFabrik\Base;

use Neos\Flow\Core\Bootstrap;
use Neos\Flow\Package\Package as BasePackage;

class Package extends BasePackage
{
    public function boot(Bootstrap $bootstrap): void
    {
        // appointment times are wall clock times of the salons; the PHP image has no timezone configured (UTC)
        date_default_timezone_set('Europe/Berlin');
    }
}
