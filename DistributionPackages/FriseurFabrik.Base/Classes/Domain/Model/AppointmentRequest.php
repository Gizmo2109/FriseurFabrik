<?php

declare(strict_types=1);

namespace FriseurFabrik\Base\Domain\Model;

use Doctrine\ORM\Mapping as ORM;
use Neos\Flow\Annotations as Flow;

/**
 * An appointment request of a salon website and its life cycle (confirm, propose another time, decline, …).
 *
 * @Flow\Entity
 * @ORM\Table(indexes={@ORM\Index(columns={"sitenodename", "createdat"})})
 */
class AppointmentRequest
{
    /**
     * @ORM\Column(type="datetime_immutable")
     */
    protected \DateTimeImmutable $createdAt;

    /**
     * @ORM\Column(type="datetime_immutable", nullable=true)
     */
    protected ?\DateTimeImmutable $updatedAt = null;

    protected string $siteNodeName;

    protected string $name;

    protected string $phone;

    /**
     * @ORM\Column(nullable=true)
     */
    protected ?string $email;

    /**
     * @ORM\Column(nullable=true)
     */
    protected ?string $service;

    /**
     * @ORM\Column(nullable=true)
     */
    protected ?string $stylist;

    /**
     * @ORM\Column(type="date_immutable", nullable=true)
     */
    protected ?\DateTimeImmutable $preferredDate;

    /**
     * @ORM\Column(nullable=true)
     */
    protected ?string $preferredTime;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    protected ?string $message;

    protected string $status = 'neu';

    /**
     * confirmed or proposed start, depending on the status
     * @ORM\Column(type="datetime_immutable", nullable=true)
     */
    protected ?\DateTimeImmutable $appointmentStart = null;

    /**
     * @ORM\Column(nullable=true)
     */
    protected ?int $durationMinutes = null;

    /**
     * @ORM\Column(nullable=true)
     */
    protected ?string $assignedStylist = null;

    /**
     * secret for the links in the customer e-mails
     * @ORM\Column(length=64, unique=true)
     */
    protected string $token;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    protected ?string $internalNote = null;

    /**
     * "website" or "salon" (entered by the salon, e.g. after a phone call)
     */
    protected string $source = 'website';

    /**
     * entries: {at: ISO date, by: who, text: what happened}
     * @var array<mixed>
     * @ORM\Column(type="json")
     */
    protected array $history = [];

    public function __construct(
        string $siteNodeName,
        string $name,
        string $phone,
        ?string $email,
        ?string $service,
        ?string $stylist,
        ?\DateTimeImmutable $preferredDate,
        ?string $preferredTime,
        ?string $message,
        string $source = 'website',
    ) {
        $this->createdAt = new \DateTimeImmutable();
        $this->siteNodeName = $siteNodeName;
        $this->name = $name;
        $this->phone = $phone;
        $this->email = $email;
        $this->service = $service;
        $this->stylist = $stylist;
        $this->preferredDate = $preferredDate;
        $this->preferredTime = $preferredTime;
        $this->message = $message;
        $this->source = $source;
        $this->token = bin2hex(random_bytes(24));
    }

    public function confirm(\DateTimeImmutable $start, int $durationMinutes, ?string $stylist): void
    {
        $this->assertNotClosed();
        $this->status = AppointmentStatus::CONFIRMED->value;
        $this->appointmentStart = $start;
        $this->durationMinutes = $durationMinutes;
        $this->assignedStylist = $stylist;
    }

    public function propose(\DateTimeImmutable $start, int $durationMinutes, ?string $stylist): void
    {
        $this->assertNotClosed();
        $this->status = AppointmentStatus::PROPOSED->value;
        $this->appointmentStart = $start;
        $this->durationMinutes = $durationMinutes;
        $this->assignedStylist = $stylist;
    }

    /** the customer accepts the proposed time */
    public function acceptProposal(): void
    {
        if ($this->getStatus() !== AppointmentStatus::PROPOSED) {
            throw new \DomainException('Es liegt kein Terminvorschlag vor.', 1794300001);
        }
        $this->status = AppointmentStatus::CONFIRMED->value;
    }

    public function decline(): void
    {
        $this->assertNotClosed();
        $this->status = AppointmentStatus::DECLINED->value;
    }

    public function cancelByCustomer(): void
    {
        $this->assertNotClosed();
        $this->status = AppointmentStatus::CANCELLED->value;
    }

    public function complete(bool $showedUp): void
    {
        if ($this->getStatus() !== AppointmentStatus::CONFIRMED) {
            throw new \DomainException('Nur bestätigte Termine können abgeschlossen werden.', 1794300002);
        }
        $this->status = $showedUp ? AppointmentStatus::DONE->value : AppointmentStatus::NO_SHOW->value;
    }

    public function setInternalNote(?string $note): void
    {
        $this->internalNote = $note !== null && trim($note) !== '' ? trim($note) : null;
        $this->touch();
    }

    public function log(string $by, string $text): void
    {
        $this->history[] = ['at' => (new \DateTimeImmutable())->format(DATE_ATOM), 'by' => $by, 'text' => $text];
        $this->touch();
    }

    private function assertNotClosed(): void
    {
        if ($this->getStatus()->isClosed()) {
            throw new \DomainException(sprintf('Die Anfrage ist bereits abgeschlossen (%s).', $this->getStatus()->label()), 1794300003);
        }
    }

    private function touch(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function getCreatedAt(): \DateTimeImmutable { return $this->createdAt; }
    public function getUpdatedAt(): ?\DateTimeImmutable { return $this->updatedAt; }
    public function getSiteNodeName(): string { return $this->siteNodeName; }
    public function getName(): string { return $this->name; }
    public function getPhone(): string { return $this->phone; }
    public function getEmail(): ?string { return $this->email; }
    public function getService(): ?string { return $this->service; }
    public function getStylist(): ?string { return $this->stylist; }
    public function getPreferredDate(): ?\DateTimeImmutable { return $this->preferredDate; }
    public function getPreferredTime(): ?string { return $this->preferredTime; }
    public function getMessage(): ?string { return $this->message; }
    public function getStatus(): AppointmentStatus { return AppointmentStatus::from($this->status); }
    public function getAppointmentStart(): ?\DateTimeImmutable { return $this->appointmentStart; }
    public function getAppointmentEnd(): ?\DateTimeImmutable { return $this->appointmentStart?->modify('+' . ($this->durationMinutes ?? 60) . ' minutes'); }
    public function getDurationMinutes(): ?int { return $this->durationMinutes; }
    public function getAssignedStylist(): ?string { return $this->assignedStylist; }
    public function getToken(): string { return $this->token; }
    public function getInternalNote(): ?string { return $this->internalNote; }
    public function getSource(): string { return $this->source; }
    /** @return list<array{at: string, by: string, text: string}> */
    public function getHistory(): array { return $this->history; }
}
