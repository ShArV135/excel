<?php

namespace AppBundle\Controller;

use AppBundle\Entity\Contractor;
use AppBundle\Form\ContractorType;
use AppBundle\Security\ContractorVoter;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\File\Exception\AccessDeniedException;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Intl\Exception\NotImplementedException;

/**
 * Class ContractorController
 */
class ContractorController extends Controller
{
    /**
     * @Route("/contractors/{page}", name="contractor_list")
     * @param int $page
     * @return Response
     */
    public function listAction($page = 1)
    {
        $em = $this->getDoctrine()->getManager();
        $qb = $em->getRepository('AppBundle:Contractor')->createQueryBuilder('contractor');

        if ($this->isGranted('ROLE_CUSTOMER_MANAGER')) {
            $qb
                ->andWhere($qb->expr()->eq('contractor.manager', ':manager'))
                ->setParameter('manager', $this->getUser())
            ;
        }

        $paginator  = $this->get('knp_paginator');
        $pagination = $paginator->paginate($qb, $page,20);

        return $this->render(
            '@App/contractor/list.html.twig',
            [
                'pagination' => $pagination,
            ]
        );
    }

    /**
     * @Route("/contractors/create/{type}", name="contractor_create", requirements={"type"="\w+"})
     * @param Request $request
     * @param         $type
     * @return RedirectResponse|Response
     */
    public function createAction(Request $request, $type)
    {
        $contractor = new Contractor();
        $choiceManager = false;

        switch ($type) {
            case Contractor::CUSTOMER:
                $pageHeader = 'Добавить заказчика';
                $successMessage = 'Заказчик успешно добавлен.';
                $contractor->setType(Contractor::CUSTOMER);

                if ($this->isGranted('ROLE_CUSTOMER_MANAGER')) {
                    $contractor->setManager($this->getUser());
                } else {
                    $choiceManager = true;
                }
                break;
            case Contractor::PROVIDER:
                if ($this->isGranted('ROLE_CUSTOMER_MANAGER')) {
                    throw new AccessDeniedException('Access denied.');
                }
                $pageHeader = 'Добавить поставщика';
                $successMessage = 'Поставщик успешно добавлен.';
                $contractor->setType(Contractor::PROVIDER);
                break;
            default:
                throw new NotImplementedException('Not implemented.');
        }

        $form = $this->createForm(
            ContractorType::class,
            $contractor,
            [
                'contractor_type' => $type,
                'choice_manager' => $choiceManager,
            ]
        );
        $form->handleRequest($request);

        if ($form->isValid()) {
            $em = $this->getDoctrine()->getManager();

            try {
                $em->persist($contractor);
                $em->flush();

                $this->addFlash('success', $successMessage);
                return $this->redirectToRoute('contractor_list');
            } catch (\Exception $e) {
                $this->addFlash('warning', 'При сохранении возникла ошибка.');
            }
        }

        return $this->render(
            '@App/contractor/save.html.twig',
            [
                'page_header' => $pageHeader,
                'form' => $form->createView(),
            ]
        );
    }

    /**
     * @Route("/contractors/{contractor}/update", name="contractor_update", requirements={"contractor"="\d+"})
     * @param Request    $request
     * @param Contractor $contractor
     * @return RedirectResponse|Response
     */
    public function updateAction(Request $request, Contractor $contractor)
    {
        $this->denyAccessUnlessGranted(ContractorVoter::EDIT, $contractor);
        $choiceManager = false;

        switch ($contractor->getType()) {
            case Contractor::CUSTOMER:
                $pageHeader = 'Редактировать заказчика';
                $successMessage = 'Заказчик успешно сохранен.';
                $contractor->setType(Contractor::CUSTOMER);

                if (!$this->isGranted('ROLE_CUSTOMER_MANAGER')) {
                    $choiceManager = true;
                }
                break;
            case Contractor::PROVIDER:
                $pageHeader = 'Редактировать поставщика';
                $successMessage = 'Поставщик успешно сохранен.';
                $contractor->setType(Contractor::PROVIDER);
                break;
            default:
                throw new NotImplementedException('Not implemented.');
        }

        $form = $this->createForm(
            ContractorType::class,
            $contractor,
            [
                'contractor_type' => $contractor->getType(),
                'choice_manager' => $choiceManager,
            ]
        );
        $form->handleRequest($request);

        if ($form->isValid()) {
            $em = $this->getDoctrine()->getManager();

            try {
                $em->flush();

                $this->addFlash('success', $successMessage);
                return $this->redirectToRoute('contractor_list');
            } catch (\Exception $e) {
                $this->addFlash('warning', 'При сохранении возникла ошибка.');
            }
        }

        return $this->render(
            '@App/contractor/save.html.twig',
            [
                'page_header' => $pageHeader,
                'form' => $form->createView(),
            ]
        );
    }

    /**
     * @Route("/contractors/{contractor}/view", name="contractor_view", requirements={"contractor"="\d+"})
     * @param Contractor $contractor
     * @return Response
     */
    public function viewAction(Contractor $contractor)
    {
        $this->denyAccessUnlessGranted(ContractorVoter::VIEW, $contractor);

        return $this->render(
            '@App/contractor/view.html.twig',
            [
                'contractor' => $contractor,
            ]
        );
    }

    /**
     * @param Contractor $contractor
     * @Route("/contractors/delete/{contractor}", name="contractor_delete", requirements={"contractor"="\d+"})
     * @return RedirectResponse
     */
    public function deleteAction(Contractor $contractor)
    {
        $this->denyAccessUnlessGranted(ContractorVoter::DELETE, $contractor);

        try {
            $em = $this->getDoctrine()->getManager();
            $em->remove($contractor);
            $em->flush();
            $this->addFlash('success', 'Контрагент удален.');
        } catch (\Exception $e) {
            $this->addFlash('warning', 'При удалении возникла ошибка.');
        }

        return $this->redirectToRoute('contractor_list');
    }
}
