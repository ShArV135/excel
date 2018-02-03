<?php

namespace AppBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class DefaultController
 * @package AppBundle\Controller
 */
class DefaultController extends Controller
{
    /**
     * @Route("/", name="homepage")
     * @param Request $request
     * @return Response
     * @throws \Doctrine\ORM\NonUniqueResultException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function indexAction(Request $request)
    {
        $em = $this->getDoctrine()->getManager();
        $timetableHelper = $this->get('timetable.helper');

        if ($id = $request->get('id')) {
            $timetable = $em->find('AppBundle:Timetable', $id);
        } else {
            $timetable = $em->getRepository('AppBundle:Timetable')->getCurrent();
        }

        $fixedColumns = [
            'manager',
            'customer',
            'provider',
            'object',
            'mechanism',
            'comment',
            'price_for_customer',
            'price_for_provider',
            'sum_times',
        ];

        $show = $timetableHelper->getShowMode();
        $columns = $timetableHelper->getColumnsByShow($show);
        $numOfFixed = count(array_intersect($columns, $fixedColumns));
        if (!$this->isGranted('ROLE_CUSTOMER_MANAGER')) {
            $managersByFio = $em->getRepository('AppBundle:User')->getManagersByFio();
        } else {
            $managersByFio = [];
        }

        return $this->render(
            '@App/default/index.html.twig',
            [
                'timetable' => $timetable,
                'columns' => $columns,
                'num_of_fixed' => $numOfFixed,
                'fixed_columns' => $fixedColumns,
                'managers_by_fio' => $managersByFio,
                'view_mode' => $show,
            ]
        );
    }
}
