<?php

declare(strict_types=1);

namespace FriseurFabrik\Base\Domain\Service;

use FriseurFabrik\Base\Domain\Model\AppointmentRequest;
use FriseurFabrik\Base\Domain\Repository\AppointmentRequestRepository;
use FriseurFabrik\Base\Mail\AppointmentMails;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\Persistence\PersistenceManagerInterface;

/**
 * The appointment workflow: every step changes the request, writes the history and informs the other side.
 */
#[Flow\Scope('singleton')]
class AppointmentService
{
    public const CUSTOMER = 'Kund:in';

    #[Flow\Inject]
    protected AppointmentRequestRepository $repository;

    #[Flow\Inject]
    protected PersistenceManagerInterface $persistenceManager;

    #[Flow\Inject]
    protected AppointmentMails $mails;

    /** @param array<string,mixed> $values validated values, see AppointmentInputValidator */
    public function request(string $siteNodeName, array $values, string $source = 'website', string $actor = self::CUSTOMER): AppointmentRequest
    {
        $request = new AppointmentRequest(
            siteNodeName: $siteNodeName,
            name: $values['name'],
            phone: $values['phone'],
            email: $values['email'],
            service: $values['service'],
            stylist: $values['stylist'],
            preferredDate: $values['date'],
            preferredTime: $values['time'],
            message: $values['message'],
            source: $source,
        );
        $request->log($actor, $source === 'website' ? 'Anfrage über die Website gestellt' : 'Termin im Salon eingetragen');
        $this->repository->add($request);
        $this->persistenceManager->persistAll();

        if ($source === 'website') {
            $this->logMail($request, $this->mails->sendReceived($request), 'Eingangsbestätigung an Kund:in');
            $this->logMail($request, $this->mails->sendSalonNotification($request), 'Benachrichtigung an den Salon');
        }
        $this->save($request);
        return $request;
    }

    public function confirm(AppointmentRequest $request, \DateTimeImmutable $start, int $duration, ?string $stylist, string $message, string $actor, bool $notify = true): void
    {
        $request->confirm($start, $duration, $stylist);
        $request->log($actor, 'Termin bestätigt: ' . AppointmentMails::dateTime($start) . ($stylist ? ' bei ' . $stylist : ''));
        if ($notify) {
            $this->logMail($request, $this->mails->sendConfirmed($request, $message), 'Terminbestätigung an Kund:in');
        }
        $this->save($request);
    }

    public function propose(AppointmentRequest $request, \DateTimeImmutable $start, int $duration, ?string $stylist, string $message, string $actor): void
    {
        $request->propose($start, $duration, $stylist);
        $request->log($actor, 'Neuen Termin vorgeschlagen: ' . AppointmentMails::dateTime($start));
        $this->logMail($request, $this->mails->sendProposed($request, $message), 'Terminvorschlag an Kund:in');
        $this->save($request);
    }

    public function decline(AppointmentRequest $request, string $message, string $actor): void
    {
        $request->decline();
        $request->log($actor, 'Anfrage abgesagt' . (trim($message) !== '' ? ': ' . trim($message) : ''));
        $this->logMail($request, $this->mails->sendDeclined($request, $message), 'Absage an Kund:in');
        $this->save($request);
    }

    public function contact(AppointmentRequest $request, string $subject, string $message, string $actor): ?bool
    {
        $sent = $this->mails->sendMessage($request, $subject, $message);
        $request->log($actor, 'Nachricht „' . $subject . '“: ' . trim($message));
        $this->logMail($request, $sent, 'Nachricht an Kund:in');
        $this->save($request);
        return $sent;
    }

    public function complete(AppointmentRequest $request, bool $showedUp, string $actor): void
    {
        $request->complete($showedUp);
        $request->log($actor, $showedUp ? 'Als erledigt markiert' : 'Als „nicht erschienen“ markiert');
        $this->save($request);
    }

    public function saveNote(AppointmentRequest $request, ?string $note, string $actor): void
    {
        $request->setInternalNote($note);
        $request->log($actor, 'Interne Notiz geändert');
        $this->save($request);
    }

    public function acceptProposal(AppointmentRequest $request): void
    {
        $request->acceptProposal();
        $request->log(self::CUSTOMER, 'Terminvorschlag angenommen');
        $this->logMail($request, $this->mails->sendConfirmed($request, ''), 'Terminbestätigung an Kund:in');
        $this->logMail($request, $this->mails->sendCustomerAnswer($request, true), 'Benachrichtigung an den Salon');
        $this->save($request);
    }

    public function cancelByCustomer(AppointmentRequest $request): void
    {
        $request->cancelByCustomer();
        $request->log(self::CUSTOMER, 'Termin von Kund:in abgesagt');
        $this->logMail($request, $this->mails->sendCustomerAnswer($request, false), 'Benachrichtigung an den Salon');
        $this->save($request);
    }

    public function delete(AppointmentRequest $request): void
    {
        $this->repository->remove($request);
        $this->persistenceManager->persistAll();
    }

    private function logMail(AppointmentRequest $request, ?bool $sent, string $what): void
    {
        if ($sent === null && str_contains($what, 'Salon')) {
            return; // the salon has no notification address configured
        }
        $request->log('System', match ($sent) {
            true => $what . ' per E-Mail gesendet',
            false => $what . ' konnte nicht gesendet werden (Mailserver)',
            null => $what . ': keine E-Mail-Adresse – bitte telefonisch informieren',
        });
    }

    private function save(AppointmentRequest $request): void
    {
        $this->repository->update($request);
        $this->persistenceManager->persistAll();
    }
}
