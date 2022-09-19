<?php

namespace AppBundle\EventListener;

use AppBundle\Event\TimeEvent;
use AppBundle\Service\Bitrix\BalanceUpdateService;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class BitrixTimeUpdateSubscriber implements EventSubscriberInterface
{
    private $balanceUpdateService;

    public function __construct(BalanceUpdateService $balanceUpdateService)
    {
        $this->balanceUpdateService = $balanceUpdateService;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            TimeEvent::UPDATE => 'onTimeUpdate',
        ];
    }

    public function onTimeUpdate(TimeEvent $event): void
    {
        $timetableRowTimes = $event->getTimetableRowTimes();
        $this->balanceUpdateService->onTimesUpdate($timetableRowTimes->getTimetableRow());
    }
}
