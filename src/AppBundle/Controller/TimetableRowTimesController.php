<?php

namespace AppBundle\Controller;

use AppBundle\Entity\TimetableRowTimes;
use AppBundle\Security\TimetableRowVoter;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class TimetableRowTimesController extends Controller
{
    /**
     * @Route("/timetable-row-times/{timetableRowTimes}/update", name="timetable_row_times_update", options={"expose"=true})
     * @param TimetableRowTimes $timetableRowTimes
     * @param Request           $request
     * @return Response
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function updateAction(TimetableRowTimes $timetableRowTimes, Request $request)
    {
        if (!$request->isXmlHttpRequest()) {
            throw new AccessDeniedHttpException();
        }

        $this->denyAccessUnlessGranted(TimetableRowVoter::EDIT, $timetableRowTimes->getTimetableRow());

        if (!$day = $request->get('day')) {
            throw new BadRequestHttpException();
        }

        $value = $request->get('value');
        if (is_null($value) || $value == '') {
            throw new BadRequestHttpException();
        }

        $times = $timetableRowTimes->getTimes();

        if (!isset($times[$day])) {
            throw new BadRequestHttpException();
        }

        $times[$day] = $value;

        $timetableRowTimes->setTimes($times);
        $this->getDoctrine()->getManager()->flush();

        $timetable = $timetableRowTimes->getTimetable();
        $timetableRow = $timetableRowTimes->getTimetableRow();

        return new JsonResponse([
            'status' => 'OK',
            'data' => $this->get('timetable.helper')->calculateRowData($timetable, $timetableRow),
        ]);
    }

    /**
     * @Route("/timetable-row-times/{timetableRowTimes}/update-comment", name="timetable_row_times_update_comment")
     * @param TimetableRowTimes $timetableRowTimes
     * @param Request           $request
     * @return JsonResponse
     */
    public function updateCommentAction(TimetableRowTimes $timetableRowTimes, Request $request)
    {
        if (!$request->isXmlHttpRequest()) {
            throw new AccessDeniedHttpException();
        }

        $this->denyAccessUnlessGranted(TimetableRowVoter::EDIT, $timetableRowTimes->getTimetableRow());

        if (!$day = $request->get('name')) {
            throw new BadRequestHttpException();
        }

        $value = $request->get('value');

        $comments = $timetableRowTimes->getComments();

        if (!isset($comments[$day])) {
            throw new BadRequestHttpException();
        }

        $comments[$day] = $value;

        $timetableRowTimes->setComments($comments);
        $this->getDoctrine()->getManager()->flush();

        return new JsonResponse([
            'status' => 'OK',
        ]);

    }
}
