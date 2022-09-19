<?php

namespace AppBundle\Service\Timetable;

use AppBundle\Entity\TimetableRow;

class MarginSumService
{
    private $salaryService;

    public function __construct(ManagerSalaryService $salaryService)
    {
        $this->salaryService = $salaryService;
    }

    public function rowMarginSum(TimetableRow $row): float
    {
        return static::getMarginSum($this->salaryService->rowCustomSalary($row), $this->salaryService->rowProviderSalary($row));
    }

    public static function getMarginSum(float $customerSalary, float $providerSalary): float
    {
        return $customerSalary - $providerSalary;
    }
}
