<?php

namespace AppBundle\Controller;

use AppBundle\Entity\Contractor;
use AppBundle\Service\Timetable\ViewHelper;
use AppBundle\Service\TimetableHelper;
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
     * @param TimetableHelper $timetableHelper
     * @param ViewHelper $viewHelper
     * @return Response
     */
    public function indexAction(Request $request, TimetableHelper $timetableHelper, ViewHelper $viewHelper): Response
    {
        $em = $this->getDoctrine()->getManager();
        $currentTimetable = $em->getRepository('AppBundle:Timetable')->getCurrent();

        if ($id = $request->get('id')) {
            $timetable = $em->find('AppBundle:Timetable', $id);

            if ($timetable === $currentTimetable) {
                $params = [];

                if ($show = $request->get('show')) {
                    $params['show'] = $show;
                }

                return $this->redirectToRoute('homepage', $params);
            }
        } else {
            $timetable = $currentTimetable;
        }

        $fixedColumns = [
            'manager',
            'provider_manager',
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

        if (in_array('manager', $columns)) {
            $managersByFio = $em->getRepository('AppBundle:User')->getManagersByFio($timetable);
        } else {
            $managersByFio = [];
        }

        if (in_array('provider_manager', $columns)) {
            $providerManagersByFio = $em->getRepository('AppBundle:User')->getManagersByFio($timetable, 'ROLE_PROVIDER_MANAGER');
        } else {
            $providerManagersByFio = [];
        }

        if (in_array('customer', $columns)) {
            $customers = $em->getRepository('AppBundle:Contractor')->getByTimetable($timetable);
        } else {
            $customers = [];
        }

        if (in_array('provider', $columns)) {
            $providers = $em->getRepository('AppBundle:Contractor')->getByTimetable($timetable, Contractor::PROVIDER);
        } else {
            $providers = [];
        }

        return $this->render(
            '@App/default/index.html.twig',
            [
                'timetable' => $timetable,
                'columns' => $columns,
                'num_of_fixed' => $numOfFixed,
                'fixed_columns' => $fixedColumns,
                'managers_by_fio' => $managersByFio,
                'provider_managers_by_fio' => $providerManagersByFio,
                'customers' => $customers,
                'providers' => $providers,
                'view_mode' => $show,
                'viewHelper' => $viewHelper,
            ]
        );
    }
}
