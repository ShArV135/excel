<?php

namespace AppBundle\Service\Report;

use AppBundle\Service\UserService;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

class SaleViewHelper
{
    private $authorizationChecker;

    public function __construct(AuthorizationCheckerInterface $authorizationChecker, RouterInterface $router, UserService $userService)
    {
        $this->authorizationChecker = $authorizationChecker;
    }

    public function marginColumn(): bool
    {
        if ($this->authorizationChecker->isGranted('ROLE_RENT_MANAGER')) {
            return true;
        }

        return !$this->authorizationChecker->isGranted('ROLE_CUSTOMER_MANAGER');
    }

    public function marginSumColumn(): bool
    {
        return $this->authorizationChecker->isGranted('ROLE_RENT_MANAGER') || $this->authorizationChecker->isGranted('ROLE_GENERAL_MANAGER');
    }

    public function marginButton(): bool
    {
        return $this->authorizationChecker->isGranted('ROLE_MANAGER') || $this->authorizationChecker->isGranted('ROLE_RENT_MANAGER');
    }
}
