<?php

namespace AppBundle\Service\Report;

use AppBundle\Entity\Organisation;

class ReportSaleSummary
{
    private $organisation;
    /** @var ReportSaleObject[] */
    private $reports;

    private $salary = 0;
    private $balance = 0;
    private $marginSum = 0;
    private $bonus = 0;
    private $marginPercent = 0;

    public function __construct(array $reports, Organisation $organisation = null)
    {
        $this->organisation = $organisation;
        $this->reports = $reports;
        $this->calculateSummary();
    }

    public function getOrganisation(): ?Organisation
    {
        return $this->organisation;
    }

    public function getReports(): array
    {
        return $this->reports;
    }

    public function getSalary(): float
    {
        return $this->salary;
    }

    public function getBalance(): float
    {
        return $this->balance;
    }

    public function getMarginSum(): float
    {
        return $this->marginSum;
    }

    public function getBonus(): float
    {
        return $this->bonus;
    }

    public function getMarginPercent(): float
    {
        return $this->marginPercent;
    }

    private function calculateSummary(): void
    {
        $marginPercentAmount = 0;

        foreach ($this->reports as $report) {
            $this->salary += $report->getSalary();
            $this->balance += $report->getBalance();
            $this->marginSum += $report->getMarginSum();
            $this->bonus += $report->getBonus();
            $this->marginPercent += $report->getMarginPercent();

            if ($report->getMarginPercent() > 0) {
                $marginPercentAmount++;
            }
        }

        if ($marginPercentAmount > 0) {
            $this->marginPercent /= $marginPercentAmount;
        } else {
            $this->marginPercent = 0;
        }
    }
}
