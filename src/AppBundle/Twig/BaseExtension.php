<?php

namespace AppBundle\Twig;

use AppBundle\Service\Report\UserReportRouteService;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class BaseExtension extends AbstractExtension
{
    private $userReportRouteService;

    public function __construct(UserReportRouteService $userReportRouteService)
    {
        $this->userReportRouteService = $userReportRouteService;
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('userReports', [$this, 'userReports']),
        ];
    }

    public function userReports(): array
    {
        return $this->userReportRouteService->getReports();
    }
}
