<?php

namespace AppBundle\Controller;

use AppBundle\Entity\Contractor;
use AppBundle\Entity\Timetable;
use AppBundle\Entity\TimetableRow;
use AppBundle\Form\TimetableRowType;
use AppBundle\Security\TimetableRowVoter;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class TimetableRowController extends Controller
{
    /**
     * @Route("/timetable/{timetable}/timetable-row/create", name="timetable_row_create", requirements={"timetable"="\d+"})
     * @param Request   $request
     * @param Timetable $timetable
     * @return RedirectResponse|Response
     */
    public function createAction(Request $request, Timetable $timetable)
    {
        $timetableRow = new TimetableRow();
        $timetableRow->setTimetable($timetable);

        $form = $this->getForm($timetableRow, $request);
        $form->handleRequest($request);

        if ($form->isValid()) {
            $em = $this->getDoctrine()->getManager();

            try {
                $em->persist($timetableRow);
                $em->flush();

                $this->addFlash('success', 'Запись успешно добавлена.');

                return $this->redirectToRoute('homepage', ['id' => $timetable->getId()]);
            } catch (\Exception $e) {
                $this->addFlash('warning', 'При сохранении возникла ошибка.');
            }
        }

        return $this->render(
            '@App/timetable_row/save.html.twig',
            [
                'form' => $form->createView(),
                'page_header' => 'Создать запись',
            ]
        );
    }

    /**
     * @Route("/timetable-row/{timetableRow}/update", name="timetable_row_update")
     * @param TimetableRow $timetableRow
     * @param Request      $request
     * @return RedirectResponse|Response
     */
    public function updateAction(TimetableRow $timetableRow, Request $request)
    {
        $this->denyAccessUnlessGranted(TimetableRowVoter::EDIT, $timetableRow);

        $form = $this->getForm($timetableRow, $request);
        $form->handleRequest($request);

        if ($form->isValid()) {
            $em = $this->getDoctrine()->getManager();

            try {
                $em->flush();

                $this->addFlash('success', 'Запись успешно изменена.');
                $redirectUrl = $this->generateUrl('homepage', ['id' => $timetableRow->getTimetable()->getId()]);

                return $this->redirect($redirectUrl);
            } catch (\Exception $e) {
                $this->addFlash('warning', 'При сохранении возникла ошибка.');
            }
        }

        return $this->render(
            '@App/timetable_row/save.html.twig',
            [
                'form' => $form->createView(),
                'page_header' => 'Редактировать запись',
            ]
        );
    }

    /**
     * @Route("/timetable-row/{timetableRow}/delete", name="timetable_row_delete")
     * @param TimetableRow $timetableRow
     * @return RedirectResponse
     */
    public function deleteAction(TimetableRow $timetableRow)
    {
        $this->denyAccessUnlessGranted(TimetableRowVoter::DELETE, $timetableRow);

        try {
            $em = $this->getDoctrine()->getManager();
            $em->remove($timetableRow);
            $em->flush();
            $this->addFlash('success', 'Запись удалена.');
        } catch (\Exception $e) {
            $this->addFlash('warning', 'При удалении возникла ошибка.');
        }

        return $this->redirectToRoute('homepage', ['id' => $timetableRow->getTimetable()->getId()]);
    }

    /**
     * @Route("/timetable-row/{timetableRow}/toggle-act", name="timetable_row_toggle_act", options={"expose"=true})
     * @param TimetableRow $timetableRow
     * @return JsonResponse
     */
    public function toggleActAction(TimetableRow $timetableRow)
    {
        $this->denyAccessUnlessGranted(TimetableRowVoter::EDIT, $timetableRow);

        if ($timetableRow->isHasAct()) {
            $timetableRow->setHasAct(false);
        } else {
            $timetableRow->setHasAct(true);
        }
        $this->getDoctrine()->getManager()->flush();

        return new JsonResponse(['has_act' => $timetableRow->isHasAct()]);
    }

    private function getForm(TimetableRow $timetableRow, Request $request)
    {
        $contractorRepository = $this->getDoctrine()->getRepository(Contractor::class);
        $choiceManager = false;
        $choiceProvider = false;
        $providers = [];

        if (!$organization = $request->get('organization')) {
            if ($organization = $timetableRow->getOrganization()) {
                $organization = $organization->getId();
            } else {
                $organization = null;
            }
        }

        if ($this->isGranted('ROLE_CUSTOMER_MANAGER')) {
            $user = $this->getUser();

            $timetableRow->setManager($user);
            $customers = $contractorRepository->search([
                'type' => Contractor::CUSTOMER,
                'organisation' => $organization,
                'manager' => $user,
            ]);
        } else {
            $choiceManager = true;
            $choiceProvider = true;
            $providers = $contractorRepository->search([
                'type' => Contractor::PROVIDER,
                'organisation' => $organization,
            ]);
            $customers = $contractorRepository->search([
                'type' => Contractor::CUSTOMER,
                'organisation' => $organization,
            ]);
        }

        $form = $this->createForm(
            TimetableRowType::class,
            $timetableRow,
            [
                'choice_manager' => $choiceManager,
                'customers' => $customers,
                'providers' => $providers,
                'choice_provider' => $choiceProvider,
            ]
        );

        return $form;
    }
}
