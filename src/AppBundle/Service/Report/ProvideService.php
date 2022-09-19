<?php

namespace AppBundle\Service\Report;

use AppBundle\Entity\Contractor;
use AppBundle\Entity\Organisation;
use AppBundle\Entity\Timetable;
use AppBundle\Entity\TimetableRow;
use AppBundle\Service\Timetable\RowTimeStorage;

class ProvideService extends ReportService
{
    private $exportService;
    /** @var RowTimeStorage */
    private $timeStorage;

    /**
     * @required
     * @param ProvideExportService $exportService
     */
    public function setExportService(ProvideExportService $exportService): void
    {
        $this->exportService = $exportService;
    }

    /**
     * @required
     * @param RowTimeStorage $timeStorage
     * @return void
     */
    public function setTimeStorage(RowTimeStorage $timeStorage): void
    {
        $this->timeStorage = $timeStorage;
    }

    public function getExportService()
    {
        return $this->exportService;
    }

    protected function createSummary(array $reports, Organisation $organisation = null)
    {
        return new ReportProvideSummary($reports, $organisation);
    }

    protected function getReportsByTimetable(ReportConfig $config): array
    {
        $this->timeStorage->init($config->getTimetable());
        $reports = $this->createReports($config);

        foreach ($this->getTimetableRows($config) as $timetableRow) {
            $contractor = $timetableRow->getProvider();

            if (!$contractor) {
                continue;
            }

            if (!$this->isValidOrganisation($contractor, $config->getOrganisation())) {
                continue;
            }

            if (!isset($reports[$contractor->getId()])) {
                $reports[$contractor->getId()] = $this->createReportObject($config->getTimetable(), $contractor);
            }

            $reportObject = $reports[$contractor->getId()];
            $rowData = $this->timetableHelper->calculateRowData($timetableRow);

            $reportObject->addSalary($rowData['provider_salary']);
        }

        return $reports;
    }

    private function createReports(ReportConfig $config): array
    {
        $contractors = $this->getContractors($config);

        $reports = [];

        foreach ($contractors as $contractor) {
            $reports[$contractor->getId()] = $this->createReportObject($config->getTimetable(), $contractor);
        }

        return $reports;
    }

    private function createReportObject(Timetable $timetable, Contractor $contractor): ReportProvideObject
    {
        try {
            $balance = $this->balanceService->getBalance($contractor);
        } catch (\Exception $e) {
            $balance = 0;
        }

        return new ReportProvideObject($timetable, $contractor, $balance);
    }

    /**
     * @param ReportConfig $config
     * @return Contractor[]
     */
    private function getContractors(ReportConfig $config): array
    {
        $criteria = [
            'type' => Contractor::PROVIDER,
        ];

        if ($organisation = $config->getOrganisation()) {
            $criteria['organisation'] = $organisation;
        }

        if ($contractor = $config->getContractor()) {
            $criteria['id'] = $contractor->getId();
        }

        return $this->entityManager->getRepository('AppBundle:Contractor')->findBy($criteria, ['name' => 'ASC']);
    }

    /**
     * @param ReportConfig $config
     * @return TimetableRow[]
     */
    private function getTimetableRows(ReportConfig $config): array
    {
        $criteria = [
            'timetable' => $config->getTimetable(),
        ];

        if ($provider = $config->getContractor()) {
            $criteria['provider'] = $provider;
        }

        return $this->entityManager->getRepository(TimetableRow::class)->findBy($criteria);
    }
}
