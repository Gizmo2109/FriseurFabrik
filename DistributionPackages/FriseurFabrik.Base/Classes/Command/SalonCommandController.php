<?php

declare(strict_types=1);

namespace FriseurFabrik\Base\Command;

use FriseurFabrik\Base\Domain\Repository\AppointmentRequestRepository;
use FriseurFabrik\Base\Media\SalonMedia;
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
    protected SalonMedia $salonMedia;

    #[Flow\Inject]
    protected SalonAccess $salonAccess;

    #[Flow\Inject]
    protected \Doctrine\ORM\EntityManagerInterface $entityManager;

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
     * Write Configuration/Policy.Salons.yaml: one role per salon that may edit only that salon's website and only
     * sees that salon's media collection (website visitors see all media)
     *
     * Run ./flow flow:cache:flush afterwards (seed and media do this file update automatically).
     */
    public function policyCommand(): void
    {
        $grant = static fn (string $target): array => ['privilegeTarget' => $target, 'permission' => 'GRANT'];
        $targets = [];
        $assetTargets = [];
        $collectionTargets = [];
        $roles = [];
        // string keys: Flow merges Policy.yaml and this split file per list index, numeric keys would overwrite the static admin grants
        $adminPrivileges = [];
        $visitorPrivileges = [];
        // not findOneByNodeName(): Neos memoizes it per process, after salon:seed --force it still returns the pruned site
        $collections = [];
        foreach ($this->siteRepository->findAll() as $site) {
            $collections[$site->getNodeName()->value] = $site->getAssetCollection();
        }
        foreach ($this->salonFiles('all') as $file) {
            $data = Yaml::parseFile($file);
            $nodeName = (string)$data['nodeName'];
            $salonName = (string)$data['site']['salonName'];
            $target = self::salonRole($nodeName);
            $targets[$target] = ['label' => 'Website ' . $salonName . ' bearbeiten', 'matcher' => SalonAccess::siteTag($nodeName)->value];
            $privileges = [$grant($target)];
            $adminPrivileges['salon_' . $nodeName] = $grant($target);

            // the collection id (not its title) is matched, so renaming the collection in the media module is harmless
            $collection = $collections[$nodeName] ?? null;
            if ($collection !== null) {
                $collectionId = (string)$this->persistenceManager->getIdentifierByObject($collection);
                $assetsTarget = str_replace('Salon_', 'SalonMedia_', $target);
                $collectionTarget = str_replace('Salon_', 'SalonMediaCollection_', $target);
                $assetTargets[$assetsTarget] = ['label' => 'Medien von ' . $salonName . ' sehen', 'matcher' => sprintf('isInCollection("%s")', $collectionId)];
                $collectionTargets[$collectionTarget] = ['label' => 'Medien-Sammlung von ' . $salonName . ' sehen', 'matcher' => sprintf('hasId("%s")', $collectionId)];
                array_push($privileges, $grant($assetsTarget), $grant($collectionTarget));
                $adminPrivileges['media_' . $nodeName] = $grant($assetsTarget);
                $adminPrivileges['mediacollection_' . $nodeName] = $grant($collectionTarget);
                // website visitors are not logged in; logged in salon accounts only see their own images
                $visitorPrivileges['media_' . $nodeName] = $grant($assetsTarget);
            } else {
                $this->outputLine('<comment>%s: no media collection yet, run ./flow salon:media</comment>', [$nodeName]);
            }

            $roles[$target] = [
                'label' => 'Salon: ' . $salonName,
                'description' => 'Darf die Website von ' . $salonName . ' bearbeiten, deren Medien sehen und deren Termine verwalten.',
                'parentRoles' => ['FriseurFabrik.Base:SalonEditor'],
                'privileges' => $privileges,
            ];
        }
        $roles['Neos.Neos:Administrator'] = ['privileges' => $adminPrivileges];
        if ($visitorPrivileges !== []) {
            $roles['Neos.Flow:Anonymous'] = ['privileges' => $visitorPrivileges];
        }
        $policy = ['privilegeTargets' => array_filter([
            'Neos\\Neos\\Security\\Authorization\\Privilege\\EditNodePrivilege' => $targets,
            'Neos\\Media\\Security\\Authorization\\Privilege\\ReadAssetPrivilege' => $assetTargets,
            'Neos\\Media\\Security\\Authorization\\Privilege\\ReadAssetCollectionPrivilege' => $collectionTargets,
        ]), 'roles' => $roles];
        $file = $this->packageManager->getPackage('FriseurFabrik.Base')->getConfigurationPath() . 'Policy.Salons.yaml';
        file_put_contents($file, "# GENERATED by ./flow salon:policy from Resources/Private/Salons/*.yaml – do not edit by hand\n" . str_replace('\\\\', '\\', Yaml::dump($policy, 6, 2)));
        $this->outputLine('Wrote %s (%d salon roles, %d media collections)', [$file, count($targets), count($assetTargets)]);
    }

    /**
     * Give every salon website its own media collection
     *
     * Creates the collection of each salon site, puts the images it uses into it (images shared by several
     * salons are copied, the pages then use the copy) and regenerates Policy.Salons.yaml. Safe to run again.
     */
    public function mediaCommand(): void
    {
        foreach ($this->salonFiles('all') as $file) {
            $nodeName = (string)Yaml::parseFile($file)['nodeName'];
            $site = $this->siteRepository->findOneByNodeName($nodeName);
            if ($site === null) {
                continue;
            }
            $stats = $this->salonMedia->separate($site);
            $this->persistenceManager->persistAll();
            $this->outputLine('%-14s übernommen: %d, kopiert: %d, übersprungen: %d', [$nodeName, $stats['adopted'], $stats['copied'], $stats['skipped']]);
        }
        // an asset in two salon collections matches both privileges and is hidden from both salons
        $shared = (int)$this->entityManager->getConnection()->fetchOne('SELECT COUNT(*) FROM (SELECT media_asset FROM neos_media_domain_model_assetcollection_assets_join GROUP BY media_asset HAVING COUNT(*) > 1) shared');
        if ($shared > 0) {
            $this->outputLine('<error>%d Medien liegen in mehreren Sammlungen und sind deshalb für die betroffenen Salons unsichtbar – in der Medienverwaltung nur einer Sammlung zuordnen.</error>', [$shared]);
        }
        $this->policyCommand();
        // the image properties changed in live
        $this->salonAccess->rebaseEditorWorkspaces();
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
