<?php

namespace AppBundle\Controller;

use AppBundle\Entity\Contractor;
use AppBundle\Entity\Timetable;
use AppBundle\Entity\User;
use Doctrine\ORM\EntityNotFoundException;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class ReportController extends Controller
{
    /**
     * @param Request $request
     * @return Response
     * @throws EntityNotFoundException
     * @throws \Doctrine\ORM\NonUniqueResultException
     * @throws \Doctrine\ORM\OptimisticLockException
     * @Route("/report-manager", name="report_manager")
     */
    public function managerAction(Request $request)
    {
        $timetableFilter = $this
            ->createFormBuilder(null, ['method' => 'GET', 'csrf_protection' => false])
            ->add(
                'timetable',
                EntityType::class,
                [
                    'class' => 'AppBundle\Entity\Timetable',
                    'choice_label' => 'name',
                    'label' => 'Табель',
                ]
            )
            ->getForm()
        ;
        $timetableFilter->handleRequest($request);

        if ($timetableFilter->isValid()) {
            $timetableHelper = $this->get('timetable.helper');
            $em = $this->getDoctrine()->getManager();

            /** @var Timetable $timetable */
            $timetable = $timetableFilter->get('timetable')->getData();

            if (!$timetable) {
                throw new EntityNotFoundException('Табель не найден');
            }

            $customerManagers = $em->getRepository('AppBundle:User')->getManagers();
            $currentTimetable = $em->getRepository('AppBundle:Timetable')->getCurrent();
            $customerManagerData = [];
            $providerManagerData = [
                'salary' => 0,
                'balance_positive' => 0,
                'balance_negative' => 0,
            ];
            /** @var User $customerManager */
            foreach ($customerManagers as $customerManager) {
                $timetableRows = $em->getRepository('AppBundle:TimetableRow')->findBy([
                    'manager' => $customerManager,
                    'timetable' => $timetable,
                ]);

                $row = [
                    'fio' => $customerManager->getFullName(),
                    'salary' => 0,
                    'margin_sum' => 0,
                    'margin_percent' => 0,
                    'balance_positive' => 0,
                    'balance_negative' => 0,
                ];

                $customers = [];
                foreach ($timetableRows as $timetableRow) {
                    $customers[] = $timetableRow->getCustomer()->getId();

                    $rowData = $timetableHelper->calculateRowData($timetableRow);

                    $row['salary'] += $rowData['customer_salary'];
                    $row['margin_sum'] += $rowData['margin_sum'];
                    $row['margin_percent'] += $rowData['margin_percent'];

                    $providerManagerData['salary'] += $rowData['provider_salary'];
                }

                if ($timetableRows) {
                    $row['margin_percent'] = $row['margin_percent'] / count($timetableRows);
                }
                $row['customers'] = count(array_unique($customers));

                $customers = $em->getRepository('AppBundle:Contractor')->findBy([
                    'manager' => $customerManager,
                ]);

                foreach ($customers as $customer) {
                    $customerBalance = $timetableHelper->contractorBalance($customer, $currentTimetable);

                    if ($customerBalance > 0) {
                        $row['balance_positive'] += $customerBalance;
                    } else {
                        $row['balance_negative'] += $customerBalance;
                    }
                }

                $customerManagerData[] = $row;
            }

            $providers = $em->getRepository('AppBundle:Contractor')->findBy([
                'type' => Contractor::PROVIDER
            ]);
            foreach ($providers as $provider) {
                $providerBalance = $timetableHelper->contractorBalance($provider, $currentTimetable);

                if ($providerBalance > 0) {
                    $providerManagerData['balance_positive'] += $providerBalance;
                } else {
                    $providerManagerData['balance_negative'] += $providerBalance;
                }
            }

            $summaryData = [
                'salary' => array_sum(array_column($customerManagerData, 'salary')),
                'margin_sum' => array_sum(array_column($customerManagerData, 'margin_sum')),
                'margin_percent' => 0,
            ];

            if ($count = count(array_filter(array_column($customerManagerData, 'margin_percent')))) {
                $summaryData['margin_percent'] = array_sum(array_column($customerManagerData, 'margin_percent')) / $count;
            }
        } else {
            $summaryData = null;
            $customerManagerData = null;
            $providerManagerData = null;
        }

        return $this->render(
            '@App/report/customer_manager.html.twig',
            [
                'timetable_filter' => $timetableFilter->createView(),
                'summary_data' => $summaryData,
                'customer_manager_data' => $customerManagerData,
                'provider_manager_data' => $providerManagerData,
            ]
        );
    }
}
