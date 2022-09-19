<?php

namespace AppBundle\Repository;

use AppBundle\Entity\Timetable;
use AppBundle\Entity\TimetableRow;
use AppBundle\Service\Utils;
use Doctrine\ORM\EntityRepository;
use function Doctrine\ORM\QueryBuilder;

/**
 * TimetableRepository
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class TimetableRepository extends EntityRepository
{
    /**
     * @return Timetable|null|object
     * @throws \Doctrine\ORM\NonUniqueResultException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function getCurrent()
    {
        $qb = $this->createQueryBuilder('timetable');

        $date = new \DateTime();

        $timetable = $qb
            ->andWhere($qb->expr()->gte('timetable.created', ':from'))
            ->andWhere($qb->expr()->lte('timetable.created', ':to'))
            ->setParameters([
                'to' => clone $date->modify('last day of')->setTime(0, 0),
                'from' => clone $date->modify('first day of')->setTime(0, 0),
            ])
            ->orderBy('timetable.id', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult()
        ;

        if (!$timetable) {
            $timetable = $this->create();
        }

        return $timetable;
    }

    /**
     * @return Timetable
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function create()
    {
        $em = $this->getEntityManager();

        $timetable = new Timetable();

        /** @var Timetable $lastTimetable */
        $lastTimetable = $this->findOneBy([], ['created' => 'DESC']);
        if ($lastTimetable) {
            $created = clone $lastTimetable->getCreated();
            $created->modify('first day of')->modify('+1 month');
        } else {
            $created  = new \DateTime();
        }

        $year = $created->format('Y');

        $name = sprintf('%s %s', Utils::getMonth($created), $year);
        $timetable
            ->setName($name)
            ->setCreated($created)
            ->setUpdated($created)
        ;

        if ($lastTimetable) {
            /** @var TimetableRow $row */
            foreach ($lastTimetable->getRows() as $row) {
                $em->detach($row);

                $row
                    ->setTimetable($timetable)
                    ->setHasAct(false)
                ;
                $em->persist($row);
                $timetable->getRows()->add($row);
            }
        }

        $em->persist($timetable);
        $em->flush();

        return $timetable;
    }

    public function getRange(Timetable $from = null,Timetable $to = null): array
    {
        $qb = $this->createQueryBuilder('entity');

        if ($from) {
            $qb
                ->andWhere($qb->expr()->gte('entity.id', ':id_from'))
                ->setParameter('id_from', $from->getId())
            ;
        }

        if ($to) {
            $qb
                ->andWhere($qb->expr()->lte('entity.id', ':id_to'))
                ->setParameter('id_to', $to->getId())
            ;
        }

        return $qb->getQuery()->getResult();
    }
}
