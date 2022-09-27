<?php

namespace AppBundle\Service\Timetable;

use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

class ViewHelper
{
    private $authorizationChecker;
    private $showModeRouteService;

    public function __construct(AuthorizationCheckerInterface $authorizationChecker, ShowModeRouteService $showModeRouteService)
    {
        $this->authorizationChecker = $authorizationChecker;
        $this->showModeRouteService = $showModeRouteService;
    }

    public function marginButton(): bool
    {
        return $this->authorizationChecker->isGranted('ROLE_MANAGER') || $this->authorizationChecker->isGranted('ROLE_RENT_MANAGER');
    }

    public function showModes(string $timetableId): array
    {
        return $this->showModeRouteService->getModes($timetableId);
    }
}
