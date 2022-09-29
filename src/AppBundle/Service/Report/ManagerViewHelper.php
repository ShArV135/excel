<?php

namespace AppBundle\Service\Report;

use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

class ManagerViewHelper
{
    private $authorizationChecker;

    public function __construct(AuthorizationCheckerInterface $authorizationChecker)
    {
        $this->authorizationChecker = $authorizationChecker;
    }

    public function salaryColumn(): bool
    {
        return !$this->authorizationChecker->isGranted('ROLE_RENT_MANAGER');
    }

    public function marginSumColumn(): bool
    {
        return $this->authorizationChecker->isGranted('ROLE_GENERAL_MANAGER') || $this->authorizationChecker->isGranted('ROLE_RENT_MANAGER');
    }

    public function marginPercentColumn(): bool
    {
        return $this->authorizationChecker->isGranted('ROLE_MANAGER') || $this->authorizationChecker->isGranted('ROLE_RENT_MANAGER');
    }
}
