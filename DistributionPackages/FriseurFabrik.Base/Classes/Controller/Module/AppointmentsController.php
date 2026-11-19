<?php

declare(strict_types=1);

namespace FriseurFabrik\Base\Controller\Module;

use FriseurFabrik\Base\Components\Atom\FactRow;
use FriseurFabrik\Base\Components\Backend\Agenda;
use FriseurFabrik\Base\Components\Backend\AgendaDay;
use FriseurFabrik\Base\Components\Backend\AgendaItem;
use FriseurFabrik\Base\Components\Backend\AppointmentDetail;
use FriseurFabrik\Base\Components\Backend\AppointmentRow;
use FriseurFabrik\Base\Components\Backend\AppointmentTable;
use FriseurFabrik\Base\Components\Backend\CompleteForm;
use FriseurFabrik\Base\Components\Backend\ContactForm;
use FriseurFabrik\Base\Components\Backend\DeclineForm;
use FriseurFabrik\Base\Components\Backend\DeleteForm;
use FriseurFabrik\Base\Components\Backend\FilterForm;
use FriseurFabrik\Base\Components\Backend\HistoryEntry;
use FriseurFabrik\Base\Components\Backend\ModuleShell;
use FriseurFabrik\Base\Components\Backend\ModuleTab;
use FriseurFabrik\Base\Components\Backend\NewAppointmentForm;
use FriseurFabrik\Base\Components\Backend\NoteForm;
use FriseurFabrik\Base\Components\Backend\Option;
use FriseurFabrik\Base\Components\Backend\TimeForm;
use FriseurFabrik\Base\Domain\Model\AppointmentRequest;
use FriseurFabrik\Base\Domain\Model\AppointmentStatus;
use FriseurFabrik\Base\Domain\Repository\AppointmentRequestRepository;
use FriseurFabrik\Base\Domain\Service\AppointmentService;
use FriseurFabrik\Base\Domain\Service\SalonSites;
use FriseurFabrik\Base\Mail\AppointmentMails;
use FriseurFabrik\Base\Rendering\SalonContext;
use Neos\Error\Messages\Message;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\Security\Context as SecurityContext;
use Neos\Neos\Controller\Module\AbstractModuleController;
use Neos\Neos\Domain\Service\UserService;
use PackageFactory\ComponentEngine\ComponentInterface;
use PackageFactory\ComponentEngine\ComponentList;
use PackageFactory\ComponentEngine\StringComponent;

/**
 * Backend module "Verwaltung → Termine": salons confirm, reschedule or decline appointment requests and contact customers.
 * A user only sees the appointments of the salon websites they may edit (see Policy.Salons.yaml).
 */
class AppointmentsController extends AbstractModuleController
{
    private const DURATIONS = [15, 30, 45, 60, 75, 90, 120, 150, 180, 240];
    private const TIME_OF_DAY = ['vormittags' => '09:00', 'mittags' => '12:00', 'nachmittags' => '15:00', 'abends' => '18:00'];

    #[Flow\Inject]
    protected AppointmentRequestRepository $appointmentRequestRepository;

    #[Flow\Inject]
    protected AppointmentService $appointmentService;

    #[Flow\Inject]
    protected SalonSites $salonSites;

    #[Flow\Inject]
    protected SecurityContext $securityContext;

    #[Flow\Inject]
    protected UserService $userService;

