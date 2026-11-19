<?php

declare(strict_types=1);

namespace FriseurFabrik\Base\Components\Content;

use PackageFactory\ComponentEngine as _;

#[\Neos\Flow\Annotations\Proxy(false)]
final readonly class FaqItem implements _\ComponentInterface
{
    private function __construct(
        private ?_\ComponentInterface $question,
        private ?_\ComponentInterface $answer,
    ) {
    }

    public static function create(
        _\ComponentInterface|string|null $question,
        _\ComponentInterface|string|null $answer,
    ): self {
        return new self(
            question: is_string($question) ? _\StringComponent::fromString($question) : $question,
            answer: is_string($answer) ? _\StringComponent::fromString($answer) : $answer,
        );
    }

    #[\Override]
    public function render(): string
    {
        return '<details class="faq__item"><summary class="faq__question">' . ((($temp = $this->question) === null) ? '' : $temp->render()) . '</summary><div class="faq__answer prose">' . ((($temp = $this->answer) === null) ? '' : $temp->render()) . '</div></details>';
    }
}
