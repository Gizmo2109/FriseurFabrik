<?php

declare(strict_types=1);

namespace FriseurFabrik\Base\Components\Mail;

use FriseurFabrik\Base\Components\Mail\MailButton;
use FriseurFabrik\Base\Components\Mail\MailFact;
use PackageFactory\ComponentEngine as _;

#[\Neos\Flow\Annotations\Proxy(false)]
final readonly class Mail implements _\ComponentInterface
{
    /**
     * @param _\ComponentListInterface<MailFact> $facts
     * @param _\ComponentListInterface<MailButton> $buttons
     */
    private function __construct(
        private string $salonName,
        private string $accent,
        private string $preheader,
        private string $heading,
        private ?_\ComponentInterface $intro,
        private _\ComponentListInterface $facts,
        private ?_\ComponentInterface $note,
        private _\ComponentListInterface $buttons,
        private string $footer,
    ) {
    }

    /**
     * @param _\ComponentListInterface<MailFact> $facts
     * @param _\ComponentListInterface<MailButton> $buttons
     */
    public static function create(
        string $salonName,
        string $accent,
        string $preheader,
        string $heading,
        _\ComponentInterface|string|null $intro,
        _\ComponentListInterface $facts,
        _\ComponentInterface|string|null $note,
        _\ComponentListInterface $buttons,
        string $footer,
    ): self {
        return new self(
            salonName: $salonName,
            accent: $accent,
            preheader: $preheader,
            heading: $heading,
            intro: is_string($intro) ? _\StringComponent::fromString($intro) : $intro,
            facts: $facts,
            note: is_string($note) ? _\StringComponent::fromString($note) : $note,
            buttons: $buttons,
            footer: $footer,
        );
    }

    #[\Override]
    public function render(): string
    {
        return '<html lang="de"><head><meta charset="UTF-8" /><meta name="viewport" content="width=device-width, initial-scale=1" /><title>' . _\Util::escapeText($this->heading) . '</title></head><body style="margin:0;padding:0;background:#f4f1ee;font-family:Helvetica,Arial,sans-serif;color:#222222;"><div style="display:none;max-height:0;overflow:hidden;">' . _\Util::escapeText($this->preheader) . '</div><table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f4f1ee;padding:24px 12px;"><tr><td align="center"><table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:600px;background:#ffffff;border-radius:8px;"><tr><td style="background:' . _\Util::escapeAttributeValue($this->accent) . ';color:#ffffff;padding:22px 32px;font-size:20px;font-weight:bold;border-radius:8px 8px 0 0;">' . _\Util::escapeText($this->salonName) . '</td></tr><tr><td style="padding:32px;"><h1 style="margin:0 0 16px;font-size:22px;line-height:1.3;color:#222222;">' . _\Util::escapeText($this->heading) . '</h1><div style="font-size:16px;line-height:1.6;">' . ((($temp = $this->intro) === null) ? '' : $temp->render()) . '</div>' . ((count($this->facts) > 0) ? '<table role="presentation" cellpadding="0" cellspacing="0" style="margin:20px 0;border-top:1px solid #eeeeee;border-bottom:1px solid #eeeeee;width:100%;">' . $this->facts->render() . '</table>' : '') . '<div style="font-size:16px;line-height:1.6;">' . ((($temp = $this->note) === null) ? '' : $temp->render()) . '</div>' . ((count($this->buttons) > 0) ? '<div style="margin-top:24px;">' . $this->buttons->render() . '</div>' : '') . '</td></tr><tr><td style="padding:18px 32px;background:#faf8f6;color:#777777;font-size:13px;line-height:1.5;border-radius:0 0 8px 8px;">' . _\Util::escapeText($this->footer) . '</td></tr></table></td></tr></table></body></html>';
    }
}
