<?php

declare(strict_types=1);

namespace FriseurFabrik\Base\Controller;

use FriseurFabrik\Base\Components\Atom\FactRow;
use FriseurFabrik\Base\Components\Content\AppointmentAnswer;
use FriseurFabrik\Base\Domain\Model\AppointmentRequest;
use FriseurFabrik\Base\Domain\Model\AppointmentStatus;
use FriseurFabrik\Base\Domain\Repository\AppointmentRequestRepository;
use FriseurFabrik\Base\Domain\Service\AppointmentInputValidator;
use FriseurFabrik\Base\Domain\Service\AppointmentService;
use FriseurFabrik\Base\Domain\Service\SalonSites;
use FriseurFabrik\Base\Mail\AppointmentMails;
use FriseurFabrik\Base\Rendering\PageLayout;
use FriseurFabrik\Base\Rendering\SalonContext;
use FriseurFabrik\Base\Security\RateLimiter;
use Neos\ContentRepository\Core\DimensionSpace\DimensionSpacePoint;
use Neos\ContentRepository\Core\Projection\ContentGraph\Filter\FindReferencesFilter;
use Neos\ContentRepository\Core\SharedModel\Workspace\WorkspaceName;
use Neos\ContentRepositoryRegistry\ContentRepositoryRegistry;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\Http\ServerRequestAttributes;
use Neos\Flow\Mvc\Controller\ActionController;
use Neos\Flow\Security\Context as SecurityContext;
use Neos\Neos\Domain\Model\RenderingMode;
use Neos\Neos\Domain\Model\Site;
use Neos\Neos\Domain\NodeMapping\NodeMapperInterface;
use Neos\Neos\Domain\SubtreeTagging\NeosVisibilityConstraints;
use Neos\Neos\FrontendRouting\SiteDetection\SiteDetectionResult;
use Neos\Neos\Security\Authorization\ContentRepositoryAuthorizationService;
use PackageFactory\ComponentEngine\ComponentList;
use PackageFactory\Neos\ComponentEngine\Infrastructure\Neos\NeosContextFactory;

/**
 * Public endpoints of the appointment workflow:
 * - POST /termin-anfrage   the form of all salon sites (JSON for the form script, redirect without JS)
 * - GET  /termin/antwort   page behind the links in the customer e-mails
 * - POST /termin/antwort   accept a proposed time or cancel
 */
class AppointmentController extends ActionController
{
    /** the form script asks for JSON, everything else is HTML */
    protected $supportedMediaTypes = ['text/html', 'application/json'];

    #[Flow\Inject]
    protected AppointmentService $appointmentService;

    #[Flow\Inject]
    protected AppointmentRequestRepository $appointmentRequestRepository;

    #[Flow\Inject]
    protected SalonSites $salonSites;

    #[Flow\Inject]
    protected RateLimiter $rateLimiter;

    #[Flow\Inject]
    protected PageLayout $pageLayout;

    #[Flow\Inject]
    protected NeosContextFactory $neosContextFactory;

    #[Flow\Inject]
    protected ContentRepositoryRegistry $contentRepositoryRegistry;

    #[Flow\Inject]
    protected ContentRepositoryAuthorizationService $authorizationService;

    #[Flow\Inject]
    protected SecurityContext $securityContext;

    #[Flow\Inject]
    protected NodeMapperInterface $nodeMapper;

    /**
     * Public form endpoint, there is no session to protect -> no CSRF token
     * @Flow\SkipCsrfProtection
     */
    public function submitAction(): string
    {
        $body = $this->request->getHttpRequest()->getParsedBody();
        $body = is_array($body) ? $body : [];
        $returnUri = $this->safeReturnPath(is_string($body['returnUri'] ?? null) ? $body['returnUri'] : null);

        // bots fill the hidden honeypot field: pretend success, store nothing
        if (trim((string)($body['website'] ?? '')) !== '') {
            return $this->answer(true, $returnUri);
        }
        $site = $this->salonSites->site((string)($body['site'] ?? ''));
        if ($site === null) {
            return $this->answer(false, $returnUri, ['form' => 'Die Anfrage konnte keinem Salon zugeordnet werden.']);
        }
        $clientIp = (string)$this->request->getHttpRequest()->getAttribute(ServerRequestAttributes::CLIENT_IP);
        if (!$this->rateLimiter->attempt('appointment|' . $clientIp, 5, 600)) {
            return $this->answer(false, $returnUri, ['form' => 'Sie haben in kurzer Zeit sehr viele Anfragen gesendet. Bitte versuchen Sie es später erneut oder rufen Sie uns an.'], 429);
        }

        $result = AppointmentInputValidator::validate($body, $this->salonSites->salonData($site), SalonContext::now());
        if ($result['errors'] !== []) {
            return $this->answer(false, $returnUri, $result['errors'], 422);
        }
        $this->appointmentService->request($site->getNodeName()->value, $result['values']);
        return $this->answer(true, $returnUri);
    }

