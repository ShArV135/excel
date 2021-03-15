<?php

namespace AppBundle\Service\Report;

use AppBundle\Entity\Organisation;

class ReportProvideSummary implements SummaryInterface
{
    private $organisation;
    /** @var ReportSaleObject[] */
    private $reports;

    private $salary = 0;
    private $balance = 0;

    public function __construct(array $reports, Organisation $organisation = null)
    {
        $this->organisation = $organisation;
        $this->reports = $reports;
        $this->calculateSummary();
    }

    public function getReports(): array
    {
        return $this->reports;
    }

    public function getOrganisation(): ?Organisation
    {
        return $this->organisation;
    }

    public function getSalary(): float
    {
        return $this->salary;
    }

    public function getBalance(): float
    {
        return $this->balance;
    }

    private function calculateSummary(): void
    {
        foreach ($this->reports as $report) {
            $this->salary += $report->getSalary();
            $this->balance += $report->getBalance();
        }
    }
}
