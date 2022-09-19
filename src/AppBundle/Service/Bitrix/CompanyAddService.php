<?php

namespace AppBundle\Service\Bitrix;

use AppBundle\Entity\Contractor;
use AppBundle\Entity\Organisation;
use AppBundle\Entity\User;
use Doctrine\ORM\EntityManagerInterface;

class CompanyAddService implements EventServiceInterface
{
    private const ADDRESS_TYPE_B = 6;
    private const ADDRESS_TYPE_P = 1;

    private $provider;
    private $entityManager;

    public function __construct(CRestProvider $provider, EntityManagerInterface $entityManager)
    {
        $this->provider = $provider;
        $this->entityManager = $entityManager;
    }

    public function execute(array $data): void
    {
        $id = $this->extractId($data);

        if (!$id) {
            throw new \RuntimeException('ID is not found');
        }

        $companyData = $this->provider->getCompany($id);

        $this->createEntity($companyData);
    }

    private function extractId(array $data): ?string
    {
        return $data['FIELDS']['ID'] ?? null;
    }

    private function createEntity(array $companyData): void
    {
        $contractor = new Contractor();
        switch ($companyData['COMPANY_TYPE']) {
            case 'SUPPLIER':
                $contractor->setType(Contractor::PROVIDER);
                break;
            case 'CUSTOMER':
                $contractor->setType(Contractor::CUSTOMER);
        }

        $contractor->setName($companyData['TITLE']);
        $contractor->setBrand($companyData['TITLE']);
        $contractor->setBitrix24Id($companyData['ID']);
        $contractor->setSite($companyData['WEB'][0]['VALUE'] ?? null);

        $this->setINN($contractor);
        $this->setAddresses($contractor, $contractor->getBitrix24Id());
        $this->setOrganization($contractor);

        if (!empty($companyData['ASSIGNED_BY_ID'])) {
            $this->setManager($contractor, $companyData['ASSIGNED_BY_ID']);
        }

        $this->entityManager->persist($contractor);
        $this->entityManager->flush();
    }

    private function setINN(Contractor $contractor): void
    {
        $requisites = $this->provider->getCompanyRequisiteList($contractor->getBitrix24Id());

        foreach ($requisites as $requisite) {
            if (!empty($requisite['RQ_INN'])) {
                $contractor->setInn($requisite['RQ_INN']);
                $this->setAddresses($contractor, $requisite['ID']);

                return;
            }
        }
    }

    private function setAddresses(Contractor $contractor, int $bitrix24Id): void
    {
        $addresses = $this->provider->getCompanyAddressList($bitrix24Id);

        foreach ($addresses as $address) {
            $fullAddress = array_filter([$address['ADDRESS_1'], $address['ADDRESS_2'], $address['CITY'], $address['COUNTRY'], $address['POSTAL_CODE']]);
            $fullAddress = implode(', ', $fullAddress);

            if ($address['TYPE_ID'] == self::ADDRESS_TYPE_B) {
                $contractor->setBusinessAddress($fullAddress);
            } elseif ($address['TYPE_ID'] == self::ADDRESS_TYPE_P) {
                $contractor->setPhysicalAddress($fullAddress);
            }
        }
    }

    private function setManager(Contractor $contractor, $bitrix24Id): void
    {
        $manager = $this->entityManager->getRepository(User::class)->findOneBy([
            'bitrix24Id' => $bitrix24Id
        ]);

        $contractor->setManager($manager);
    }

    private function setOrganization(Contractor $contractor): void
    {
        $organization = $this->entityManager->getRepository(Organisation::class)->findOneBy([]);

        $contractor->setOrganisation($organization);
    }
}
