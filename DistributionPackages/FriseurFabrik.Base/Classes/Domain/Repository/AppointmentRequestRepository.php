<?php

declare(strict_types=1);

namespace FriseurFabrik\Base\Domain\Repository;

use FriseurFabrik\Base\Domain\Model\AppointmentRequest;
use FriseurFabrik\Base\Domain\Model\AppointmentStatus;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\Persistence\QueryInterface;
use Neos\Flow\Persistence\Repository;

/**
 * @Flow\Scope("singleton")
 */
class AppointmentRequestRepository extends Repository
{
    protected $defaultOrderings = ['createdAt' => QueryInterface::ORDER_DESCENDING];

    public function findOneByToken(string $token): ?AppointmentRequest
    {
        $query = $this->createQuery();
        $result = $query->matching($query->equals('token', $token))->execute()->getFirst();
        return $result instanceof AppointmentRequest ? $result : null;
    }

    /**
     * @param list<string> $siteNodeNames sites the current user may manage
     * @param list<AppointmentStatus> $statuses empty = all
     * @return list<AppointmentRequest>
     */
    public function findForSites(array $siteNodeNames, array $statuses = [], ?string $searchTerm = null, bool $orderByAppointment = false, int $limit = 200): array
    {
        if ($siteNodeNames === []) {
            return [];
        }
        $query = $this->createQuery();
        $constraints = [$query->in('siteNodeName', $siteNodeNames)];
        if ($statuses !== []) {
            $constraints[] = $query->in('status', array_map(static fn (AppointmentStatus $status) => $status->value, $statuses));
        }
        if ($searchTerm !== null && trim($searchTerm) !== '') {
            $like = '%' . trim($searchTerm) . '%';
            $constraints[] = $query->logicalOr([$query->like('name', $like, false), $query->like('phone', $like), $query->like('email', $like, false)]);
        }
        $query->matching($query->logicalAnd($constraints))->setLimit($limit);
        if ($orderByAppointment) {
            $query->setOrderings(['appointmentStart' => QueryInterface::ORDER_ASCENDING]);
        }
        return array_values($query->execute()->toArray());
    }

    /**
     * Confirmed appointments within a time range, ordered by time
     * @param list<string> $siteNodeNames
     * @return list<AppointmentRequest>
     */
    public function findConfirmedBetween(array $siteNodeNames, \DateTimeImmutable $from, \DateTimeImmutable $until): array
    {
        if ($siteNodeNames === []) {
            return [];
        }
        $query = $this->createQuery();
        $query->matching($query->logicalAnd([
            $query->in('siteNodeName', $siteNodeNames),
            $query->equals('status', AppointmentStatus::CONFIRMED->value),
            $query->greaterThanOrEqual('appointmentStart', $from),
            $query->lessThan('appointmentStart', $until),
        ]))->setOrderings(['appointmentStart' => QueryInterface::ORDER_ASCENDING]);
        return array_values($query->execute()->toArray());
    }

    /**
     * @param list<string> $siteNodeNames
     * @return array<string,int> status value => count
     */
    public function countByStatus(array $siteNodeNames): array
    {
        $counts = [];
        foreach (AppointmentStatus::cases() as $status) {
            $query = $this->createQuery();
            $counts[$status->value] = $siteNodeNames === [] ? 0 : $query->matching($query->logicalAnd([
                $query->in('siteNodeName', $siteNodeNames),
                $query->equals('status', $status->value),
            ]))->count();
        }
        return $counts;
    }
}
