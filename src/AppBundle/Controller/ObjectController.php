<?php

namespace AppBundle\Controller;

use AppBundle\Entity\Contractor;
use AppBundle\Entity\Object;
use AppBundle\Form\ObjectType;
use AppBundle\Security\ContractorVoter;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class ObjectController
 */
class ObjectController extends Controller
{
    /**
     * @Route("/contractors/{contractor}/objects/create", name="object_create", requirements={"contractor"="\d+"})
     * @param Request    $request
     * @param Contractor $contractor
     * @return RedirectResponse|Response
     */
    public function createAction(Request $request, Contractor $contractor)
    {
        $this->denyAccessUnlessGranted(ContractorVoter::EDIT, $contractor);

        $object = new Object();
        $object->setContractor($contractor);

        $form = $this->createForm(
            ObjectType::class,
            $object
        );
        $form->handleRequest($request);

        if ($form->isValid()) {
            $em = $this->getDoctrine()->getManager();

            try {
                $em->persist($object);
                $em->flush();

                $this->addFlash('success', 'Объект успешно добавлена');
                return $this->redirectToRoute('contractor_view', ['contractor' => $contractor->getId()]);
            } catch (\Exception $e) {
                $this->addFlash('warning', 'При сохранении возникла ошибка.');
            }
        }

        return $this->render(
            '@App/object/save.html.twig',
            [
                'page_header' => 'Создать объект',
                'form' => $form->createView(),
            ]
        );
    }

    /**
     * @Route("/objects/{object}/delete", name="object_delete", requirements={"object"="\d+"})
     * @param Object $object
     * @param Request $request
     * @return RedirectResponse
     */
    public function deleteAction(Object $object, Request $request)
    {
        $this->denyAccessUnlessGranted(ContractorVoter::DELETE, $object->getContractor());

        try {
            $em = $this->getDoctrine()->getManager();
            $em->remove($object);
            $em->flush();
            $this->addFlash('success', 'Объект удален.');
        } catch (\Exception $e) {
            $this->addFlash('warning', 'При удалении возникла ошибка.');
        }

        $referer = $request->headers->get('referer');

        return $this->redirect($request->get('redirect_url', $referer));
    }
}