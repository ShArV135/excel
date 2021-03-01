<?php

namespace AppBundle\Controller;

use AppBundle\Service\Bitrix\BitrixEventFactory;
use Psr\Log\LoggerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class BitrixController extends Controller
{
    /**
     * @Route("/bitrix-api", name="bitrix_api")
     * @param LoggerInterface    $logger
     * @param Request            $request
     * @param BitrixEventFactory $factory
     * @return Response
     */
    public function apiAction(LoggerInterface $logger, Request $request, BitrixEventFactory $factory): Response
    {
        $message = var_export($request->query->all() + $request->request->all(), true);
        $logger->error($message);

        $eventService = $factory->getEventService($request->get('event'));
        $eventService->execute($request->get('data'));

        return new Response($message);
    }
}
