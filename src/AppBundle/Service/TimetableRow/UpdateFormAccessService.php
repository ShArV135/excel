<?php

namespace AppBundle\Service\TimetableRow;

use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

class UpdateFormAccessService
{
    private $authorizationChecker;

    public function __construct(AuthorizationCheckerInterface $authorizationChecker)
    {
        $this->authorizationChecker = $authorizationChecker;
    }

    public function customerManager(): bool
    {
        if ($this->authorizationChecker->isGranted('ROLE_CUSTOMER_MANAGER')) {
            return false;
        }

        return true;
    }

    public function providerManager(): bool
    {
        if ($this->authorizationChecker->isGranted('ROLE_RENT_MANAGER')) {
            return true;
        }

        if ($this->authorizationChecker->isGranted('ROLE_CUSTOMER_MANAGER')) {
            return false;
        }

        return true;
    }

    public function provider(): bool
    {
        if ($this->authorizationChecker->isGranted('ROLE_RENT_MANAGER')) {
            return true;
        }

        if ($this->authorizationChecker->isGranted('ROLE_CUSTOMER_MANAGER')) {
            return false;
        }

        return true;
    }
}
