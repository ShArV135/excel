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

        $qb = $this
            ->entityManager
            ->getRepository('AppBundle:Payment')
            ->createQueryBuilder('payment')
        ;
        $customerPayments = $qb
            ->andWhere($qb->expr()->lte('payment.date', ':date'))
            ->andWhere($qb->expr()->eq('payment.contractor', ':contractor'))
            ->setParameters([
                'date' => clone $timetable->getCreated()->modify('last day of'),
                'contractor' => $customer
            ])
            ->getQuery()
            ->getResult()
        ;
        $customerPaid = 0;
        /** @var Payment $payment */
        foreach ($customerPayments as $payment) {
            $customerPaid += $payment->getAmount();
        }

        $qb = $this
            ->entityManager
            ->getRepository('AppBundle:Payment')
            ->createQueryBuilder('payment')
        ;
        $providerPayments = $qb
            ->andWhere($qb->expr()->lte('payment.date', ':date'))
            ->andWhere($qb->expr()->eq('payment.contractor', ':contractor'))
            ->setParameters([
                'date' => clone $timetable->getCreated()->modify('last day of'),
                'contractor' => $provider
            ])
            ->getQuery()
            ->getResult()
        ;

        $providerPaid = 0;
        /** @var Payment $payment */
        foreach ($providerPayments as $payment) {
            $providerPaid += $payment->getAmount();
        }

        $timetableRowTimes = $timetableRowTimesRepository->getTimesOrCreate($timetable, $timetableRow);
        $times = $timetableRowTimes->getTimes();
        $sumTimes = $timetableRowTimesRepository->sumTimes($times);
        $customerSalary = $timetableRow->getPriceForCustomer() * $sumTimes;
        $providerSalary = $timetableRow->getPriceForProvider() * $sumTimes;
        $marginSum = $customerSalary - $providerSalary;
        $customerBalance = $customerPaid - $timetableRowTimesRepository->calculateContractorBalance($timetable, $customer);

        if ($provider) {
            $providerBalance = $providerPaid - $timetableRowTimesRepository->calculateContractorBalance($timetable, $provider);
        } else {
            $providerBalance = 0;
        }

        if ($providerSalary) {
            $marginPercent = round(100-($providerSalary/$customerSalary*100), 2);
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
            'provider_id' => $provider ? $provider->getId() : null,
        ];
    }
}
