<?php

namespace AppBundle\Service;

use AppBundle\Entity\TimetableRowTimes;
use AppBundle\Event\TimeEvent;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class TimeUpdateService
{
    private $entityManager;
    private $dispatcher;

    public function __construct(EntityManagerInterface $entityManager, EventDispatcherInterface $dispatcher)
    {
        $this->entityManager = $entityManager;
        $this->dispatcher = $dispatcher;
    }

    public function update(TimetableRowTimes $timetableRowTimes, int $day, float $value): void
    {
        $times = $timetableRowTimes->getTimes();

        if (!isset($times[$day])) {
            throw new \LogicException('Incorrect day.');
        }

        $times[$day] = $value;
        $timetableRowTimes->setTimes($times);
        $this->entityManager->flush();

        $this->dispatcher->dispatch(TimeEvent::UPDATE, new TimeEvent($timetableRowTimes));
        $this->entityManager->flush();
    }
}
