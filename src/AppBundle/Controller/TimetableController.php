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
}
