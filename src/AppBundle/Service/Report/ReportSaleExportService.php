<?php

namespace AppBundle\Service\Report;


use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class ReportSaleExportService
{
    /**
     * @param ReportSaleSummary[] $reports
     * @param SaleExportConfig    $config
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     * @throws \PhpOffice\PhpSpreadsheet\Writer\Exception
     */
    public function export(array $reports, SaleExportConfig $config): void
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        $rows = [
            $this->buildHeader($config),
        ];
        foreach ($reports as $report) {
            foreach ($report->getReports() as $reportObject) {
                if ($row = $this->buildRow($reportObject, $config)) {
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

    private function buildHeader(SaleExportConfig $config): array
    {
        $header = ['Месяц'];

        if (!$config->isManagerMode()) {
            $header[] = 'Менеджер';
        }

        $header = array_merge($header, ['Заказчик', "Наработка", "Баланс (среднее)"]);

        if ($config->isMarginCol()) {
            if ($config->isGeneralMode()) {
                $header[] = 'Маржа';
            }

            if (!$config->isManagerMode()) {
                $header[] = 'Маржа, %';
            }
        }

        return $header;
    }

    private function buildRow(ReportSaleObject $report, SaleExportConfig $config): ?array
    {
        if ($config->isDebtCol() && $report->getBalance() >= 0) {
            return null;
        }

        $row = [
            $report->getTimetable()->getName(),
        ];

        if (!$config->isManagerMode()) {
            $row[] = $report->getContractor()->getManager()->getFullName();
        }

        $row = array_merge(
            $row,
            [
                $report->getContractor()->getName(),
                (string) $report->getSalary(),
                (string) $report->getBalance(),
            ]
        );

        if ($config->isMarginCol()) {
            if ($config->isGeneralMode()) {
                $row[] = (string) $report->getMarginSum();
            }

            if (!$config->isManagerMode()) {
                $row[] = (string) $report->getMarginPercent();
            }
        }

        return $row;
    }
}
