<?php

namespace AppBundle\Service\Bitrix;

class CRestProvider
{
    private const ENTITY_TYPE_COMPANY = 4;

    public function __construct(string $webhookUrl)
    {
        define('C_REST_WEB_HOOK_URL', $webhookUrl);
        define('C_REST_BLOCK_LOG', true);
    }

    public function getCompany($id): array
    {
        $result = CRest::call('crm.company.get', ['id' => $id]);

        $this->checkApiData($result);
        return $result['result'];
    }

    public function getCompanyRequisiteList(int $id): array
    {
        $result = CRest::call('crm.requisite.list', [
            'filter' => [
                'ENTITY_TYPE_ID' => self::ENTITY_TYPE_COMPANY,
                'ENTITY_ID' => $id,
            ],
        ]);

        $this->checkApiData($result);
        return $result['result'];
    }

    public function getCompanyAddressList(int $id): array
    {
        $result = CRest::call('crm.address.list', [
            'filter' => [
                'ENTITY_ID' => $id,
            ],
        ]);

        $this->checkApiData($result);
        return $result['result'];
    }

    public function getDeal(int $id): array
    {
        $result = CRest::call('crm.deal.get', ['id' => $id]);
        $this->checkApiData($result);
        return $result['result'];
    }

    public function getDealProducts(int $id): array
    {
        $result = CRest::call('crm.deal.productrows.get', ['id' => $id]);
        $this->checkApiData($result);
        return $result['result'];
    }

    private function checkApiData(array $data): void
    {
        if (isset($data['error'])) {
            throw new \RuntimeException(var_export($data, true));
        }

        if (!isset($data['result'])) {
            throw new \RuntimeException('Result is empty');
        }
    }
}
