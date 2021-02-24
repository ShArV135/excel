<?php

namespace AppBundle\Controller;

use Psr\Log\LoggerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class BitrixController extends Controller
{
    /**
     * @Route("/bitrix-api", name="bitrix_api")
     * @param LoggerInterface $logger
     * @param Request         $request
     * @return Response
     */
    public function apiAction(LoggerInterface $logger, Request $request): Response
    {
        $message = var_export($request->query->all() + $request->request->all(), true);
        $logger->info($message);

        return new Response($message);
    }
}
