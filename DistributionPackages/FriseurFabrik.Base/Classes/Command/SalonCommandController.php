<?php

declare(strict_types=1);

namespace FriseurFabrik\Base\Command;

use FriseurFabrik\Base\Domain\Repository\AppointmentRequestRepository;
use FriseurFabrik\Base\Security\SalonAccess;
use Neos\Neos\Domain\Service\UserService;
use FriseurFabrik\Base\Seed\SalonSeeder;
use Neos\ContentRepository\Core\Feature\WorkspaceRebase\Dto\RebaseErrorHandlingStrategy;
use Neos\ContentRepository\Core\Service\WorkspaceMaintenanceServiceFactory;
use Neos\ContentRepository\Core\DimensionSpace\DimensionSpacePoint;
use Neos\ContentRepository\Core\Projection\ContentGraph\Filter\FindDescendantNodesFilter;
use Neos\ContentRepository\Core\SharedModel\Workspace\WorkspaceName;
use Neos\ContentRepository\Core\SharedModel\ContentRepository\ContentRepositoryId;
use Neos\ContentRepositoryRegistry\ContentRepositoryRegistry;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\Cli\CommandController;
use Neos\Flow\Package\PackageManager;
use Neos\Flow\Persistence\PersistenceManagerInterface;
use Symfony\Component\Yaml\Yaml;

/**
 * Salon websites: create sites from the salon data files and list appointment requests
 */
class SalonCommandController extends CommandController
{
    #[Flow\Inject]
    protected SalonSeeder $seeder;

    #[Flow\Inject]
    protected PackageManager $packageManager;

    #[Flow\Inject]
    protected AppointmentRequestRepository $appointmentRequestRepository;

    #[Flow\Inject]
    protected PersistenceManagerInterface $persistenceManager;

    #[Flow\Inject]
    protected ContentRepositoryRegistry $contentRepositoryRegistry;

    #[Flow\Inject]
    protected UserService $userService;

    #[Flow\Inject]
    protected \Neos\Flow\Security\Policy\PolicyService $policyService;

    #[Flow\Inject]
    protected \Neos\Neos\Domain\Repository\SiteRepository $siteRepository;

    #[Flow\Inject]
    protected \Neos\Neos\Domain\Service\SiteNodeUtility $siteNodeUtility;

    #[Flow\Inject]
    protected \Neos\Neos\Security\Authorization\ContentRepositoryAuthorizationService $authorizationService;

    /**
     * Create salon websites from FriseurFabrik.Base/Resources/Private/Salons/*.yaml
     *
     * @param string $salon file name without .yaml, or "all"
     * @param bool $force remove and recreate existing sites (content changes made in Neos are lost!)
     * @param int $port port used for the local *.localhost domains
     */
    public function seedCommand(string $salon = 'all', bool $force = false, int $port = 8081): void
    {
        foreach ($this->salonFiles($salon) as $file) {
            $data = Yaml::parseFile($file);
            $this->outputLine('<b>%s</b> (%s)', [$data['site']['salonName'], basename($file)]);
            try {
                $this->seeder->seed($data, $force, $port, fn (string $line) => $this->outputLine($line));
                $this->outputLine('  <success>→ http://%s:%d/</success>', [$data['domains'][0] ?? 'localhost', $port]);
            } catch (\RuntimeException $e) {
                $this->outputLine('  <error>%s</error>', [$e->getMessage()]);
            }
        }
        $this->policyCommand();
        // personal workspaces of editors were forked before the new sites existed -> rebase them, otherwise the
        // backend does not find the new site nodes. pruneSite() also records the removal of a recreated site in every
        // workspace, which can no longer apply -> force drops such conflicting changes, all other changes are kept.
        try {
            $rebased = $this->contentRepositoryRegistry
                ->buildService(ContentRepositoryId::fromString('default'), new WorkspaceMaintenanceServiceFactory())
                ->rebaseOutdatedWorkspaces(RebaseErrorHandlingStrategy::STRATEGY_FORCE);
            foreach ($rebased as $workspace) {
                $this->outputLine('Rebased workspace %s', [$workspace->workspaceName->value]);
            }
        } catch (\Exception $e) {
            $this->outputLine('<error>Could not rebase editor workspaces: %s</error>', [$e->getMessage()]);
            $this->outputLine('Discard the affected changes (./flow workspace:discard <name>) and run ./flow workspace:rebaseoutdated');
        }
    }

