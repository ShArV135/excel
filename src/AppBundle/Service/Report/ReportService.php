<?php

namespace AppBundle\Service\Report;

use AppBundle\Entity\Contractor;
use AppBundle\Entity\Organisation;
use AppBundle\Entity\Timetable;
use AppBundle\Service\TimetableHelper;
use Doctrine\ORM\EntityManagerInterface;

abstract class ReportService
{
    protected $entityManager;
    protected $timetableHelper;

    public function __construct(EntityManagerInterface $entityManager, TimetableHelper $timetableHelper)
    {
        $this->entityManager = $entityManager;
        $this->timetableHelper = $timetableHelper;
    }

    abstract protected function getReportsByTimetable(ReportConfig $config): array;
    abstract protected function createSummary(array $reports, Organisation $organisation= null);
    abstract public function getExportService();

    public function getReports(ReportConfig $config): array
    {
        if ($config->getByOrganisation()) {
            return $this->getReportsByOrganisations($config);
        }

        return [$this->getReport($config)];
    }

    public function getReport(ReportConfig $config): SummaryInterface
    {
        $reports = $this->doGetReports($config);

        return $this->createSummary($reports);
    }

    public function export(array $reports): void
    {
        $this->getExportService()->export($reports);
    }

    protected function getReportsByOrganisations(ReportConfig $config): array
    {
        $resultReports = [];
        foreach ($this->getOrganisations() as $organisation) {
            $newConfig = clone $config;
            $newConfig->setOrganisation($organisation);

            $reports = $this->doGetReports($newConfig);

            $resultReports[] = $this->createSummary($reports, $organisation);
        }

        return $resultReports;
    }

    protected function doGetReports(ReportConfig $config): array
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

    protected function isValidOrganisation(Contractor $contractor = null, Organisation $organisation = null): bool
    {
        if (!$organisation) {
            return true;
        }

        return $contractor->getOrganisation() === $organisation;
    }

    private function getOrganisations(): array
    {
        return $this->entityManager->getRepository(Organisation::class)->findBy([], ['name' => 'ASC']);
    }

    private function getTimetables(ReportConfig $config): array
    {
        return $this
            ->entityManager
            ->getRepository(Timetable::class)
            ->getRange($config->getTimetableFrom(), $config->getTimetableTo())
        ;
    }
}
