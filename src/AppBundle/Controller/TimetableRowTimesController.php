<?php

namespace AppBundle\Controller;

use AppBundle\Entity\TimetableRowTimes;
use AppBundle\Security\TimetableRowVoter;
use AppBundle\Service\TimeUpdateService;
use Doctrine\ORM\EntityNotFoundException;
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
     * @param Request $request
     * @param TimeUpdateService $updateService
     * @return Response
     */
    public function updateAction(TimetableRowTimes $timetableRowTimes, Request $request, TimeUpdateService $updateService): Response
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

        $updateService->update($timetableRowTimes, $day, $value);

        return new JsonResponse([
            'status' => 'OK',
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

    /**
     * @Route("/timetable-row-times/update-colors/{color}", name="timetable_row_times_update_colors", options={"expose"=true})
     * @param         $color
     * @param Request $request
     * @return JsonResponse
     * @throws EntityNotFoundException
     */
    public function updateColorsAction($color, Request $request)
    {
        if (!$request->isXmlHttpRequest()) {
            throw new AccessDeniedHttpException();
        }

        $availableColors = ['yellow', 'green', 'blue', 'purple', 'no-color'];

        if (!in_array($color, $availableColors)) {
            throw new BadRequestHttpException();
        }

        if ($color === 'no-color') {
            $color = '';
        }

        $data = $request->get('data');
        if (!is_array($data)) {
            throw new BadRequestHttpException();
        }

        $em = $this->getDoctrine()->getManager();

        foreach ($data as $datum) {
            $id = $datum['id'] ?: null;
            $day = $datum['day'] ?: null;

            if (!$id || !$day) {
                throw new BadRequestHttpException();
            }

            $timetableRowTimes = $em->find('AppBundle:TimetableRowTimes', $id);
            if (!$timetableRowTimes) {
                throw new EntityNotFoundException('Times not found.');
            }
            $this->denyAccessUnlessGranted(TimetableRowVoter::EDIT, $timetableRowTimes->getTimetableRow());

            $colors = $timetableRowTimes->getColors();

            if (!isset($colors[$day])) {
                throw new BadRequestHttpException();
            }

            $colors[$day] = $color;

            $timetableRowTimes->setColors($colors);
        }

        $em->flush();

        return new JsonResponse([
            'status' => 'OK',
        ]);
    }
}
