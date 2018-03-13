<?php

namespace AppBundle\Controller;

use AppBundle\Entity\Plan;
use AppBundle\Entity\Timetable;
use AppBundle\Form\PlanType;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class ContractorController
 */
class PlanController extends Controller
{
    /**
     * @Route("/plans", name="plan_list")
     * @return Response
     */
    public function listAction()
    {
        $em = $this->getDoctrine()->getManager();
        $timetables = $em->getRepository('AppBundle:Timetable')->findAll();

        $plansByTimetable = [];
        foreach ($timetables as $timetable) {
            $plans = $em->getRepository('AppBundle:Plan')->findBy(['timetable' => $timetable]);

            $plansByTimetable[] = [
                'timetable' => $timetable,
                'plans' => $plans,
            ];
        }

        return $this->render(
            '@App/plan/list.html.twig',
            [
                'plans_by_timetable' => $plansByTimetable,
            ]
        );
    }

    /**
     * @Route("/plans/create/{timetable}", name="plan_create")
     * @param Request   $request
     * @param Timetable $timetable
     * @return RedirectResponse|Response
     */
    public function createAction(Request $request, Timetable $timetable)
    {
        $em = $this->getDoctrine()->getManager();
        $plan = new Plan();
        $plan->setTimetable($timetable);

        $form = $this->createForm(
            PlanType::class,
            $plan,
            [
                'users' => $em->getRepository('AppBundle:Plan')->getAvailableManagers($timetable),
            ]
        );
        $form->handleRequest($request);

        if ($form->isValid()) {
            try {
                $em->persist($plan);
                $em->flush();

                $this->addFlash('success', 'План продаж успешно добавлен');

                return $this->redirectToRoute('plan_list');
            } catch (\Exception $e) {
                $this->addFlash('warning', 'При сохранении возникла ошибка.');
            }
        }

        return $this->render(
            '@App/plan/save.html.twig',
            [
                'page_header' => 'Новый план продаж',
                'form' => $form->createView(),
            ]
        );
    }

    /**
     * @Route("/plans/{plan}/update", name="plan_update")
     * @param Request $request
     * @param Plan    $plan
     * @return RedirectResponse|Response
     */
    public function updateAction(Request $request, Plan $plan)
    {
        $em = $this->getDoctrine()->getManager();

        $form = $this->createForm(
            PlanType::class,
            $plan,
            [
                'users' => $em->getRepository('AppBundle:Plan')->getAvailableManagers($plan->getTimetable(), $plan),
            ]
        );
        $form->handleRequest($request);

        if ($form->isValid()) {
            try {
                $em->flush();
                $this->addFlash('success', 'План продаж успешно изменен');

                return $this->redirectToRoute('plan_list');
            } catch (\Exception $e) {
                $this->addFlash('warning', 'При сохранении возникла ошибка.');
            }
        }

        return $this->render(
            '@App/plan/save.html.twig',
            [
                'page_header' => 'Редактировать план продаж',
                'form' => $form->createView(),
            ]
        );
    }

    /**
     * @Route("/plans/{plan}/delete", name="plan_delete")
     * @param Plan $plan
     * @return RedirectResponse
     */
    public function deleteAction(Plan $plan)
    {
        try {
            $em = $this->getDoctrine()->getManager();
            $em->remove($plan);
            $em->flush();
            $this->addFlash('success', 'Пользователь удален.');
        } catch (\Exception $e) {
            $this->addFlash('warning', 'При удалении возникла ошибка.');
        }

        return $this->redirectToRoute('plan_list');
    }
}
