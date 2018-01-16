<?php

namespace AppBundle\Service;

use AppBundle\Entity\Payment;
use AppBundle\Entity\Timetable;
use AppBundle\Entity\TimetableRow;
use Doctrine\ORM\EntityManager;

class TimetableHelper
{
    private $entityManager;

    public function __construct(EntityManager $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    /**
     * @param Timetable    $timetable
     * @param TimetableRow $timetableRow
     * @return array
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function calculateRowData(Timetable $timetable, TimetableRow $timetableRow)
    {
        $timetableRowTimesRepository = $this->entityManager->getRepository('AppBundle:TimetableRowTimes');

        $customer = $timetableRow->getCustomer();
        $provider = $timetableRow->getProvider();

        $customerPaid = 0;
        /** @var Payment $payment */
        foreach ($customer->getPayments() as $payment) {
            $customerPaid += $payment->getAmount();
        }

        $providerPaid = 0;
        /** @var Payment $payment */
        foreach ($provider->getPayments() as $payment) {
            $providerPaid += $payment->getAmount();
        }

        $timetableRowTimes = $timetableRowTimesRepository->getTimesOrCreate($timetable, $timetableRow);
        $times = $timetableRowTimes->getTimes();
        $sumTimes = $timetableRowTimesRepository->sumTimes($times);
        $customerSalary = $timetableRow->getPriceForCustomer() * $sumTimes;
        $providerSalary = $timetableRow->getPriceForProvider() * $sumTimes;
        $marginSum = $providerSalary - $customerSalary;
        $customerBalance = $customerPaid - $timetableRowTimesRepository->calculateContractorBalance($timetable, $customer);
        $providerBalance = $providerPaid - $timetableRowTimesRepository->calculateContractorBalance($timetable, $provider);

        if ($providerSalary) {
            $marginPercent = round(100-($customerSalary/$providerSalary*100), 2);
        } else {
            $marginPercent = 0;
        }

        return [
            'timetable_row_times' => $timetableRowTimes,
            'sum_times' => $sumTimes,
            'customer_salary' => $customerSalary,
            'provider_salary' => $providerSalary,
            'customer_balance' => $customerBalance,
            'provider_balance' => $providerBalance,
            'margin_sum' => $marginSum,
            'margin_percent' => $marginPercent,
            'customer_paid' => $customerPaid,
            'provider_paid' => $providerPaid,
            'customer_id' => $customer->getId(),
            'provider_id' => $provider->getId(),
        ];
    }
}
