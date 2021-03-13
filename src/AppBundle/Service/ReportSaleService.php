<?php

namespace AppBundle\Service;

use AppBundle\Entity\Bonus;
use AppBundle\Entity\Contractor;
use AppBundle\Entity\Organisation;
use AppBundle\Entity\Timetable;
use AppBundle\Entity\TimetableRow;
use AppBundle\Entity\User;
use AppBundle\Service\Report\ReportSaleExportService;
use AppBundle\Service\Report\ReportSaleObject;
use AppBundle\Service\Report\ReportSaleSummary;
use AppBundle\Service\Report\SaleExportConfig;
use Doctrine\ORM\EntityManagerInterface;

class ReportSaleService
{
    private $entityManager;
    private $timetableHelper;
    private $exportService;

    public function __construct(EntityManagerInterface $entityManager, TimetableHelper $timetableHelper, ReportSaleExportService $exportService)
    {
        $this->entityManager = $entityManager;
        $this->timetableHelper = $timetableHelper;
        $this->exportService = $exportService;
    }

    public function getReports(ReportSaleConfig $config): array
    {
        if ($config->getByOrganisation()) {
            return $this->getReportsByOrganisations($config);
        }

        return [$this->getReport($config)];
    }

    public function getReport(ReportSaleConfig $config): ReportSaleSummary
    {
        $reports = $this->doGetReports($config);

        return new ReportSaleSummary($reports);
    }

    public function export(array $reports, SaleExportConfig $config): void
    {
        $this->exportService->export($reports, $config);
    }

    private function getReportsByOrganisations(ReportSaleConfig $config): array
    {
        $resultReports = [];
        foreach ($this->getOrganisations() as $organisation) {
            $newConfig = clone $config;
            $newConfig->setOrganisation($organisation);

            $reports = $this->doGetReports($config);

            $resultReports[] = new ReportSaleSummary($reports, $organisation);
        }

        return $resultReports;
    }

    private function doGetReports(ReportSaleConfig $config): array
    {
        $timetables = $this->getTimetables($config);

        $resultReports = [];
        foreach ($timetables as $timetable) {
            $newConfig = clone $config;
            $newConfig->setTimetable($timetable);

            $resultReports = array_merge($resultReports, $this->getReportsByTimetable($newConfig));
        }

        return $resultReports;
    }

    private function getReportsByTimetable(ReportSaleConfig $config): array
    {
        $reports = $this->createReports($config);

        foreach ($this->getTimetableRows($config) as $timetableRow) {
            $customer = $timetableRow->getCustomer();

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
            $reportObject->incCounter();
        }

        foreach ($reports as $report) {
            $contractor = $report->getContractor();

            if ($manager = $contractor->getManager()) {
                if ($bonus = $this->getBonus($manager)) {
                    $report->calculateBonus($bonus);
                }
            }
        }

        return $reports;
    }

    /**
     * @param ReportSaleConfig $config
     * @return TimetableRow[]
     */
    private function getTimetableRows(ReportSaleConfig $config): array
    {
        $criteria = [
            'timetable' => $config->getTimetable(),
        ];

        if ($manager = $config->getManager()) {
            $criteria['manager'] = $manager;
        }

        if ($customer = $config->getCustomer()) {
            $criteria['customer'] = $customer;
        }

        return $this->entityManager->getRepository(TimetableRow::class)->findBy($criteria);
    }

    private function getOrganisations(): array
    {
        return $this->entityManager->getRepository(Organisation::class)->findBy([], ['name' => 'ASC']);
    }

    private function getBonus(User $user): ?Bonus
    {
        return $this->entityManager->getRepository(Bonus::class)->getForUser($user);
    }

    private function getTimetables(ReportSaleConfig $config): array
    {
        return $this
            ->entityManager
            ->getRepository(Timetable::class)
            ->getRange($config->getTimetableFrom(), $config->getTimetableTo())
        ;
    }

    private function createReports(ReportSaleConfig $config): array
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
     * @param ReportSaleConfig $config
     * @return Contractor[]
     */
    private function getCustomers(ReportSaleConfig $config): array
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

        if ($customer = $config->getCustomer()) {
            $criteria['id'] = $customer->getId();
        }

        return $this->entityManager->getRepository('AppBundle:Contractor')->findBy($criteria, ['name' => 'ASC']);
    }

    private function isValidOrganisation(Contractor $contractor, Organisation $organisation = null): bool
    {
        if (!$organisation) {
            return true;
        }

        return $contractor->getOrganisation() === $organisation;
    }
}
