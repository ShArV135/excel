<?php

namespace AppBundle\Service;

use AppBundle\Entity\TimetableRow;
use AppBundle\Entity\TimetableRowTimes;
use Doctrine\ORM\EntityManagerInterface;

class TimetableRowDeleteService
{
    private $entityManager;
    private $balanceService;

    public function __construct(EntityManagerInterface $entityManager, ContractorBalanceService $balanceService)
    {
        $this->entityManager = $entityManager;
        $this->balanceService = $balanceService;
    }

    public function checkDelete(TimetableRow $timetableRow): void
    {
        $this->checkTimes($timetableRow);

        $customerBalance = $this->balanceService->getBalance($timetableRow->getCustomer(), $timetableRow->getTimetable());

        if ($customerBalance >= 0) {
            return;
        }

        $this->checkSiblings($timetableRow);
    }

    private function checkTimes(TimetableRow $timetableRow): void
    {
        /** @var TimetableRowTimes $times */
        $times = $this->entityManager->getRepository(TimetableRowTimes::class)->findOneBy([
            'timetableRow' => $timetableRow,
        ]);

        if (!$times) {
            return;
        }

        if ($times->sumTimes() > 0) {
            throw new \RuntimeException('Невозможно удалить запись, если сумма часов больше нуля.');
        }
    }

    private function checkSiblings(TimetableRow $timetableRow): void
    {
        $timetableRows = $this->entityManager->getRepository(TimetableRow::class)->findBy([
            'timetable' => $timetableRow->getTimetable(),
            'customer' => $timetableRow->getCustomer(),
        ]);

        /** @var TimetableRow $row */
        foreach ($timetableRows as $row) {
            if ($row->getId() === $timetableRow->getId()) {
                continue;
            }

            return;
        }

        throw new \RuntimeException('Невозможно удалить запись с отрицательным балансом. Это последняя запись с данным заказчиком.');
    }
}
