<?php

declare(strict_types=1);

namespace FriseurFabrik\Base;

use FriseurFabrik\Base\Media\SalonMedia;
use Neos\Flow\Core\Bootstrap;
use Neos\Flow\Package\Package as BasePackage;
use Neos\Media\Domain\Service\AssetService;

class Package extends BasePackage
{
    public function boot(Bootstrap $bootstrap): void
    {
        // appointment times are wall clock times of the salons; the PHP image has no timezone configured (UTC)
        date_default_timezone_set('Europe/Berlin');

        $bootstrap->getSignalSlotDispatcher()->connect(AssetService::class, 'assetCreated', SalonMedia::class, 'assignUploadToSalon');
    }
}