    /**
     * @Flow\SkipCsrfProtection
     */
    public function respondAction(string $token = '', string $done = ''): string
    {
        $request = $this->findByToken($token);
        if ($request === null) {
            return $this->renderUnknownLink();
        }
        $notice = match ($done) {
            'accept' => 'Vielen Dank! Ihr Termin ist jetzt verbindlich gebucht. Sie erhalten eine Bestätigung per E-Mail.',
            'cancel' => 'Ihr Termin wurde abgesagt. Schade – vielleicht klappt es ein anderes Mal!',
            default => null,
        };
        return $this->renderAnswerPage($request, $notice);
    }

    /**
     * The secret token in the form protects the action, there is no session to protect
     * @Flow\SkipCsrfProtection
     */
    public function answerAction(string $token, string $decision): void
    {
        $request = $this->findByToken($token) ?? $this->throwStatus(404, 'Diese Terminanfrage gibt es nicht (mehr).');
        try {
            if ($decision === 'accept') {
                $this->appointmentService->acceptProposal($request);
            } elseif ($decision === 'cancel') {
                $this->appointmentService->cancelByCustomer($request);
            }
        } catch (\DomainException) {
            // already answered (e.g. double click) -> just show the current state
        }
        $this->redirectToUri('/termin/antwort?' . http_build_query(['token' => $token, 'done' => $decision]), 0, 303);
    }

    /** @param array<string,string> $errors */
    private function answer(bool $ok, string $returnUri, array $errors = [], int $status = 200): string
    {
        if (str_contains($this->request->getHttpRequest()->getHeaderLine('Accept'), 'application/json')) {
            $this->response->setContentType('application/json');
            $this->response->setStatusCode($ok ? 200 : $status);
            return json_encode(['ok' => $ok, 'errors' => (object)$errors], JSON_THROW_ON_ERROR);
        }
        $this->redirectToUri($returnUri . ($ok ? '#termin-gesendet' : '#termin-fehler'), 0, 303);
    }

    private function findByToken(string $token): ?AppointmentRequest
    {
        return preg_match('/^[a-f0-9]{48}$/', $token) ? $this->appointmentRequestRepository->findOneByToken($token) : null;
    }

    /** old link of a deleted request, typo, …: a normal page of the salon (detected by host) instead of a bare error */
    private function renderUnknownLink(): string
    {
        $heading = 'Diese Terminanfrage gibt es nicht (mehr)';
        $site = $this->salonSites->site(SiteDetectionResult::fromRequest($this->request->getHttpRequest())->siteNodeName->value)
            ?? $this->throwStatus(404, $heading);
        $this->response->setStatusCode(404);
        $data = $this->salonSites->salonData($site);
        return $this->pageLayout->render($this->createNeosContext($site), AppointmentAnswer::create(
            heading: $heading,
            intro: 'Der Link ist ungültig oder die Anfrage wurde inzwischen gelöscht. Rufen Sie uns gerne an – wir helfen weiter.',
            statusLabel: null,
            facts: ComponentList::list(),
            notice: null,
            action: '/termin/antwort',
            token: '',
            canAccept: false,
            canCancel: false,
            phoneLabel: $data->phone,
            phoneHref: $data->phoneHref(),
        ), $heading . ' | ' . $data->name)->render();
    }

