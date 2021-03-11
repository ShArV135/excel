<?php

namespace AppBundle\Service\Report;

use AppBundle\Entity\Bonus;
use AppBundle\Entity\Contractor;
use AppBundle\Entity\Timetable;

class ReportSaleObject
{
    private $timetable;
    private $contractor;
    private $balance;
    private $salary = 0;
    private $marginSum = 0;
    private $marginPercent = 0;
    private $bonus = 0;
    private $counter = 0;

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

    public function addMarginSum(float $value): void
    {
        $this->marginSum += $value;
    }

    public function getMarginSum(): float
    {
        return $this->marginSum;
    }

    public function addMarginPercent(float $value): void
    {
        $this->marginPercent += $value;
    }

    public function getMarginPercent(): float
    {
        return $this->marginPercent;
    }

    public function incCounter(): void
    {
        $this->counter++;
    }

    public function calculateBonus(Bonus $bonus): void
    {
        $value = $bonus->getValue();
        if ($bonus->getType() === Bonus::TYPE_FROM_SALARY) {
            $this->bonus = ($value * $this->salary) / 100;
        } elseif ($bonus->getType() === Bonus::TYPE_FROM_MARGIN) {
            $this->bonus = ($value * $this->marginSum) / 100;
        }
    }

    public function getBonus(): float
    {
        return $this->bonus;
    }
}
