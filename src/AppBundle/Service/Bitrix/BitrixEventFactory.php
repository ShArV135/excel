<?php

namespace AppBundle\Service\Bitrix;

use AppBundle\Service\Bitrix\EventServiceInterface;

class BitrixEventFactory
{
    private $companyAddService;
    private $dealUpdateService;

    public function __construct(CompanyAddService $companyAddService, DealUpdateService $dealUpdateService)
    {
        $this->companyAddService = $companyAddService;
        $this->dealUpdateService = $dealUpdateService;
    }

    public function getEventService(?string $event): EventServiceInterface
    {
        switch ($event) {
            case 'ONCRMCOMPANYADD':
                return $this->companyAddService;
            case 'ONCRMDEALUPDATE':
                return $this->dealUpdateService;
            default:
                throw new \RuntimeException('Incorrect event name.');
        }
    }
}
