<?php

namespace AppBundle\Repository;

use AppBundle\Entity\Bonus;
use AppBundle\Entity\User;
use AppBundle\Service\Utils;
use Doctrine\ORM\EntityRepository;

class BonusRepository extends EntityRepository
{
    public function getForUser(User $user): Bonus
    {
        return $this->findOneBy(['managerType' => $this->getBonusType($user)]);
    }

    public function getBonusType(User $user): string
    {
        $isCustomer = Utils::isCustomerManager($user);
        $isTop = Utils::isTopManager($user);

        if ($isCustomer) {
            if ($isTop) {
                return Bonus::MANAGER_TYPE_TOP_CUSTOMER;
            } else {
                return Bonus::MANAGER_TYPE_CUSTOMER;
            }
        } elseif ($isTop) {
            return Bonus::MANAGER_TYPE_TOP_PROVIDER;
        } else {
            return Bonus::MANAGER_TYPE_PROVIDER;
        }
    }
}
