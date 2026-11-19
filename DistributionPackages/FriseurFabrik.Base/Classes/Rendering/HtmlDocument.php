<?php

declare(strict_types=1);

namespace FriseurFabrik\Base\Rendering;

use Neos\Flow\Annotations as Flow;
use PackageFactory\ComponentEngine\ComponentInterface;

/** Prepends the doctype, which CPX cannot express */
#[Flow\Proxy(false)]
final readonly class HtmlDocument implements ComponentInterface
{
    public function __construct(private ComponentInterface $document)
    {
    }

    #[\Override]
    public function render(): string
    {
        return "<!DOCTYPE html>\n" . $this->document->render();
    }
}
