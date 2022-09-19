<?php

namespace AppBundle\Service\Report;

use AppBundle\Entity\Bonus;
use AppBundle\Entity\Contractor;
use AppBundle\Entity\Organisation;
use AppBundle\Entity\Timetable;
use AppBundle\Entity\TimetableRow;
use AppBundle\Entity\User;

class ReportSaleService extends ReportService
{
    private $exportService;

    /**
     * @required
     * @param ReportSaleExportService $exportService
     */
    public function setExportService(ReportSaleExportService $exportService): void
    {
        $this->exportService = $exportService;
    }

    public function getExportService(): ReportSaleExportService
    {
        return $this->exportService;
    }

    protected function createSummary(array $reports, Organisation $organisation = null): SummaryInterface
    {
        return new ReportSaleSummary($reports, $organisation);
    }

    protected function getReportsByTimetable(ReportConfig $config): array
    {
        if (!$config instanceof SaleConfig) {
            throw new \LogicException('Incorrect config');
        }

        $reports = $this->createReports($config);

        foreach ($this->getTimetableRows($config) as $timetableRow) {
            $customer = $timetableRow->getCustomer();

            if (!$customer) {
                continue;
            }

            if (!$this->isValidOrganisation($customer, $config->getOrganisation())) {
                continue;
            }

            if (!isset($reports[$customer->getId()])) {
                $reports[$customer->getId()] = $this->createReportObject($config->getTimetable(), $customer);
            }

            $reportObject = $reports[$customer->getId()];
            $rowData = $this->timetableHelper->calculateRowData($timetableRow);

            $reportObject->addSalary($rowData['customer_salary']);
            $reportObject->addMarginSum($rowData['margin_sum']);
            $reportObject->addMarginPercent($rowData['margin_percent']);
        }

        foreach ($reports as $report) {
            $contractor = $report->getContractor();

            if ($manager = $contractor->getManager()) {
                if ($bonus = $this->getBonus($manager)) {
                    $report->calculateBonus($bonus);
                }
            }
        }

        $reports = $this->filterZeroSalary($reports);

        return $reports;
    }

    /**
     * @param SaleConfig $config
     * @return TimetableRow[]
     */
    private function getTimetableRows(SaleConfig $config): array
    {
        $criteria = [
            'timetable' => $config->getTimetable(),
        ];

        if ($manager = $config->getManager()) {
            $criteria['manager'] = $manager;
        }

        if ($customer = $config->getContractor()) {
            $criteria['customer'] = $customer;
        }

        return $this->entityManager->getRepository(TimetableRow::class)->findBy($criteria);
    }

    private function getBonus(User $user): ?Bonus
    {
        return $this->entityManager->getRepository(Bonus::class)->getForUser($user);
    }

    private function createReports(SaleConfig $config): array
    {
        $customers = $this->getCustomers($config);

        $reports = [];

        foreach ($customers as $customer) {
            $reports[$customer->getId()] = $this->createReportObject($config->getTimetable(), $customer);
        }

        return $reports;
    }

    private function createReportObject(Timetable $timetable, Contractor $customer): ReportSaleObject
    {
        try {
            $balance = $this->timetableHelper->contractorBalance($customer);
        } catch (\Exception $e) {
            $balance = 0;
        }

        return new ReportSaleObject($timetable, $customer, $balance);
    }

    /**
     * @param SaleConfig $config
     * @return Contractor[]
     */
    private function getCustomers(SaleConfig $config): array
    {
        $criteria = [
            'type' => Contractor::CUSTOMER,
        ];

        if ($manager = $config->getManager()) {
            $criteria['manager'] = $manager;
        }

        if ($organisation = $config->getOrganisation()) {
            $criteria['organisation'] = $organisation;
        }

        if ($customer = $config->getContractor()) {
            $criteria['id'] = $customer->getId();
        }

        return $this->entityManager->getRepository('AppBundle:Contractor')->findBy($criteria, ['name' => 'ASC']);
    }

    private function filterZeroSalary(array $reports): array
    {
        $filtered = [];
        /** @var ReportSaleObject $report */
        foreach ($reports as $id => $report) {
            if ($report->getSalary() > 0.0 || $report->getBalance() !== 0.0) {
                $filtered[$id] = $report;
            }
        }

        return $filtered;
    }
}
