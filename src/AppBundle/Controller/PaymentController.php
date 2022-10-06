<?php

namespace AppBundle\Controller;

use AppBundle\Entity\Contractor;
use AppBundle\Entity\Payment;
use AppBundle\Entity\Timetable;
use AppBundle\Form\PaymentType;
use AppBundle\Form\TimetablePaymentType;
use AppBundle\Security\ContractorVoter;
use AppBundle\Service\Contractor\GetListService;
use AppBundle\Service\Payment\SaveService;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class ContractorController
 */
class PaymentController extends Controller
{
    /**
     * @Route("/contractors/{contractor}/payments/create", name="payment_create", requirements={"contractor"="\d+"})
     * @param Request    $request
     * @param Contractor $contractor
     * @return RedirectResponse|Response
     */
    public function createContractorAction(Request $request, Contractor $contractor, SaveService $saveService)
    {
        $this->denyAccessUnlessGranted(ContractorVoter::VIEW, $contractor);

        $payment = new Payment();
        $payment->setContractor($contractor);

        $form = $this->createForm(
            PaymentType::class,
            $payment
        );
        $form->handleRequest($request);

        if ($form->isValid()) {
            try {
                $saveService->save($payment);

                $this->addFlash('success', 'Оплата успешно добавлена');
                return $this->redirectToRoute('contractor_view', ['contractor' => $contractor->getId()]);
            } catch (\Exception $e) {
                $this->addFlash('warning', 'При сохранении возникла ошибка.');
            }
        }

        return $this->render(
            '@App/payment/save.html.twig',
            [
                'page_header' => 'Создать оплату',
                'form' => $form->createView(),
            ]
        );
    }

    /**
     * @Route("/timetable/{timetable}/payments/create", name="timetable_payment_create", requirements={"timetable"="\d+"})
     * @param Request $request
     * @param Timetable $timetable
     * @param GetListService $getListService
     * @return RedirectResponse|Response
     */
    public function createTimetableAction(Request $request, Timetable $timetable, GetListService $getListService, SaveService $saveService)
    {
        $payment = new Payment();

        $form = $this->createForm(
            TimetablePaymentType::class,
            $payment,
            [
                'contractors_qb' => $getListService->createQueryBuilder()
            ]
        );
        $form->handleRequest($request);

        if ($form->isValid()) {
            try {
                $saveService->save($payment);

                $this->addFlash('success', 'Оплата успешно добавлена');
                return $this->redirectToRoute('homepage', ['id' => $timetable->getId()]);
            } catch (\Exception $e) {
                $this->addFlash('warning', 'При сохранении возникла ошибка.');
            }
        }

        return $this->render(
            '@App/payment/save.html.twig',
            [
                'page_header' => 'Создать оплату',
                'form' => $form->createView(),
            ]
        );
    }

    /**
     * @Route("/contractors/{contractor}/payments/{payment}", name="payment_update", requirements={"contractor"="\d+"})
     * @param Request $request
     * @param Contractor $contractor
     * @param SaveService $saveService
     * @param Payment $payment
     * @return RedirectResponse|Response
     */
    public function updateAction(Request $request, Contractor $contractor, SaveService $saveService, Payment $payment): Response
    {
        $this->denyAccessUnlessGranted(ContractorVoter::VIEW, $contractor);

        $form = $this->createForm(
            PaymentType::class,
            $payment
        );
        $form->handleRequest($request);

        if ($form->isValid()) {
            try {
                $saveService->save($payment);

                $this->addFlash('success', 'Оплата успешно изменена');
                return $this->redirectToRoute('contractor_view', ['contractor' => $contractor->getId()]);
            } catch (\Exception $e) {
                $this->addFlash('warning', 'При сохранении возникла ошибка.');
            }
        }

        return $this->render(
            '@App/payment/save.html.twig',
            [
                'page_header' => 'Создать оплату',
                'form' => $form->createView(),
            ]
        );
    }
}