    private function renderAnswerPage(AppointmentRequest $request, ?string $notice): string
    {
        $site = $this->salonSites->site($request->getSiteNodeName()) ?? $this->throwStatus(404);
        $status = $request->getStatus();
        $start = $request->getAppointmentStart();
        $inFuture = $start === null || $start > new \DateTimeImmutable();
        [$heading, $intro] = match ($status) {
            AppointmentStatus::NEW => ['Ihre Anfrage ist eingegangen', 'Wir melden uns schnellstmöglich, um den Termin zu bestätigen.'],
            AppointmentStatus::PROPOSED => ['Passt Ihnen dieser Termin?', 'Wir haben Ihnen einen neuen Termin vorgeschlagen. Bitte nehmen Sie ihn an oder lehnen Sie ihn ab.'],
            AppointmentStatus::CONFIRMED => ['Ihr Termin ist bestätigt', 'Wir freuen uns auf Ihren Besuch!'],
            AppointmentStatus::DECLINED => ['Ihre Anfrage wurde abgesagt', 'Leider konnten wir Ihnen keinen Termin anbieten. Rufen Sie uns gerne an.'],
            AppointmentStatus::CANCELLED => ['Der Termin ist abgesagt', 'Sie haben diesen Termin abgesagt.'],
            AppointmentStatus::DONE, AppointmentStatus::NO_SHOW => ['Dieser Termin liegt zurück', 'Vielen Dank für Ihren Besuch.'],
        };
        $facts = array_values(array_filter([
            ['Leistung', $request->getService() ?? 'Beratung'],
            $start !== null && in_array($status, [AppointmentStatus::PROPOSED, AppointmentStatus::CONFIRMED], true) ? [$status === AppointmentStatus::PROPOSED ? 'Vorschlag' : 'Termin', AppointmentMails::dateTime($start)] : null,
            $start === null && $request->getPreferredDate() ? ['Wunschtermin', trim(AppointmentMails::date($request->getPreferredDate()) . ' ' . ($request->getPreferredTime() ?? ''))] : null,
            $request->getAssignedStylist() ? ['Bei', $request->getAssignedStylist()] : null,
        ]));

        $context = $this->createNeosContext($site);
        $data = $this->salonSites->salonData($site);
        return $this->pageLayout->render($context, AppointmentAnswer::create(
            heading: $heading,
            intro: $intro,
            statusLabel: $status->label(),
            facts: ComponentList::list(...array_map(static fn (array $fact) => FactRow::create(label: $fact[0], value: $fact[1]), $facts)),
            notice: $notice,
            action: '/termin/antwort',
            token: $request->getToken(),
            canAccept: $status === AppointmentStatus::PROPOSED,
            canCancel: !$status->isClosed() && $inFuture,
            phoneLabel: $data->phone,
            phoneHref: $data->phoneHref(),
        ), $heading . ' | ' . $data->name)->render();
    }

    /** frontend rendering context for a page that is not a node (live workspace, visitor visibility) */
    private function createNeosContext(Site $site): \PackageFactory\Neos\ComponentEngine\NeosContext
    {
        $contentRepository = $this->contentRepositoryRegistry->get($site->getConfiguration()->contentRepositoryId);
        $visibility = $this->authorizationService->getVisibilityConstraints($contentRepository->id, $this->securityContext->getRoles())
            ->merge(NeosVisibilityConstraints::excludeDisabled());
        $subgraph = $contentRepository->getContentGraph(WorkspaceName::forLive())->getSubgraph(DimensionSpacePoint::createWithoutDimensions(), $visibility);
        $siteNode = $this->salonSites->siteNode($site);
        // the answer page belongs to the booking page: its menu item is shown as active (not "Start")
        $bookingPage = $subgraph->findReferences($siteNode->aggregateId, FindReferencesFilter::create(referenceName: 'bookingPage'))->getNodes()->first();
        return $this->neosContextFactory->create(documentNode: $bookingPage ?? $siteNode, siteNode: $siteNode, subgraph: $subgraph, request: $this->request, renderingMode: RenderingMode::createFrontend());
    }

    /** only same-site relative paths, prevents open redirects */
    private function safeReturnPath(?string $uri): string
    {
        $path = $uri !== null ? (string)parse_url($uri, PHP_URL_PATH) : '';
        return str_starts_with($path, '/') && !str_starts_with($path, '//') ? $path : '/';
    }
}
