<?php

namespace AppBundle\Service\Report;

class ProvideExportService extends ExportService
{
    protected function buildHeader(): array
    {
        return ['Месяц', 'Поставщик', 'Наработка', 'Баланс (среднее)'];
    }

    protected function buildRow(ReportObjectInterface $reportObject): ?array
    {
        if (!$reportObject instanceof ReportProvideObject) {
            return null;
        }

        return [
            $reportObject->getTimetable()->getName(),
            $reportObject->getContractor()->getName(),
            (string) $reportObject->getSalary(),
            (string) $reportObject->getBalance()
        ];
    }
}