    public function indexAction(string $view = 'open', string $site = '', string $q = ''): string
    {
        $sites = $this->salonSites->manageableSites();
        $siteFilter = $site !== '' && isset($sites[$site]) ? [$site] : array_keys($sites);
        $counts = $this->appointmentRequestRepository->countByStatus(array_keys($sites));

        $content = match ($view) {
            'calendar' => $this->agenda($siteFilter, $sites),
            'all' => $this->table($this->appointmentRequestRepository->findForSites($siteFilter, [], $q), $sites, 'Keine Anfragen gefunden.'),
            default => $this->table(
                $this->appointmentRequestRepository->findForSites($siteFilter, [AppointmentStatus::NEW, AppointmentStatus::PROPOSED], $q),
                $sites,
                $q !== '' ? 'Keine offenen Anfragen gefunden.' : 'Keine offenen Anfragen – alles erledigt!',
            ),
        };
        if ($sites === []) {
            $content = StringComponent::fromString('Ihrem Konto ist noch keine Salon-Website zugeordnet. Bitte wenden Sie sich an die Agentur.');
        }

        $tab = fn (string $key, string $label, string $count) => ModuleTab::create(
            label: $label,
            href: $this->uriBuilder->reset()->uriFor('index', array_filter(['view' => $key, 'site' => $site])),
            count: $count,
            active: $view === $key || ($key === 'open' && !in_array($view, ['calendar', 'all'], true)),
        );
        $open = $counts[AppointmentStatus::NEW->value] + $counts[AppointmentStatus::PROPOSED->value];

        return ModuleShell::create(
            tabs: ComponentList::list(
                $tab('open', 'Offene Anfragen', $open > 0 ? (string)$open : ''),
                $tab('calendar', 'Kalender', ''),
                $tab('all', 'Alle', ''),
            ),
            tools: FilterForm::create(
                action: $this->uriBuilder->reset()->uriFor('index'),
                view: $view,
                query: $q,
                sites: ComponentList::list(...array_map(static fn (string $key, string $name) => Option::create(value: $key, label: $name, selected: $key === $site), array_keys($sites), $sites)),
                showSites: count($sites) > 1,
                newHref: $this->uriBuilder->reset()->uriFor('new', array_filter(['site' => $site])),
            ),
            content: $content,
        )->render();
    }

