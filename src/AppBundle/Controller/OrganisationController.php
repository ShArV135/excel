<?php

namespace AppBundle\Controller;

use AppBundle\Entity\Organisation;
use AppBundle\Form\OrganisationType;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class ContractorController
 */
class OrganisationController extends Controller
{
    /**
     * @Route("/organisations/{page}", name="organisation_list", requirements={"page"="\d+"})
     * @param int $page
     * @return Response
     */
    public function listAction($page = 1)
    {
        $em = $this->getDoctrine()->getManager();
        $qb = $em->getRepository('AppBundle:Organisation')->createQueryBuilder('organisation');

        $paginator  = $this->get('knp_paginator');
        $pagination = $paginator->paginate($qb, $page,20);

        return $this->render(
            '@App/organisations/list.html.twig',
            [
                'pagination' => $pagination,
            ]
        );
    }

    /**
     * @Route("/organisations/create", name="organisation_create")
     * @param Request $request
     * @return RedirectResponse|Response
     */
    public function createAction(Request $request)
    {
        $organisation = new Organisation();

        $form = $this->createForm(OrganisationType::class, $organisation);
        $form->handleRequest($request);

        if ($form->isValid()) {
            $em = $this->getDoctrine()->getManager();

            try {
                $em->persist($organisation);
                $em->flush();

                $this->addFlash('success', 'Организация успешно добавлена');
                return $this->redirectToRoute('organisation_list');
            } catch (\Exception $e) {
                $this->addFlash('warning', 'При сохранении возникла ошибка.');
            }
        }

        return $this->render(
            '@App/organisations/save.html.twig',
            [
                'page_header' => 'Создать организацию',
                'form' => $form->createView(),
            ]
        );
    }

    /**
     * @Route("/organisations/{organisation}/update", name="organisation_update", requirements={"organisation"="\d+"})
     * @param Request      $request
     * @param Organisation $organisation
     * @return RedirectResponse|Response
     */
    public function updateAction(Request $request, Organisation $organisation)
    {
        $form = $this->createForm(OrganisationType::class, $organisation);
        $form->handleRequest($request);

        if ($form->isValid()) {
            $em = $this->getDoctrine()->getManager();

            try {
                $em->flush();

                $this->addFlash('success', 'Организация успешно изменена');

                return $this->redirectToRoute('organisation_list');
            } catch (\Exception $e) {
                $this->addFlash('warning', 'При сохранении возникла ошибка.');
            }
        }

        return $this->render(
            '@App/organisations/save.html.twig',
            [
                'page_header' => 'Изменить организацию',
                'form' => $form->createView(),
            ]
        );
    }

    /**
     * @Route("/organisations/{organisation}/delete", name="organisation_delete", requirements={"organisation"="\d+"})
     * @param Organisation $organisation
     * @param Request      $request
     * @return RedirectResponse
     */
    public function deleteAction(Organisation $organisation, Request $request)
    {
        try {
            $em = $this->getDoctrine()->getManager();
            $em->remove($organisation);
            $em->flush();
            $this->addFlash('success', 'Организация удалена.');
        } catch (\Exception $e) {
            $this->addFlash('warning', 'При удалении возникла ошибка.');
        }

        $referer = $request->headers->get('referer');

        return $this->redirect($request->get('redirect_url', $referer));
    }
}