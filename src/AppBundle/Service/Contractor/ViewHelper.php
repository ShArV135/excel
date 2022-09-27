<?php

namespace AppBundle\Service\Contractor;

use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

class ViewHelper
{
    private $authorizationChecker;

    public function __construct(AuthorizationCheckerInterface $authorizationChecker)
    {
        $this->authorizationChecker = $authorizationChecker;
    }

    public function canCreateProvider(): bool
    {
        if ($this->authorizationChecker->isGranted('ROLE_RENT_MANAGER')) {
            return true;
        }

        return !$this->authorizationChecker->isGranted('ROLE_CUSTOMER_MANAGER');
    }

    public function canCreateCustomer(): bool
    {
        return !$this->authorizationChecker->isGranted('ROLE_PROVIDER_MANAGER');
    }
}
