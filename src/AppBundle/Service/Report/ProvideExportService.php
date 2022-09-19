<?php

namespace AppBundle\Service\Report;

use AppBundle\Service\Utils;

class ProvideExportService extends ExportService
{
    protected function buildHeader(): array
    {
        return ['Год', 'Месяц', 'Поставщик', 'Наработка', 'Баланс (среднее)'];
    }

    protected function buildRow(ReportObjectInterface $reportObject): ?array
    {
        if (!$reportObject instanceof ReportProvideObject) {
            return null;
        }

        return [
            $reportObject->getTimetable()->getCreated()->format('Y'),
            Utils::getMonth($reportObject->getTimetable()->getCreated()),
            $reportObject->getContractor()->getName(),
            (string) $reportObject->getSalary(),
            (string) $reportObject->getBalance()
        ];
    }
}
