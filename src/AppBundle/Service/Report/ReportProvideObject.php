<?php

namespace AppBundle\Service\Report;

use AppBundle\Entity\Contractor;
use AppBundle\Entity\Timetable;

class ReportProvideObject implements ReportObjectInterface
{
    private $timetable;
    private $contractor;
    private $balance;
    private $salary = 0;

    public function __construct(Timetable $timetable, Contractor $contractor, float $balance)
    {
        $this->timetable = $timetable;
        $this->contractor = $contractor;
        $this->balance = $balance;
    }

    public function getTimetable(): Timetable
    {
        return $this->timetable;
    }

    public function getContractor(): Contractor
    {
        return $this->contractor;
    }

    public function getBalance(): float
    {
        return $this->balance;
    }

    public function addSalary(float $value): void
    {
        $this->salary += $value;
    }

    public function getSalary(): float
    {
        return $this->salary;
    }
}
