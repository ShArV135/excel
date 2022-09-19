<?php

namespace AppBundle\EventListener;

use AppBundle\Event\TimeEvent;
use AppBundle\Service\ContractorBalanceCalculateService;
use AppBundle\Service\ContractorBalanceService;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class TimeUpdateSubscriber implements EventSubscriberInterface
{
    private $balanceCalculateService;
    private $balanceService;

    public function __construct(ContractorBalanceCalculateService $balanceCalculateService, ContractorBalanceService $balanceService)
    {
        $this->balanceCalculateService = $balanceCalculateService;
        $this->balanceService = $balanceService;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            TimeEvent::UPDATE => ['onTimeUpdate', 9999],
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
}
