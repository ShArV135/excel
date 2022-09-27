<?php

namespace AppBundle\Service\Timetable;

use AppBundle\Service\Model\Route;
use AppBundle\Service\UserService;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

class ShowModeRouteService
{
    private $authorizationChecker;
    private $router;

    public function __construct(AuthorizationCheckerInterface $authorizationChecker, RouterInterface $router, UserService $userService)
    {
        $this->authorizationChecker = $authorizationChecker;
        $this->router = $router;
    }

    public function getModes(string $timetableId): array
    {
        switch (true) {
            case $this->authorizationChecker->isGranted('ROLE_MANAGER'):
                return $this->manager($timetableId);
            case $this->authorizationChecker->isGranted('ROLE_RENT_MANAGER'):
                return $this->rentManager($timetableId);
            default:
                return [];
        }
    }

    private function manager(string $timetableId): array
    {
        return [
            new Route($this->router->generate('homepage'), 'Обычный'),
            new Route($this->router->generate('homepage', ['show' => 'customer_manager', 'id' => $timetableId]), 'Менеджер по продажам'),
            new Route($this->router->generate('homepage', ['show' => 'provider_manager', 'id' => $timetableId]), 'Менеджер по снабжению'),
            new Route($this->router->generate('homepage', ['show' => 'dispatcher', 'id' => $timetableId]), 'Диспетчер'),
        ];
    }

    private function rentManager(string $timetableId): array
    {
        return [
            new Route($this->router->generate('homepage'), 'Обычный'),
            new Route($this->router->generate('homepage', ['show' => 'customer_manager', 'id' => $timetableId]), 'Менеджер по продажам'),
            new Route($this->router->generate('homepage', ['show' => 'provider_manager', 'id' => $timetableId]), 'Менеджер по снабжению'),
        ];
    }
}
