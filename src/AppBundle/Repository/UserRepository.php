<?php

namespace AppBundle\Repository;

use AppBundle\Entity\User;
use Doctrine\ORM\EntityRepository;

/**
 * ContractorRepository
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class UserRepository extends EntityRepository
{
    /**
     * @return array
     */
    public function getManagersByFio()
    {
        $managers = $this->getManagers();

        $managersByFio = [];
        /** @var User $manager */
        foreach ($managers as $manager) {
            $managersByFio[$manager->getAbbrFullName()] = $manager->getFullName();
        }

        return $managersByFio;
    }

    /**
     * @return array
     */
    public function getManagersById()
    {
        $managers = $this->getManagers();

        $managersById = [];
        /** @var User $manager */
        foreach ($managers as $manager) {
            $managersById[$manager->getId()] = $manager->getAbbrFullName();
        }

        return $managersById;
    }

    /**
     * @param string $role
     * @return array
     */
    public function getManagers($role = 'ROLE_CUSTOMER_MANAGER')
    {
        $qb = $this->createQueryBuilder('user');
        $qb = $qb
            ->where($qb->expr()->like('user.roles', ':roles'))
            ->setParameter('roles', '%'.$role.'%')
        ;

        return $qb
            ->addOrderBy('user.lastname', 'ASC')
            ->getQuery()
            ->getResult()
        ;
    }
}