    public function showAction(AppointmentRequest $appointment): string
    {
        $this->assertMayManage($appointment);
        $status = $appointment->getStatus();
        $csrf = $this->securityContext->getCsrfProtectionToken();
        $uri = fn (string $action) => $this->uriBuilder->reset()->uriFor($action, ['appointment' => $appointment]);
        $site = $this->salonSites->site($appointment->getSiteNodeName());
        $stylists = $site !== null ? $this->salonSites->stylists($site) : [];
        $start = $appointment->getAppointmentStart();
        $selectedStylist = $appointment->getAssignedStylist() ?? $appointment->getStylist() ?? '';
        $stylistOptions = fn () => ComponentList::list(...array_map(
            static fn (string $name) => Option::create(value: $name, label: $name, selected: $name === $selectedStylist),
            $stylists,
        ));
        $date = ($start ?? $appointment->getPreferredDate() ?? SalonContext::now()->modify('+1 day'))->format('Y-m-d');
        $time = $start?->format('H:i') ?? self::TIME_OF_DAY[$appointment->getPreferredTime() ?? ''] ?? '10:00';
        $hasEmail = $appointment->getEmail() !== null;
        $noMailHint = $hasEmail ? '' : ' Keine E-Mail-Adresse – bitte die Kundin / den Kunden anrufen.';

        $actions = [];
        if (!$status->isClosed()) {
            $actions[] = TimeForm::create(
                title: $status === AppointmentStatus::CONFIRMED ? 'Termin verschieben' : 'Termin bestätigen',
                hint: ($status === AppointmentStatus::CONFIRMED ? 'Neue Zeit direkt festlegen (z.B. nach Absprache am Telefon). ' : '') . 'Die Kundin / der Kunde erhält eine Bestätigung mit Kalendereintrag.' . $noMailHint,
                action: $uri('confirm'),
                csrf: $csrf,
                date: $date,
                time: $time,
                durations: $this->durationOptions($appointment->getDurationMinutes() ?? 60),
                stylists: $stylistOptions(),
                messagePlaceholder: 'z.B. Bitte kommen Sie mit ungewaschenen Haaren.',
                buttonLabel: $status === AppointmentStatus::CONFIRMED ? 'Neue Zeit bestätigen' : 'Termin bestätigen',
                primary: true,
            );
            $actions[] = TimeForm::create(
                title: 'Anderen Termin vorschlagen',
                hint: 'Die Kundin / der Kunde kann den Vorschlag per Link annehmen oder ablehnen.' . $noMailHint,
                action: $uri('propose'),
                csrf: $csrf,
                date: $date,
                time: $time,
                durations: $this->durationOptions($appointment->getDurationMinutes() ?? 60),
                stylists: $stylistOptions(),
                messagePlaceholder: 'z.B. Am Wunschtag sind wir leider ausgebucht.',
                buttonLabel: 'Vorschlag senden',
                primary: false,
            );
            if ($status === AppointmentStatus::CONFIRMED) {
                $actions[] = CompleteForm::create(action: $uri('complete'), csrf: $csrf);
            }
            $actions[] = DeclineForm::create(action: $uri('decline'), csrf: $csrf);
        }

        $phoneDigits = preg_replace('/[^\d+]/', '', $appointment->getPhone()) ?? '';
        $international = preg_replace('/^(\+|00)/', '', preg_replace('/^0(?!0)/', '49', $phoneDigits) ?? '') ?? '';
        $isMobile = (bool)preg_match('/^491[5-7]/', $international);

        $facts = array_values(array_filter([
            ['Salon', $this->salonSites->manageableSites()[$appointment->getSiteNodeName()] ?? $appointment->getSiteNodeName()],
            ['Telefon', $appointment->getPhone()],
            $appointment->getEmail() ? ['E-Mail', $appointment->getEmail()] : null,
            ['Leistung', $appointment->getService() ?? 'Beratung / nicht angegeben'],
            $appointment->getStylist() ? ['Wunsch-Stylist:in', $appointment->getStylist()] : null,
            ['Wunschtermin', trim(($appointment->getPreferredDate() ? AppointmentMails::date($appointment->getPreferredDate()) : 'flexibel') . ' ' . ($appointment->getPreferredTime() ?? ''))],
            $start ? [$status === AppointmentStatus::PROPOSED ? 'Vorgeschlagen' : 'Termin', AppointmentMails::dateTime($start) . ' (' . ($appointment->getDurationMinutes() ?? 60) . ' Min.)'] : null,
            $appointment->getAssignedStylist() ? ['Bei', $appointment->getAssignedStylist()] : null,
            ['Eingang', $appointment->getCreatedAt()->format('d.m.Y H:i') . ($appointment->getSource() === 'website' ? ' über die Website' : ' im Salon eingetragen')],
        ]));

        return AppointmentDetail::create(
            backHref: $this->uriBuilder->reset()->uriFor('index'),
            name: $appointment->getName(),
            statusLabel: $status->label(),
            statusClass: 'ff-badge--' . $status->value,
            headline: $this->headline($appointment),
            facts: ComponentList::list(...array_map(static fn (array $fact) => FactRow::create(label: $fact[0], value: $fact[1]), $facts)),
            message: $appointment->getMessage(),
            actions: ComponentList::list(...$actions),
            contact: ContactForm::create(
                action: $uri('contact'),
                csrf: $csrf,
                subject: 'Ihre Terminanfrage',
                hasEmail: $hasEmail,
                phoneLabel: $appointment->getPhone(),
                phoneHref: 'tel:' . $phoneDigits,
                whatsappHref: $isMobile ? 'https://wa.me/' . $international : null,
            ),
            note: NoteForm::create(action: $uri('note'), csrf: $csrf, note: $appointment->getInternalNote() ?? ''),
            history: ComponentList::list(...array_map(static fn (array $entry) => HistoryEntry::create(
                at: (new \DateTimeImmutable($entry['at']))->setTimezone(new \DateTimeZone('Europe/Berlin'))->format('d.m.Y H:i'),
                by: (string)$entry['by'],
                text: (string)$entry['text'],
            ), array_reverse($appointment->getHistory()))),
            danger: DeleteForm::create(action: $uri('delete'), csrf: $csrf),
        )->render();
    }

