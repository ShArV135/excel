<?php

namespace AppBundle\Repository;

use AppBundle\Entity\Timetable;
use Doctrine\ORM\EntityRepository;

class ContractorBalanceRepository extends EntityRepository
{
    public function balancePerContractor(Timetable $timetable): array
    {
        $qb = $this
            ->createQueryBuilder('cb')
            ->join('cb.timetable', 't')
            ->select('SUM(cb.balance) as mysum', 'IDENTITY(cb.contractor) as id')
            ->groupBy('cb.contractor')
        ;

        $qb
            ->andWhere($qb->expr()->lte('t.id', ':id'))
            ->setParameter('id', $timetable->getId())
        ;

        $result = $qb->getQuery()->getArrayResult();

        return array_combine(
            array_column($result, 'id'),
            array_column($result, 'mysum')
        );
    }
}