    /**
     * Write Configuration/Policy.Salons.yaml: one role per salon that may edit only that salon's website
     *
     * Run ./flow flow:cache:flush afterwards (seed does this file update automatically).
     */
    public function policyCommand(): void
    {
        $targets = [];
        $roles = [];
        $adminPrivileges = [];
        foreach ($this->salonFiles('all') as $file) {
            $data = Yaml::parseFile($file);
            $nodeName = (string)$data['nodeName'];
            $target = self::salonRole($nodeName);
            $targets[$target] = ['label' => 'Website ' . $data['site']['salonName'] . ' bearbeiten', 'matcher' => SalonAccess::siteTag($nodeName)->value];
            $roles[$target] = [
                'label' => 'Salon: ' . $data['site']['salonName'],
                'description' => 'Darf die Website von ' . $data['site']['salonName'] . ' bearbeiten und deren Termine verwalten.',
                'parentRoles' => ['FriseurFabrik.Base:SalonEditor'],
                'privileges' => [['privilegeTarget' => $target, 'permission' => 'GRANT']],
            ];
            // string keys: Flow merges Policy.yaml and this split file per list index, numeric keys would overwrite the static admin grants
            $adminPrivileges['salon_' . $nodeName] = ['privilegeTarget' => $target, 'permission' => 'GRANT'];
        }
        $roles['Neos.Neos:Administrator'] = ['privileges' => $adminPrivileges];
        $policy = ['privilegeTargets' => ['Neos\\Neos\\Security\\Authorization\\Privilege\\EditNodePrivilege' => $targets], 'roles' => $roles];
        $file = $this->packageManager->getPackage('FriseurFabrik.Base')->getConfigurationPath() . 'Policy.Salons.yaml';
        file_put_contents($file, "# GENERATED by ./flow salon:policy from Resources/Private/Salons/*.yaml – do not edit by hand\n" . str_replace('\\\\', '\\', Yaml::dump($policy, 6, 2)));
        $this->outputLine('Wrote %s (%d salon roles)', [$file, count($targets)]);
    }

    /**
     * Create the missing backend accounts of the salons (username = site node name, random password)
     *
     * Existing accounts are only given their salon role again. Run after salon:policy + cache flush.
     */
    public function accountsCommand(string $salon = 'all'): void
    {
        $rows = [];
        foreach ($this->salonFiles($salon) as $file) {
            $data = Yaml::parseFile($file);
            $username = (string)$data['nodeName'];
            $role = self::salonRole($username);
            $user = $this->userService->getUser($username);
            if ($user === null) {
                $password = rtrim(strtr(base64_encode(random_bytes(9)), '+/', 'Xy'), '=');
                $this->userService->createUser($username, $password, (string)$data['site']['salonName'], 'Team', [$role]);
                $rows[] = [$data['site']['salonName'], $username, $password, 'neu angelegt'];
            } else {
                $this->userService->addRoleToUser($user, $role);
                $rows[] = [$data['site']['salonName'], $username, '(unverändert)', 'Rolle geprüft'];
            }
        }
        $this->persistenceManager->persistAll();
        $this->output->outputTable($rows, ['Salon', 'Benutzername', 'Passwort', 'Status']);
        $this->outputLine('Passwörter bitte notieren – sie werden nur jetzt angezeigt. Ändern mit ./flow user:setpassword <name> <passwort>');
    }

