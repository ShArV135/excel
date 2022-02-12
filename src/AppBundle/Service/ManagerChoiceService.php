<?php

namespace AppBundle\Service;

use AppBundle\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

class ManagerChoiceService
{
    private $authorizationChecker;
    private $entityManager;

    public function __construct(AuthorizationCheckerInterface $authorizationChecker, EntityManagerInterface $entityManager)
    {
        $this->authorizationChecker = $authorizationChecker;
        $this->entityManager = $entityManager;
    }

    public function getBuilder(): QueryBuilder
    {
        $qb = $this
            ->entityManager
            ->getRepository(User::class)
            ->createQueryBuilder('u')
        ;

        if (!$this->authorizationChecker->isGranted('ROLE_MANAGER')) {
            $qb
                ->where($qb->expr()->like('u.roles', ':roles'))
                ->setParameter('roles', '%CUSTOMER_MANAGER%')
            ;
        }

        return $qb;
    }
}
