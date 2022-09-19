<?php

namespace AppBundle\Controller;

use AppBundle\Entity\Contact;
use AppBundle\Entity\Contractor;
use AppBundle\Form\ContactType;
use AppBundle\Security\ContractorVoter;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class ContactController
 */
class ContactController extends Controller
{
    /**
     * @Route("/contractors/{contractor}/contacts/create", name="contact_create", requirements={"contractor"="\d+"})
     * @param Request    $request
     * @param Contractor $contractor
     * @return RedirectResponse|Response
     */
    public function createAction(Request $request, Contractor $contractor)
    {
        $this->denyAccessUnlessGranted(ContractorVoter::VIEW, $contractor);

        $contact = new Contact();
        $contact->setContractor($contractor);

        $form = $this->createForm(
            ContactType::class,
            $contact
        );
        $form->handleRequest($request);

        if ($form->isValid()) {
            $em = $this->getDoctrine()->getManager();

            try {
                $em->persist($contact);
                $em->flush();

                $this->addFlash('success', 'Контакт успешно добавлен');
                return $this->redirectToRoute('contractor_view', ['contractor' => $contractor->getId()]);
            } catch (\Exception $e) {
                $this->addFlash('warning', 'При сохранении возникла ошибка.');
            }
        }

        return $this->render(
            '@App/contact/save.html.twig',
            [
                'page_header' => 'Создать контакт',
                'form' => $form->createView(),
            ]
        );
    }

    /**
     * @Route("/contacts/{contact}/delete", name="contact_delete", requirements={"contact"="\d+"})
     * @param Contact $contact
     * @param Request $request
     * @return RedirectResponse
     */
    public function deleteAction(Contact $contact, Request $request)
    {
        $this->denyAccessUnlessGranted(ContractorVoter::DELETE, $contact->getContractor());

        try {
            $em = $this->getDoctrine()->getManager();
            $em->remove($contact);
            $em->flush();
            $this->addFlash('success', 'Контакт удален.');
        } catch (\Exception $e) {
            $this->addFlash('warning', 'При удалении возникла ошибка.');
        }

        $referer = $request->headers->get('referer');

        return $this->redirect($request->get('redirect_url', $referer));
    }
}