    /**
     * Show which salon websites (and locked pages) an account may edit
     */
    public function accessCommand(string $username): void
    {
        $user = $this->userService->getUser($username) ?? $this->quitWithError('Unknown user ' . $username);
        $roles = [];
        foreach ($user->getAccounts() as $account) {
            foreach ($account->getRoles() as $role) {
                $roles[$role->getIdentifier()] = $role;
                foreach ($this->policyService->getRole($role->getIdentifier())->getAllParentRoles() as $parent) {
                    $roles[$parent->getIdentifier()] = $parent;
                }
            }
        }
        $roles['Neos.Flow:Everybody'] = $this->policyService->getRole('Neos.Flow:Everybody');
        $roles['Neos.Flow:AuthenticatedUser'] = $this->policyService->getRole('Neos.Flow:AuthenticatedUser');
        $rows = [];
        foreach ($this->siteRepository->findAll() as $site) {
            $siteNode = $this->siteNodeUtility->findSiteNodeBySite($site, WorkspaceName::forLive(), DimensionSpacePoint::createWithoutDimensions());
            $subgraph = $this->contentRepositoryRegistry->subgraphForNode($siteNode);
            $locked = [];
            foreach ($subgraph->findDescendantNodes($siteNode->aggregateId, FindDescendantNodesFilter::create(nodeTypes: 'FriseurFabrik.Base:Document')) as $page) {
                if ($page->tags->withoutInherited()->contain(SalonAccess::lockTag())) {
                    $locked[] = $page->getProperty('title') . ($this->authorizationService->getNodePermissions($page, $roles)->edit ? ' (bearbeitbar)' : ' (gesperrt)');
                }
            }
            $rows[] = [
                $site->getNodeName()->value,
                $this->authorizationService->getNodePermissions($siteNode, $roles)->edit ? 'ja' : 'nein',
                implode(', ', $locked),
            ];
        }
        $this->outputLine('Rollen: %s', [implode(', ', array_keys($roles))]);
        $this->output->outputTable($rows, ['Website', 'Darf bearbeiten', 'Gesperrte Seiten']);
    }

    private function quitWithError(string $message): never
    {
        $this->outputLine('<error>%s</error>', [$message]);
        $this->quit(1);
    }

    /**
     * List the salon data files with their local domain
     */
    public function listCommand(int $port = 8081): void
    {
        $rows = [];
        foreach ($this->salonFiles('all') as $file) {
            $data = Yaml::parseFile($file);
            $rows[] = [basename($file, '.yaml'), $data['site']['salonName'], 'http://' . ($data['domains'][0] ?? '') . ':' . $port . '/', $data['rating']['score'] ?? '', $data['rating']['previousWebsite'] ?? ''];
        }
        $this->output->outputTable($rows, ['Datei', 'Salon', 'Lokale Adresse', 'Bewertung alt', 'Alte Website']);
    }

    /**
     * Show the latest appointment requests sent via the website forms
     *
     * @param string|null $site site node name to filter by
     */
    public function appointmentsCommand(?string $site = null, int $limit = 20): void
    {
        $query = $this->appointmentRequestRepository->createQuery();
        if ($site !== null) {
            $query->matching($query->equals('siteNodeName', $site));
        }
        $rows = [];
        foreach ($query->setLimit($limit)->execute() as $request) {
            $rows[] = [
                $request->getCreatedAt()->format('d.m.Y H:i'),
                $request->getSiteNodeName(),
                $request->getName(),
                $request->getPhone(),
                $request->getService() ?? '',
                $request->getStylist() ?? '',
                trim(($request->getPreferredDate()?->format('d.m.Y') ?? '') . ' ' . ($request->getPreferredTime() ?? '')),
                $request->getStatus(),
            ];
        }
        $this->output->outputTable($rows, ['Eingang', 'Site', 'Name', 'Telefon', 'Leistung', 'Stylist', 'Wunschtermin', 'Status']);
    }

    /** role (and privilege target) of a salon; Flow role names only allow word characters after the colon */
    public static function salonRole(string $siteNodeName): string
    {
        return 'FriseurFabrik.Base:Salon_' . str_replace('-', '_', $siteNodeName);
    }

    /** @return list<string> */
    private function salonFiles(string $salon): array
    {
        $directory = $this->packageManager->getPackage('FriseurFabrik.Base')->getResourcesPath() . 'Private/Salons/';
        $files = $salon === 'all' ? glob($directory . '*.yaml') : [$directory . $salon . '.yaml'];
        $files = array_values(array_filter($files ?: [], 'is_file'));
        if ($files === []) {
            $this->outputLine('<error>No salon data found for "%s" in %s</error>', [$salon, $directory]);
            $this->quit(1);
        }
        sort($files);
        return $files;
    }
}
