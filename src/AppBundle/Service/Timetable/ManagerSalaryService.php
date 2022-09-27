<?php

namespace AppBundle\Service\Timetable;

use AppBundle\Entity\TimetableRow;

class ManagerSalaryService
{
    private $timeStorage;

    public function __construct(RowTimeStorage $timeStorage)
    {
        $this->timeStorage = $timeStorage;
    }

    public function rowCustomSalary(TimetableRow $timetableRow): float
    {
        return $this->getSalary($timetableRow->getPriceForCustomer(), $this->getSumTimes($timetableRow));
    }

    public function rowProviderSalary(TimetableRow $timetableRow): float
    {
        if ($price = $timetableRow->getPriceForProvider()) {
            return $this->getSalary($price, $this->getSumTimes($timetableRow));
        } else {
            return 0;
        }
    }

    public function getSalary(float $price, float $times): float
    {
        return $price * $times;
    }

    private function getSumTimes(TimetableRow $timetableRow): float
    {
        $times = $this->timeStorage->get($timetableRow);

        return $times->sumTimes();
    }
}
