<?php

namespace AppBundle\Service\Bitrix;

use AppBundle\Entity\Contractor;
use AppBundle\Entity\Timetable;
use AppBundle\Entity\TimetableRow;
use AppBundle\Service\TimetableHelper;

class BalanceUpdateService
{
    private $provider;
    private $timetableHelper;

    public function __construct(CRestProvider $provider, TimetableHelper $timetableHelper)
    {
        $this->provider = $provider;
        $this->timetableHelper = $timetableHelper;
    }

    public function onTimesUpdate(TimetableRow $timetableRow): void
    {
        if ($customer = $timetableRow->getCustomer()) {
            $this->updateBalance($customer, $timetableRow->getTimetable());
        }

        if ($provider = $timetableRow->getProvider()) {
            $this->updateBalance($provider, $timetableRow->getTimetable());
        }
    }

    private function updateBalance(Contractor $contractor, Timetable $timetable): void
    {
        $bitrix24Id = $contractor->getBitrix24Id();

        if (empty($bitrix24Id)) {
            return;
        }

        $fields = [
            'UF_CRM_1613403884' => $this->timetableHelper->contractorBalance($contractor, $timetable),
        ];

        $params = [
            'REGISTER_SONET_EVENT' => 'Y',
        ];

        $this->provider->updateCompany($bitrix24Id, $fields, $params);
    }
}
