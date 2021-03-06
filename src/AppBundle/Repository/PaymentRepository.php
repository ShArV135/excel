<?php

namespace AppBundle\Repository;

use AppBundle\Entity\Contractor;
use AppBundle\Entity\Timetable;
use Doctrine\ORM\EntityRepository;

/**
 * PaymentRepository
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class PaymentRepository extends EntityRepository
{
    public function getByContractorAndDate(Contractor $contractor, \DateTime $dateTime)
    {
        $qb = $this->createQueryBuilder('payment');

        return $qb
            ->andWhere($qb->expr()->lte('payment.date', ':date'))
            ->andWhere($qb->expr()->eq('payment.contractor', ':contractor'))
            ->setParameters([
                'date' => $dateTime,
                'contractor' => $contractor
            ])
            ->getQuery()
            ->getResult()
        ;
    }
}
