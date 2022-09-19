<?php

namespace AppBundle\Service\Report;

use AppBundle\Service\Utils;

class ReportSaleExportService extends ExportService
{
    private $config;

    public function getConfig(): ?SaleExportConfig
    {
        return $this->config;
    }

    public function setConfig(SaleExportConfig $config): void
    {
        $this->config = $config;
    }

    protected function buildHeader(): array
    {
        $config = $this->getConfig();
        $header = ['Год', 'Месяц'];

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

    protected function buildRow(ReportObjectInterface $reportObject): ?array
    {
        if (!$reportObject instanceof ReportSaleObject) {
            return null;
        }

        $config = $this->getConfig();

        if ($config->isDebtCol() && $reportObject->getBalance() >= 0) {
            return null;
        }

        $row = [
            $reportObject->getTimetable()->getCreated()->format('Y'),
            Utils::getMonth($reportObject->getTimetable()->getCreated()),
        ];

        if (!$config->isManagerMode()) {
            if ($manager = $reportObject->getContractor()->getManager()) {
                $row[] = $manager->getFullName();
            } else {
                $row[] = '';
            }
        }

        $row = array_merge(
            $row,
            [
                $reportObject->getContractor()->getName(),
                (string) $reportObject->getSalary(),
                (string) $reportObject->getBalance(),
            ]
        );

        if ($config->isMarginCol()) {
            if ($config->isGeneralMode()) {
                $row[] = (string) $reportObject->getMarginSum();
            }

            if (!$config->isManagerMode()) {
                $row[] = (string) $reportObject->getMarginPercent();
            }
        }

        return $row;
    }
}
