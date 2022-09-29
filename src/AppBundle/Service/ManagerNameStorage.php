<?php

namespace AppBundle\Service;

use AppBundle\Entity\User;
use Doctrine\ORM\EntityManagerInterface;

class ManagerNameStorage
{
    private $entityManager;

    private $customerStorage;
    private $providerStorage;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    public function init(): void
    {
        $this->customerStorage = $this->entityManager->getRepository(User::class)->getManagersById();
        $this->providerStorage = $this->entityManager->getRepository(User::class)->getManagersById(['ROLE_PROVIDER_MANAGER', 'ROLE_RENT_MANAGER']);
    }

    public function getCustomer(int $id): string
    {
        if (empty($this->customerStorage[$id])) {
            $this->init();
        }

        return $this->customerStorage[$id] ?? '';
    }

    public function getProvider(int $id): ?string
    {
        if (empty($this->providerStorage[$id])) {
            $this->init();
        }

        return $this->providerStorage[$id] ?? '';
    }
}
