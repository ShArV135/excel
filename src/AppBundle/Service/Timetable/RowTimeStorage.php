<?php

namespace AppBundle\Service\Timetable;

use AppBundle\Entity\Timetable;
use AppBundle\Entity\TimetableRow;
use AppBundle\Entity\TimetableRowTimes;
use Doctrine\ORM\EntityManagerInterface;

class RowTimeStorage
{
    private $storage;
    private $isInit = false;

    private $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->storage = new \SplObjectStorage();
        $this->entityManager = $entityManager;
    }

    public function init(Timetable $timetable): void
    {
        $rows = $timetable->getRows();
        $times = $this->entityManager->getRepository(TimetableRowTimes::class)->findBy([
            'timetableRow' => $rows->getValues(),
        ]);

        /** @var TimetableRowTimes $time */
        foreach ($times as $time) {
            $this->storage->attach($time->getTimetableRow(), $time);
        }

        foreach ($rows as $row) {
            if (!$this->storage->contains($row)) {
                $times = $this->entityManager->getRepository(TimetableRowTimes::class)->create($row);
                $this->storage->attach($row, $times);
            }
        }

        $this->isInit = true;
    }

    public function get(TimetableRow $row): ?TimetableRowTimes
    {
        if (!$this->isInit) {
            throw new \RuntimeException('Storage is not init.');
        }

        return $this->storage[$row];
    }
}
