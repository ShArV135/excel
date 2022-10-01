<?php

namespace AppBundle\Repository;

use AppBundle\Entity\Bonus;
use AppBundle\Entity\User;
use AppBundle\Service\Utils;
use Doctrine\ORM\EntityRepository;

class BonusRepository extends EntityRepository
{
    public function getForUser(User $user): ?Bonus
    {
        return $this->findOneBy(['managerType' => $this->getBonusType($user)]);
    }

    public function getBonusType(User $user): string
    {
        $isCustomer = Utils::isCustomerManager($user);
        $isTop = Utils::isTopManager($user);

        if ($isCustomer) {
            $isRent = Utils::isRentManager($user);

            if ($isRent) {
                return Bonus::MANAGER_TYPE_RENT;
            }

            if ($isTop) {
                return Bonus::MANAGER_TYPE_TOP_CUSTOMER;
            }

            return Bonus::MANAGER_TYPE_CUSTOMER;
        }

        if ($isTop) {
            return Bonus::MANAGER_TYPE_TOP_PROVIDER;
        }

        return Bonus::MANAGER_TYPE_PROVIDER;
    }
}
