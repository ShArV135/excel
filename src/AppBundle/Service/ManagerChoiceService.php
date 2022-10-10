<?php

namespace AppBundle\Service;

use AppBundle\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

class ManagerChoiceService
{
    private $authorizationChecker;
    private $entityManager;
    private $tokenStorage;

    public function __construct(AuthorizationCheckerInterface $authorizationChecker, EntityManagerInterface $entityManager, TokenStorageInterface $tokenStorage)
    {
        $this->authorizationChecker = $authorizationChecker;
        $this->entityManager = $entityManager;
        $this->tokenStorage = $tokenStorage;
    }

    public function getCustomerManagerBuilder(): QueryBuilder
    {
        if (!$this->authorizationChecker->isGranted('ROLE_MANAGER')) {
            return $this->entityManager->getRepository(User::class)->getManagerQueryBuilder(['ROLE_CUSTOMER_MANAGER', 'ROLE_RENT_MANAGER']);
        }

        return $this
            ->entityManager
            ->getRepository(User::class)
            ->createQueryBuilder('u')
        ;
    }

    public function getProviderManagerBuilder(): QueryBuilder
    {
        $qb = $this
            ->entityManager
            ->getRepository(User::class)
            ->createQueryBuilder('u')
        ;

        if ($this->authorizationChecker->isGranted('ROLE_RENT_MANAGER')) {
            /** @var User $user */
            $user = $this->tokenStorage->getToken()->getUser();
            $qb
                ->andWhere($qb->expr()->eq('u.id', ':id'))
                ->setParameter('id', $user->getId())
            ;
        } else {
            return $this->entityManager->getRepository(User::class)->getManagerQueryBuilder(['PROVIDER_MANAGER', 'RENT_MANAGER']);
        }

        return $qb;
    }
}
