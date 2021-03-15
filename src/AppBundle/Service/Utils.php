<?php

namespace AppBundle\Service;

use AppBundle\Entity\User;

class Utils
{
    public static function isTopManager(User $user): bool
    {
        return  (bool) array_intersect(['ROLE_TOP_PROVIDER_MANAGER', 'ROLE_TOP_CUSTOMER_MANAGER'], $user->getRoles());
    }

    public static function isCustomerManager(User $user): bool
    {
        return  (bool) array_intersect(['ROLE_CUSTOMER_MANAGER', 'ROLE_TOP_CUSTOMER_MANAGER'], $user->getRoles());
    }

    public static function getMonth(\DateTime $dateTime): string
    {
        $months = ['Январь', 'Февраль', 'Март', 'Апрель', 'Май', 'Июнь', 'Июль', 'Август', 'Сентябрь', 'Октябрь', 'Ноябрь', 'Декабрь'];

        $index = $dateTime->format('m')-1;

        return $months[$index-1];
    }
}
