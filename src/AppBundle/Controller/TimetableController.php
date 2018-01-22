<?php

namespace AppBundle\Controller;

use AppBundle\Entity\Timetable;
use AppBundle\Security\TimetableVoter;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;

class TimetableController extends Controller
{
    /**
     * @Route("/timetable-create", name="timetable_create")
     * @return Response
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function createAction()
    {
        $em = $this->getDoctrine()->getManager();
        $em->getRepository('AppBundle:Timetable')->create();

        return $this->redirectToRoute('homepage');
    }

    /**
     * @Route("/timetable-delete/{timetable}", name="timetable_delete")
     * @param Timetable $timetable
     * @return RedirectResponse
     */
    public function deleteAction(Timetable $timetable)
    {
        $this->denyAccessUnlessGranted(TimetableVoter::DELETE, $timetable);

        try {
            $em = $this->getDoctrine()->getManager();
            $em->remove($timetable);
            $em->flush();
            $this->addFlash('success', 'Табель удален.');
        } catch (\Exception $e) {
            $this->addFlash('warning', 'При удалении возникла ошибка.');
        }

        return $this->redirectToRoute('homepage');
    }
}
