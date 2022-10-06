<?php

namespace AppBundle\Service\Report;

use AppBundle\Service\Model\Route;
use AppBundle\Service\UserService;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

class UserReportRouteService
{
    private $authorizationChecker;
    private $router;
    private $userService;

    public function __construct(AuthorizationCheckerInterface $authorizationChecker, RouterInterface $router, UserService $userService)
    {
        $this->authorizationChecker = $authorizationChecker;
        $this->router = $router;
        $this->userService = $userService;
    }

    public function getReports(): array
    {
        switch (true) {
            case $this->authorizationChecker->isGranted('ROLE_MANAGER'):
                return $this->managerReports();
            case $this->authorizationChecker->isGranted('ROLE_RENT_MANAGER'):
                return $this->rentManagerReports();
            case $this->authorizationChecker->isGranted('ROLE_CUSTOMER_MANAGER'):
                return $this->customerManagerReports();
            case $this->authorizationChecker->isGranted('ROLE_PROVIDER_MANAGER'):
                return $this->providerManagerReports();
            case $this->authorizationChecker->isGranted('ROLE_DISPATCHER'):
                return $this->dispatcherReports();
            default:
                return [];
        }
    }

    private function managerReports(): array
    {
        return [
            new Route($this->router->generate('report_manager'), 'Эффективность'),
            new Route($this->router->generate('report_sale'), 'По продажам'),
            new Route($this->router->generate('report_provide'), 'По снабжению'),
        ];
    }

    private function rentManagerReports(): array
    {
        return [
            new Route($this->router->generate('report_manager', ['user' => $this->userService->getUser()]), 'Эффективность'),
            new Route($this->router->generate('report_sale'), 'По продажам'),
            new Route($this->router->generate('report_provide'), 'По снабжению'),
        ];
    }

    private function customerManagerReports(): array
    {
        return [
            new Route($this->router->generate('report_manager', ['user' => $this->userService->getUser()]), 'Эффективность'),
            new Route($this->router->generate('report_sale'), 'По продажам'),
        ];
    }

    private function providerManagerReports(): array
    {
        return [
            new Route($this->router->generate('report_manager', ['user' => $this->userService->getUser()]), 'Эффективность'),
            new Route($this->router->generate('report_provide'), 'По снабжению'),
        ];
    }

    private function dispatcherReports(): array
    {
        return [
            new Route($this->router->generate('report_sale'), 'По продажам'),
            new Route($this->router->generate('report_provide'), 'По снабжению'),
        ];
    }
}
