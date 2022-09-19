<?php

namespace AppBundle\Service;

use AppBundle\Entity\Plan;
use AppBundle\Entity\Timetable;
use AppBundle\Entity\User;
use AppBundle\Service\Timetable\ManagerSalaryService;
use AppBundle\Service\Timetable\MarginSumService;
use AppBundle\Service\Timetable\RowTimeStorage;
use Doctrine\ORM\EntityManagerInterface;

class PlanDataService
{
    private $entityManager;
    private $salaryService;
    private $rowTimeStorage;

    public function __construct(EntityManagerInterface $entityManager, ManagerSalaryService $salaryService, RowTimeStorage $rowTimeStorage)
    {
        $this->entityManager = $entityManager;
        $this->salaryService = $salaryService;
        $this->rowTimeStorage = $rowTimeStorage;
    }

    public function getData(Timetable $timetable, User $user = null): array
    {
        $planData = $this->planData($timetable, $user);

        $data = [
            'plan_amount' => number_format($planData['plan_amount'], 2, '.', ' ').' руб.',
            'plan_completed' => number_format($planData['plan_completed'], 2, '.', ' ').' руб.',
            'plan_completed_percent' => $planData['plan_completed_percent'].'%',
        ];

        if (isset($planData['left_amount'])) {
            $data['left_amount'] = number_format( $planData['left_amount'], 2, '.', ' ').' руб.';
            $data['left_amount_percent'] = $planData['left_amount_percent'].'%';
        }

        return $data;
    }

    public function planData(Timetable $timetable, User $user = null): array
    {
        $this->rowTimeStorage->init($timetable);
        $marginSumService = new MarginSumService($this->salaryService);
        $planAmount = 0;
        $planCompleted = 0;
        $planCompletedPercent = 0;

        $plans = $this->getPlans($timetable, $user);

        /** @var Plan $plan */
        foreach ($plans as $plan) {
            $planAmount += $plan->getAmount();
        }

        $criteria = [
            'timetable' => $timetable,
        ];
        if ($user) {
            $criteria['manager'] = $user;
        }

        $timetableRows = $this->entityManager->getRepository('AppBundle:TimetableRow')->findBy($criteria);
        foreach ($timetableRows as $timetableRow) {
            if ($timetable->isMarginPlan()) {
                $planCompleted += $marginSumService->rowMarginSum($timetableRow);
            } else {
                $planCompleted += $this->salaryService->rowCustomSalary($timetableRow);
            }
        }

        if ($planAmount > 0) {
            $planCompletedPercent = floor($planCompleted * 100 / $planAmount);
        }

        $data = [
            'plan_amount' => $planAmount,
            'plan_completed' => $planCompleted,
            'plan_completed_percent' => $planCompletedPercent,
        ];

        if ($planAmount > $planCompleted) {
            $data['left_amount'] = $planAmount - $planCompleted;
            $data['left_amount_percent'] = 100 - $planCompletedPercent;
        }

        return $data;
    }

    private function getPlans(Timetable $timetable, User $user = null): array
    {
        $qb = $this
            ->entityManager
            ->getRepository(Plan::class)
            ->createQueryBuilder('plan')
            ->andWhere('plan.timetable = :timetable')
            ->setParameter('timetable', $timetable)
        ;
        if ($user) {
            $qb
                ->andWhere($qb->expr()->eq('plan.user', ':user'))
                ->setParameter('user', $user)
            ;
        }

        return $qb->getQuery()->getResult();
    }
}
