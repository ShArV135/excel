<?php

namespace AppBundle\Controller;

use AppBundle\Entity\Plan;
use AppBundle\Entity\Timetable;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

/**
 * Class ContractorController
 */
class PlanController extends Controller
{
    /**
     * @Route("/plans", name="plan_index")
     * @return Response
     */
    public function indexAction()
    {
        $em = $this->getDoctrine()->getManager();
        $timetables = $em->getRepository('AppBundle:Timetable')->findBy([], ['id' => 'DESC']);

        return $this->render(
            '@App/plan/index.html.twig',
            [
                'timetables' => $timetables,
            ]
        );
    }

    /**
     * @Route("/plans/{timetable}", name="plan_data", options={"expose"=true})
     * @param Timetable $timetable
     * @param Request   $request
     * @return Response
     * @throws \Doctrine\ORM\NonUniqueResultException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function dataAction(Timetable $timetable, Request $request)
    {
        if (!$request->isXmlHttpRequest()) {
            throw new BadRequestHttpException();
        }

        $em = $this->getDoctrine()->getManager();

        $currentTimetable = $em->getRepository('AppBundle:Timetable')->getCurrent();

        if ($data = $request->get('plans')) {
            if (!is_array($data)) {
                throw new BadRequestHttpException();
            }
            foreach ($data as $userId => $amount) {
                $amount = (double) $amount;

                if ($amount <= 0) {
                    continue;
                }

                $plan = $em->getRepository('AppBundle:Plan')->findOneBy(['user' => $userId, 'timetable' => $currentTimetable]);
                if (!$plan) {
                    $plan = new Plan();
                    $plan->setUser($em->find('AppBundle:User', $userId));
                    $plan->setTimetable($currentTimetable);
                    $em->persist($plan);
                }

                $plan->setAmount($amount);
            }

            $em->flush();
        }

        $users = $em->getRepository('AppBundle:User')->getManagers();
        $plans = $em->getRepository('AppBundle:Plan')->findBy(['timetable' => $timetable]);
        $planByUsers = [];
        foreach ($plans as $plan) {
            $user = $plan->getUser();
            $planByUsers[$user->getId()] = $plan;
        }

        if ($currentTimetable === $timetable) {
            $view = '@App/plan/save.html.twig';
        } else {
            $view = '@App/plan/view.html.twig';
        }

        return $this->render(
            $view,
            [
                'users' => $users,
                'plans_by_user' => $planByUsers,
            ]
        );
    }

    /**
     * @Route("/plan-timetable/{timetable}", name="timetable_plan_data", options={"expose"=true})
     * @param Timetable $timetable
     * @param Request   $request
     * @return JsonResponse
     * @throws \Doctrine\ORM\NonUniqueResultException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function timetableDataAction(Timetable $timetable, Request $request)
    {
        if (!$request->isXmlHttpRequest()) {
            throw new BadRequestHttpException();
        }

        $timetableHelper = $this->get('timetable.helper');

        if ($this->isGranted('ROLE_CUSTOMER_MANAGER')) {
            $planData = $timetableHelper->planDataFormat($timetable, $this->getUser());
        } elseif ($this->isGranted('ROLE_MANAGER')) {
            $planData = $timetableHelper->planDataFormat($timetable);
        } else {
            $planData = [];
        }

        return new JsonResponse($planData);
    }
}
