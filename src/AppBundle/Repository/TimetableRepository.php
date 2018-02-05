<?php

namespace AppBundle\Repository;

use AppBundle\Entity\Timetable;
use Doctrine\ORM\EntityRepository;

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
            ->andWhere($qb->expr()->lte('timetable.created', ':to'))
            ->setParameters([
                'to' => clone $date->modify('last day of'),
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
     * @param Timetable $timetable
     * @param bool      $include
     * @return array
     */
    public function getAllPrevious(Timetable $timetable, $include = true)
    {
        $qb = $this->createQueryBuilder('timetable');

        if ($include) {
            $qb->where($qb->expr()->lte('timetable.id', ':id'));
        } else {
            $qb->where($qb->expr()->lt('timetable.id', ':id'));
        }

        $qb->setParameter('id', $timetable);

        return $qb->getQuery()->getResult();
    }

    /**
     * @return Timetable
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function create()
    {
        $em = $this->getEntityManager();

        $timetable = new Timetable();

        $months = ['Январь', 'Февраль', 'Март', 'Апрель', 'Май', 'Июнь', 'Июль', 'Август', 'Сентябрь', 'Октябрь', 'Ноябрь', 'Декабрь'];

        /** @var Timetable $lastTimetable */
        $lastTimetable = $this->findOneBy([], ['created' => 'DESC']);
        if ($lastTimetable) {
            $created = clone $lastTimetable->getCreated();
            $created->modify('+1 month');
        } else {
            $created  = new \DateTime();
        }

        $month = $months[ $created->format('m')-1 ];
        $year = $created->format('Y');

        $name = sprintf('%s %s', $month, $year);
        $timetable
            ->setName($name)
            ->setCreated($created)
        ;

        $em->persist($timetable);
        $em->flush();

        return $timetable;
    }
}
