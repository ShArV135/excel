<?php

namespace AppBundle\Repository;

use AppBundle\Entity\Contractor;
use AppBundle\Entity\Timetable;
use AppBundle\Entity\TimetableRow;
use AppBundle\Entity\TimetableRowTimes;
use Doctrine\ORM\EntityRepository;

class TimetableRowTimesRepository extends EntityRepository
{
    public function create(TimetableRow $timetableRow): TimetableRowTimes
    {
        $em = $this->getEntityManager();

        $times = new TimetableRowTimes();
        $times->setTimetableRow($timetableRow);

        $em->persist($times);
        $em->flush($times);

        return $times;
    }

    public function findByContractorAndTimetable(Contractor $contractor, Timetable $timetable): array
    {
        $qb = $this->createQueryBuilder('trt');

        $qb
            ->join('trt.timetableRow', 'tr')
            ->andWhere($qb->expr()->eq('tr.timetable', ':timetable'))
            ->andWhere($qb->expr()->eq(sprintf('tr.%s', $contractor->getType()), ':contractor'))
            ->setParameter('timetable', $timetable)
            ->setParameter('contractor', $contractor)
        ;

        return $qb->getQuery()->getResult();
    }
}
