<?php

namespace AppBundle\Service\Contractor;

use AppBundle\Entity\Contractor;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

class CreateAccessService
{
    private $authorizationChecker;

    public function __construct(AuthorizationCheckerInterface $authorizationChecker)
    {
        $this->authorizationChecker = $authorizationChecker;
    }

    public function canCreate(string $type): bool
    {
        if ($this->authorizationChecker->isGranted('ROLE_RENT_MANAGER')) {
            return true;
        }

        if ($this->authorizationChecker->isGranted('ROLE_CUSTOMER_MANAGER')) {
            return $type === Contractor::CUSTOMER;
        }

        return true;
    }
}
