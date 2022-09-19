<?php

namespace AppBundle\Service;

use AppBundle\Entity\Contractor;
use AppBundle\Entity\ContractorBalance;
use AppBundle\Entity\Payment;
use AppBundle\Entity\Timetable;
use AppBundle\Entity\TimetableRowTimes;
use Doctrine\ORM\EntityManagerInterface;

class ContractorBalanceCalculateService
{
    private $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    public function update(Contractor $contractor, Timetable $timetable): void
    {
        $balanceEntity = $this->entityManager->getRepository(ContractorBalance::class)->findOneBy([
            'contractor' => $contractor,
            'timetable' => $timetable,
        ]);

        if (!$balanceEntity) {
            $balanceEntity = new ContractorBalance($contractor, $timetable, 0);
            $this->entityManager->persist($balanceEntity);
        }

        $balance = $this->calculate($contractor, $timetable);
        $balanceEntity->setBalance($balance);
    }

    public function calculate(Contractor $contractor, Timetable $timetable): float
    {
        $paid = $this->getPaid($contractor, $timetable);
        $shouldPay = $this->getShouldPay($contractor, $timetable);

        return $paid - $shouldPay;
    }

    private function getPaid(Contractor $contractor, Timetable $timetable): float
    {
        $payments = $this->entityManager->getRepository(Payment::class)->getContractorTimetablePayments($contractor, $timetable);
        return array_reduce($payments, static function (float $total, Payment $payment) {
            return $total + $payment->getAmount();
        }, 0);
    }

    private function getShouldPay(Contractor $contractor, Timetable $timetable): float
    {
        $times = $this->entityManager->getRepository(TimetableRowTimes::class)->findByContractorAndTimetable($contractor, $timetable);

        return array_reduce($times, static function (float $total, TimetableRowTimes $times) use ($contractor) {
            $sum = $times->sumTimes();
            $row = $times->getTimetableRow();

            if ($contractor->getType() === Contractor::PROVIDER) {
                $price = $row->getPriceForProvider();
            } else {
                $price = $row->getPriceForCustomer();
            }

            return $total + ($price * $sum);
        }, 0);
    }
}