    public function newAction(string $site = ''): string
    {
        $sites = $this->salonSites->manageableSites();
        return NewAppointmentForm::create(
            action: $this->uriBuilder->reset()->uriFor('create'),
            backHref: $this->uriBuilder->reset()->uriFor('index'),
            csrf: $this->securityContext->getCsrfProtectionToken(),
            sites: ComponentList::list(...array_map(static fn (string $key, string $name) => Option::create(value: $key, label: $name, selected: $key === $site), array_keys($sites), $sites)),
            showSites: count($sites) > 1,
            date: SalonContext::now()->format('Y-m-d'),
            durations: $this->durationOptions(60),
        )->render();
    }

    public function createAction(string $site = '', string $name = '', string $phone = '', string $email = '', string $service = '', string $stylist = '', string $date = '', string $time = '', int $duration = 60, string $message = '', bool $notify = false): void
    {
        $sites = $this->salonSites->manageableSites();
        $site = count($sites) === 1 ? (string)array_key_first($sites) : $site;
        $start = $this->parseStart($date, $time);
        if (!isset($sites[$site]) || trim($name) === '' || trim($phone) === '' || $start === null || ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL))) {
            $this->addFlashMessage('Bitte Salon, Name, Telefon, Datum und Uhrzeit angeben (und ggf. eine gültige E-Mail-Adresse).', 'Termin nicht gespeichert', Message::SEVERITY_ERROR);
            $this->redirect('new', null, null, ['site' => $site]);
        }
        $request = $this->appointmentService->request($site, [
            'name' => trim($name), 'phone' => trim($phone), 'email' => trim($email) ?: null, 'service' => trim($service) ?: null,
            'stylist' => trim($stylist) ?: null, 'date' => $start->setTime(0, 0), 'time' => null, 'message' => null,
        ], 'salon', $this->actor());
        if (trim($message) !== '') {
            $this->appointmentService->saveNote($request, $message, $this->actor());
        }
        $this->appointmentService->confirm($request, $start, $this->duration($duration), trim($stylist) ?: null, '', $this->actor(), $notify);
        $this->addFlashMessage('Der Termin am %s wurde eingetragen.', 'Gespeichert', Message::SEVERITY_OK, [AppointmentMails::dateTime($start)]);
        $this->redirect('show', null, null, ['appointment' => $request]);
    }

    public function confirmAction(AppointmentRequest $appointment, string $date = '', string $time = '', int $duration = 60, string $stylist = '', string $message = ''): void
    {
        $this->assertMayManage($appointment);
        $start = $this->parseStart($date, $time) ?? $this->fail($appointment, 'Bitte ein gültiges Datum und eine Uhrzeit angeben.');
        $this->attempt($appointment, fn () => $this->appointmentService->confirm($appointment, $start, $this->duration($duration), trim($stylist) ?: null, $message, $this->actor()),
            'Termin bestätigt: ' . AppointmentMails::dateTime($start) . '.');
    }

    public function proposeAction(AppointmentRequest $appointment, string $date = '', string $time = '', int $duration = 60, string $stylist = '', string $message = ''): void
    {
        $this->assertMayManage($appointment);
        $start = $this->parseStart($date, $time) ?? $this->fail($appointment, 'Bitte ein gültiges Datum und eine Uhrzeit angeben.');
        $this->attempt($appointment, fn () => $this->appointmentService->propose($appointment, $start, $this->duration($duration), trim($stylist) ?: null, $message, $this->actor()),
            'Terminvorschlag für ' . AppointmentMails::dateTime($start) . ' gesendet.');
    }

    public function declineAction(AppointmentRequest $appointment, string $message = ''): void
    {
        $this->assertMayManage($appointment);
        $this->attempt($appointment, fn () => $this->appointmentService->decline($appointment, $message, $this->actor()), 'Die Anfrage wurde abgesagt.');
    }

    public function contactAction(AppointmentRequest $appointment, string $subject = '', string $message = ''): void
    {
        $this->assertMayManage($appointment);
        if (trim($subject) === '' || trim($message) === '') {
            $this->fail($appointment, 'Bitte Betreff und Nachricht eingeben.');
        }
        $sent = $this->appointmentService->contact($appointment, mb_substr(trim($subject), 0, 150), mb_substr($message, 0, 5000), $this->actor());
        if ($sent === true) {
            $this->addFlashMessage('Die E-Mail wurde gesendet.', 'Gesendet', Message::SEVERITY_OK);
        } else {
            $this->addFlashMessage('Die E-Mail konnte nicht gesendet werden.', 'Fehler', Message::SEVERITY_ERROR);
        }
        $this->redirect('show', null, null, ['appointment' => $appointment]);
    }

    public function completeAction(AppointmentRequest $appointment, string $result = 'done'): void
    {
        $this->assertMayManage($appointment);
        $this->attempt($appointment, fn () => $this->appointmentService->complete($appointment, $result !== 'noshow', $this->actor()), 'Status gespeichert.');
    }

    public function noteAction(AppointmentRequest $appointment, string $note = ''): void
    {
        $this->assertMayManage($appointment);
        $this->appointmentService->saveNote($appointment, mb_substr($note, 0, 5000), $this->actor());
        $this->addFlashMessage('Die Notiz wurde gespeichert.', 'Gespeichert', Message::SEVERITY_OK);
        $this->redirect('show', null, null, ['appointment' => $appointment]);
    }

    public function deleteAction(AppointmentRequest $appointment): void
    {
        $this->assertMayManage($appointment);
        $this->appointmentService->delete($appointment);
        $this->addFlashMessage('Die Anfrage wurde gelöscht.', 'Gelöscht', Message::SEVERITY_OK);
        $this->redirect('index');
    }

    /** @param array<string,string> $sites */
    private function table(array $requests, array $sites, string $emptyText): ComponentInterface
    {
        return AppointmentTable::create(
            rows: ComponentList::list(...array_map(fn (AppointmentRequest $request) => AppointmentRow::create(
                href: $this->uriBuilder->reset()->uriFor('show', ['appointment' => $request]),
                created: $request->getCreatedAt()->format('d.m.Y H:i'),
                site: $sites[$request->getSiteNodeName()] ?? $request->getSiteNodeName(),
                name: $request->getName(),
                phone: $request->getPhone(),
                service: $request->getService() ?? '–',
                when: $this->when($request),
                statusLabel: $request->getStatus()->label(),
                statusClass: 'ff-badge--' . $request->getStatus()->value,
                showSite: count($sites) > 1,
            ), $requests)),
            showSite: count($sites) > 1,
            emptyText: $emptyText,
        );
    }

    /**
     * @param list<string> $siteFilter
     * @param array<string,string> $sites
     */
    private function agenda(array $siteFilter, array $sites): ComponentInterface
    {
        $from = SalonContext::now()->setTime(0, 0);
        $until = $from->modify('+28 days');
        $byDay = [];
        foreach ($this->appointmentRequestRepository->findConfirmedBetween($siteFilter, $from, $until) as $request) {
            $start = $request->getAppointmentStart();
            if ($start !== null) {
                $byDay[$start->format('Y-m-d')][] = $request;
            }
        }
        $days = [];
        foreach ($byDay as $day => $requests) {
            $days[] = AgendaDay::create(
                label: AppointmentMails::date(new \DateTimeImmutable($day)),
                items: ComponentList::list(...array_map(fn (AppointmentRequest $request) => AgendaItem::create(
                    time: ($request->getAppointmentStart()?->format('H:i') ?? '') . '–' . ($request->getAppointmentEnd()?->format('H:i') ?? ''),
                    name: $request->getName(),
                    service: $request->getService() ?? '',
                    stylist: $request->getAssignedStylist() ?? '',
                    site: count($sites) > 1 ? ($sites[$request->getSiteNodeName()] ?? '') : '',
                    href: $this->uriBuilder->reset()->uriFor('show', ['appointment' => $request]),
                ), $requests)),
            );
        }
        return Agenda::create(days: ComponentList::list(...$days), rangeLabel: 'vom ' . $from->format('d.m.') . ' bis ' . $until->modify('-1 day')->format('d.m.Y'));
    }

    private function when(AppointmentRequest $request): string
    {
        $start = $request->getAppointmentStart();
        if ($start !== null) {
            return ($request->getStatus() === AppointmentStatus::PROPOSED ? 'Vorschlag: ' : '') . $start->format('d.m.Y H:i');
        }
        return trim('Wunsch: ' . ($request->getPreferredDate()?->format('d.m.Y') ?? 'flexibel') . ' ' . ($request->getPreferredTime() ?? ''));
    }

    private function headline(AppointmentRequest $request): string
    {
        return match ($request->getStatus()) {
            AppointmentStatus::NEW => 'Neue Anfrage – bitte bestätigen, einen anderen Termin vorschlagen oder absagen.',
            AppointmentStatus::PROPOSED => 'Terminvorschlag gesendet – wartet auf Antwort der Kundin / des Kunden.',
            AppointmentStatus::CONFIRMED => 'Fester Termin. Bei Bedarf verschieben oder nach dem Termin als erledigt markieren.',
            default => 'Diese Anfrage ist abgeschlossen.',
        };
    }

    private function durationOptions(int $selected): \PackageFactory\ComponentEngine\ComponentListInterface
    {
        return ComponentList::list(...array_map(
            static fn (int $minutes) => Option::create(value: (string)$minutes, label: $minutes . ' Minuten', selected: $minutes === $selected),
            self::DURATIONS,
        ));
    }

    private function duration(int $minutes): int
    {
        return max(15, min(480, $minutes));
    }

    private function parseStart(string $date, string $time): ?\DateTimeImmutable
    {
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) || !preg_match('/^\d{2}:\d{2}$/', $time)) {
            return null;
        }
        return \DateTimeImmutable::createFromFormat('!Y-m-d H:i', $date . ' ' . $time, new \DateTimeZone('Europe/Berlin')) ?: null;
    }

    /** runs a workflow step and reports domain errors (e.g. request already closed) as flash message */
    private function attempt(AppointmentRequest $appointment, \Closure $step, string $successMessage): never
    {
        try {
            $step();
            $this->addFlashMessage($successMessage, 'Gespeichert', Message::SEVERITY_OK);
        } catch (\DomainException $e) {
            $this->addFlashMessage($e->getMessage(), 'Nicht möglich', Message::SEVERITY_ERROR);
        }
        $this->redirect('show', null, null, ['appointment' => $appointment]);
    }

    private function fail(AppointmentRequest $appointment, string $message): never
    {
        $this->addFlashMessage($message, 'Fehler', Message::SEVERITY_ERROR);
        $this->redirect('show', null, null, ['appointment' => $appointment]);
    }

    private function assertMayManage(AppointmentRequest $appointment): void
    {
        if (!$this->salonSites->mayManage($appointment->getSiteNodeName())) {
            $this->throwStatus(403, 'Kein Zugriff auf die Termine dieses Salons.');
        }
    }

    private function actor(): string
    {
        return $this->userService->getCurrentUser()?->getLabel() ?? ($this->securityContext->getAccount()?->getAccountIdentifier() ?? 'Backend');
    }
}
