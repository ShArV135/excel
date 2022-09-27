<?php

namespace AppBundle\Service\Contractor;

use AppBundle\Entity\Contractor;
use AppBundle\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

class GetListService
{
    private $entityManager;
    private $authorizationChecker;
    private $tokenStorage;

    public function __construct(EntityManagerInterface $entityManager, AuthorizationCheckerInterface $authorizationChecker, TokenStorageInterface $tokenStorage)
    {
        $this->entityManager = $entityManager;
        $this->authorizationChecker = $authorizationChecker;
        $this->tokenStorage = $tokenStorage;
    }

    public function createQueryBuilder(): QueryBuilder
    {
        $qb = $this
            ->entityManager
            ->getRepository(Contractor::class)
            ->createQueryBuilder('contractor')
            ->addOrderBy('contractor.name', 'ASC')
        ;

        switch (true) {
            case $this->authorizationChecker->isGranted('ROLE_RENT_MANAGER'):
                $qb
                    ->andWhere($qb->expr()->orX(
                        $qb->expr()->eq('contractor.manager', ':contractor_manager'),
                        $qb->expr()->eq('contractor.type', ':contractor_type')
                    ))
                    ->setParameter('contractor_manager', $this->getUser())
                    ->setParameter('contractor_type', Contractor::PROVIDER)
                ;
                break;
            case $this->authorizationChecker->isGranted('ROLE_CUSTOMER_MANAGER'):
                $qb
                    ->andWhere($qb->expr()->eq('contractor.manager', ':contractor_manager'))
                    ->setParameter('contractor_manager', $this->getUser())
                ;
                break;
            case $this->authorizationChecker->isGranted('ROLE_PROVIDER_MANAGER'):
                $qb
                    ->andWhere($qb->expr()->eq('contractor.type', ':contractor_type'))
                    ->setParameter('contractor_type', Contractor::PROVIDER)
                ;
                break;
        }

        return $qb;
    }

    public function getCustomers(): array
    {
        $qb = $this->createQueryBuilder();
        $qb
            ->andWhere($qb->expr()->eq('contractor.type', ':contractor_type'))
            ->setParameter('contractor_type', Contractor::CUSTOMER)
        ;

        return $qb->getQuery()->getResult();
    }

    public function getProviders(): array
    {
        $qb = $this->createQueryBuilder();
        $qb
            ->andWhere($qb->expr()->eq('contractor.type', ':contractor_type'))
            ->setParameter('contractor_type', Contractor::PROVIDER)
        ;

        return $qb->getQuery()->getResult();
    }

    private function getUser(): User
    {
        return $this->tokenStorage->getToken()->getUser();
    }
}
