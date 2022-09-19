<?php

namespace AppBundle\Service\Report;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

abstract class ExportService
{
    abstract protected function buildHeader(): array;
    abstract protected function buildRow(ReportObjectInterface $reportObject): ?array;

    public function export(array $reports): void
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        $rows = [
            $this->buildHeader(),
        ];
        foreach ($reports as $report) {
            foreach ($report->getReports() as $reportObject) {
                if ($row = $this->buildRow($reportObject)) {
                    $rows[] = $row;
                }
            }
        }

        $sheet->fromArray($rows);

        header('Content-type: application/vnd.ms-excel');
        header('Content-Disposition: attachment; filename="file.xls"');
        $writer = new Xlsx($spreadsheet);
        $writer->save('php://output');
    }
}
