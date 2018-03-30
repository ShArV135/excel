<?php

namespace AppBundle\Controller;

use AppBundle\Entity\Contractor;
use AppBundle\Entity\Payment;
use AppBundle\Entity\Timetable;
use AppBundle\Form\PaymentType;
use AppBundle\Form\TimetablePaymentType;
use AppBundle\Security\ContractorVoter;
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
    public function createContractorAction(Request $request, Contractor $contractor)
    {
        $this->denyAccessUnlessGranted(ContractorVoter::EDIT, $contractor);

        $payment = new Payment();
        $payment->setContractor($contractor);

        $form = $this->createForm(
            PaymentType::class,
            $payment
        );
        $form->handleRequest($request);

        if ($form->isValid()) {
            $em = $this->getDoctrine()->getManager();

            try {
                $em->persist($payment);
                $em->flush();

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
     * @param Request   $request
     * @param Timetable $timetable
     * @return RedirectResponse|Response
     */
    public function createTimetableAction(Request $request, Timetable $timetable)
    {
        $payment = new Payment();

        $qb = $this
            ->getDoctrine()
            ->getManager()
            ->getRepository('AppBundle:Contractor')
            ->createQueryBuilder('contractor')
            ->addOrderBy('contractor.name', 'ASC')
        ;

        switch (true) {
            case $this->isGranted('ROLE_CUSTOMER_MANAGER'):
                $qb
                    ->andWhere($qb->expr()->eq('contractor.manager', ':manager'))
                    ->setParameter('manager', $this->getUser())
                ;
                break;
            case $this->isGranted('ROLE_PROVIDER_MANAGER'):
                $qb
                    ->andWhere($qb->expr()->eq('contractor.type', ':type'))
                    ->setParameter('type', Contractor::PROVIDER)
                ;
                break;
        }

        $form = $this->createForm(
            TimetablePaymentType::class,
            $payment,
            [
                'contractors_qb' => $qb
            ]
        );
        $form->handleRequest($request);

        if ($form->isValid()) {
            $em = $this->getDoctrine()->getManager();

            try {
                $em->persist($payment);
                $em->flush();

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
     * @Route("/payments/{payment}/delete", name="payment_delete", requirements={"payment"="\d+"})
     * @param Payment $payment
     * @param Request $request
     * @return RedirectResponse
     */
    public function deleteAction(Payment $payment, Request $request)
    {
        $this->denyAccessUnlessGranted(ContractorVoter::DELETE, $payment->getContractor());

        try {
            $em = $this->getDoctrine()->getManager();
            $em->remove($payment);
            $em->flush();
            $this->addFlash('success', 'Оплата удалена.');
        } catch (\Exception $e) {
            $this->addFlash('warning', 'При удалении возникла ошибка.');
        }

        $referer = $request->headers->get('referer');

        return $this->redirect($request->get('redirect_url', $referer));
    }
}
