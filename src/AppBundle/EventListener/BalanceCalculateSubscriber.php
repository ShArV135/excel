<?php

namespace AppBundle\EventListener;

use AppBundle\Entity\Timetable;
use AppBundle\Event\PaymentEvent;
use AppBundle\Event\TimeEvent;
use AppBundle\Service\ContractorBalanceCalculateService;
use AppBundle\Service\ContractorBalanceService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class BalanceCalculateSubscriber implements EventSubscriberInterface
{
    private $balanceCalculateService;
    private $balanceService;
    private $entityManager;

    public function __construct(
        ContractorBalanceCalculateService $balanceCalculateService,
        ContractorBalanceService $balanceService,
        EntityManagerInterface $entityManager
    )
    {
        $this->balanceCalculateService = $balanceCalculateService;
        $this->balanceService = $balanceService;
        $this->entityManager = $entityManager;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            TimeEvent::UPDATE => ['onTimeUpdate', 9999],
            PaymentEvent::UPDATE => 'onPaymentUpdate',
        ];
    }

    public function onTimeUpdate(TimeEvent $event): void
    {
        $timetableRowTimes = $event->getTimetableRowTimes();
        $row = $timetableRowTimes->getTimetableRow();
        $timetable = $row->getTimetable();

        if ($customer = $row->getCustomer()) {
            $this->balanceCalculateService->update($customer, $timetable);
        }

        if ($provider = $row->getProvider()) {
            $this->balanceCalculateService->update($provider, $timetable);
        }

        $this->balanceService->invalidate();
    }

    public function onPaymentUpdate(PaymentEvent $event): void
    {
        $payment = $event->getPayment();
        $contractor = $payment->getContractor();
        $date = $payment->getDate();

        if ($timetable = $this->entityManager->getRepository(Timetable::class)->findOneByDate($date)) {
            $this->balanceCalculateService->update($contractor, $timetable);
        }
    }
}
