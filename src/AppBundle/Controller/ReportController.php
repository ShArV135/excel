<?php

namespace AppBundle\Controller;

use AppBundle\Entity\Contractor;
use AppBundle\Entity\Timetable;
use AppBundle\Entity\User;
use Doctrine\ORM\EntityNotFoundException;
use Doctrine\ORM\EntityRepository;
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
            $customerManagerData = [];
            $providerData = [
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

                    $providerData['salary'] += $rowData['provider_salary'];
                }

                if ($timetableRows) {
                    $row['margin_percent'] = $row['margin_percent'] / count($timetableRows);
                }
                $row['customers'] = count(array_unique($customers));

                $customers = $em->getRepository('AppBundle:Contractor')->findBy([
                    'manager' => $customerManager,
                ]);

                foreach ($customers as $customer) {
                    $customerBalance = $timetableHelper->contractorBalance($customer);

                    if ($customerBalance > 0) {
                        $row['balance_positive'] += $customerBalance;
                    } else {
                        $row['balance_negative'] += $customerBalance;
                    }
                }

                $planData = $timetableHelper->planData($timetable, $customerManager);
                $row['plan_completed_percent'] = $planData['plan_completed_percent'];

                $customerManagerData[] = $row;
            }

            $providerManagers = $em->getRepository('AppBundle:User')->getManagers('ROLE_PROVIDER_MANAGER');
            $providerManagerData = [];
            /** @var User $providerManager */
            foreach ($providerManagers as $providerManager) {
                $timetableRows = $em->getRepository('AppBundle:TimetableRow')->findBy([
                    'providerManager' => $providerManager,
                    'timetable' => $timetable,
                ]);

                $row = [
                    'fio' => $providerManager->getFullName(),
                    'salary' => 0,
                ];

                foreach ($timetableRows as $timetableRow) {
                    $rowData = $timetableHelper->calculateRowData($timetableRow);

                    $row['salary'] += $rowData['provider_salary'];
                }

                $providerManagerData[] = $row;
            }

            $providers = $em->getRepository('AppBundle:Contractor')->findBy([
                'type' => Contractor::PROVIDER,
            ]);
            foreach ($providers as $provider) {
                $providerBalance = $timetableHelper->contractorBalance($provider);

                if ($providerBalance > 0) {
                    $providerData['balance_positive'] += $providerBalance;
                } else {
                    $providerData['balance_negative'] += $providerBalance;
                }
            }

            $planData = $timetableHelper->planData($timetable);

            $summaryData = [
                'salary' => array_sum(array_column($customerManagerData, 'salary')),
                'margin_sum' => array_sum(array_column($customerManagerData, 'margin_sum')),
                'margin_percent' => 0,
                'plan_completed_percent' => $planData['plan_completed_percent'],
            ];

            if ($count = count(array_filter(array_column($customerManagerData, 'margin_percent')))) {
                $summaryData['margin_percent'] = array_sum(array_column($customerManagerData, 'margin_percent')) / $count;
            }
        } else {
            $summaryData = null;
            $customerManagerData = null;
            $providerData = null;
            $providerManagerData = null;
        }

        return $this->render(
            '@App/report/manager.html.twig',
            [
                'timetable_filter' => $timetableFilter->createView(),
                'summary_data' => $summaryData,
                'customer_manager_data' => $customerManagerData,
                'provider_manager_data' => $providerManagerData,
                'provider_data' => $providerData,
            ]
        );
    }

    /**
     * @Route("/report-sale", name="report_sale")
     * @param Request $request
     * @return Response
     * @throws \Doctrine\ORM\NonUniqueResultException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function saleAction(Request $request)
    {
        $em = $this->getDoctrine()->getManager();

        $manager = $this->isGranted('ROLE_CUSTOMER_MANAGER') ? $this->getUser() : null;

        $timetableFilter = $this
            ->createFormBuilder(null, ['method' => 'GET', 'csrf_protection' => false])
            ->add(
                'timetable',
                EntityType::class,
                [
                    'placeholder' => 'За всё время',
                    'required' => false,
                    'class' => 'AppBundle\Entity\Timetable',
                    'choice_label' => 'name',
                    'label' => 'Табель',
                ]
            )
            ->add(
                'customer',
                EntityType::class,
                [
                    'required' => false,
                    'attr' => ['class' => 'select2me'],
                    'class' => 'AppBundle\Entity\Contractor',
                    'choice_label' => 'name',
                    'label' => 'Заказчик',
                    'query_builder' => function(EntityRepository $repository) use ($manager) {
                        $qb = $repository->createQueryBuilder('e');

                        $qb
                            ->where($qb->expr()->eq('e.type', ':type'))
                            ->setParameter('type', Contractor::CUSTOMER)
                        ;

                        if ($manager) {
                            $qb
                                ->andWhere($qb->expr()->eq('e.manager', ':manager'))
                                ->setParameter('manager', $manager)
                            ;
                        }

                        return $qb;
                    },
                ]
            )
            ->getForm()
        ;

        if (!$this->isGranted('ROLE_CUSTOMER_MANAGER')) {
            $timetableFilter
                ->add(
                    'manager',
                    EntityType::class,
                    [
                        'required' => false,
                        'attr' => ['class' => 'select2me'],
                        'class' => 'AppBundle\Entity\User',
                        'choice_label' => 'fullname',
                        'label' => 'Менеджер',
                        'choices' => $em->getRepository('AppBundle:User')->getManagers(),
                    ]
                )
            ;
        }

        $timetableFilter->handleRequest($request);

        if ($timetableFilter->isValid()) {
            $data = $timetableFilter->getData();
            $timetableHelper = $this->get('timetable.helper');
            $salesData = [];

            if (!empty($data['timetable'])) {
                $timetables = $data['timetable'];
            } else {
                $timetables = $em->getRepository('AppBundle:Timetable')->findAll();

                if (empty($data['customer'])) {
                    $criteria = [
                        'type' => Contractor::CUSTOMER,
                    ];

                    if ($this->isGranted('ROLE_CUSTOMER_MANAGER')) {
                        $criteria['manager'] = $this->getUser();
                    } else {
                        if (!empty($data['manager'])) {
                            $criteria['manager'] = $data['manager'];
                        }
                    }

                    $customers = $em->getRepository('AppBundle:Contractor')->findBy($criteria, ['name' => 'ASC']);
                    foreach ($customers as $customer) {
                        $salesData[$customer->getId()] = [
                            'name' => $customer->getName(),
                            'balance' => $timetableHelper->contractorBalance($customer),
                            'manager' => $customer->getManager()->getFullName(),
                            'salary' => 0,
                            'margin_sum' => 0,
                            'margin_percent' => 0,
                            'counter' => 0,
                        ];
                    }
                }
            }

            $criteria = [
                'timetable' => $timetables,
            ];
            if ($this->isGranted('ROLE_CUSTOMER_MANAGER')) {
                $criteria['manager'] = $this->getUser();
            } else {
                if (!empty($data['manager'])) {
                    $criteria['manager'] = $data['manager'];
                }
            }
            if (!empty($data['customer'])) {
                $criteria['customer'] = $data['customer'];
            }
            $timetableRows = $em->getRepository('AppBundle:TimetableRow')->findBy($criteria);

            foreach ($timetableRows as $timetableRow) {
                $customer = $timetableRow->getCustomer();

                if (!isset($salesData[$customer->getId()])) {
                    $salesData[$customer->getId()] = [
                        'manager' => $timetableRow->getManager()->getFullName(),
                        'name' => $customer->getName(),
                        'salary' => 0,
                        'balance' => $timetableHelper->contractorBalance($customer),
                        'margin_sum' => 0,
                        'margin_percent' => 0,
                        'counter' => 0,
                    ];
                }

                $rowData = $timetableHelper->calculateRowData($timetableRow);

                $salesData[$customer->getId()]['salary'] += $rowData['customer_salary'];
                $salesData[$customer->getId()]['margin_sum'] += $rowData['margin_sum'];
                $salesData[$customer->getId()]['margin_percent'] += $rowData['margin_percent'];
                $salesData[$customer->getId()]['counter']++;
            }

            foreach ($salesData as $i => $data) {
                if ($data['counter'] > 0) {
                    $salesData[$i]['margin_percent'] = $data['margin_percent'] / $data['counter'];
                }
            }

            $summaryData = [
                'salary' => array_sum(array_column($salesData, 'salary')),
                'balance' => array_sum(array_column($salesData, 'balance')),
                'margin_sum' => array_sum(array_column($salesData, 'margin_sum')),
                'margin_percent' => 0,
            ];
            if ($count = count(array_filter(array_column($salesData, 'margin_percent')))) {
                $summaryData['margin_percent'] = array_sum(array_column($salesData, 'margin_percent')) / $count;
            }

        } else {
            $salesData = null;
            $summaryData = null;
        }

        return $this->render(
            '@App/report/sale.html.twig',
            [
                'timetable_filter' => $timetableFilter->createView(),
                'sales_data' => $salesData,
                'summary_data' => $summaryData,
            ]
        );
    }

    /**
     * @Route("/report-provide", name="report_provide")
     * @param Request $request
     * @return Response
     * @throws \Doctrine\ORM\NonUniqueResultException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function provideAction(Request $request)
    {
        $em = $this->getDoctrine()->getManager();

        $timetableFilter = $this
            ->createFormBuilder(null, ['method' => 'GET', 'csrf_protection' => false])
            ->add(
                'timetable',
                EntityType::class,
                [
                    'placeholder' => 'За всё время',
                    'required' => false,
                    'class' => 'AppBundle\Entity\Timetable',
                    'choice_label' => 'name',
                    'label' => 'Табель',
                ]
            )
            ->add(
                'provider',
                EntityType::class,
                [
                    'required' => false,
                    'attr' => ['class' => 'select2me'],
                    'class' => 'AppBundle\Entity\Contractor',
                    'choice_label' => 'name',
                    'label' => 'Поставщик',
                    'query_builder' => function(EntityRepository $repository) {
                        $qb = $repository->createQueryBuilder('e');

                        $qb
                            ->where($qb->expr()->eq('e.type', ':type'))
                            ->setParameter('type', Contractor::PROVIDER)
                        ;

                        return $qb;
                    },
                ]
            )
            ->getForm()
        ;
        $timetableFilter->handleRequest($request);

        if ($timetableFilter->isValid()) {
            $data = $timetableFilter->getData();
            $timetableHelper = $this->get('timetable.helper');
            $provideData = [];

            if (!empty($data['timetable'])) {
                $timetables = $data['timetable'];
            } else {
                $timetables = $em->getRepository('AppBundle:Timetable')->findAll();

                if (empty($data['provider'])) {
                    $providers = $em->getRepository('AppBundle:Contractor')->findBy(['type' => Contractor::PROVIDER], ['name' => 'ASC']);
                    foreach ($providers as $provider) {
                        $provideData[$provider->getId()] = [
                            'name' => $provider->getName(),
                            'balance' => $timetableHelper->contractorBalance($provider),
                            'manager' => '',
                            'salary' => 0,
                        ];
                    }
                }
            }

            $criteria = [
                'timetable' => $timetables,
            ];
            if (!empty($data['provider'])) {
                $criteria['provider'] = $data['provider'];
            }
            $timetableRows = $em->getRepository('AppBundle:TimetableRow')->findBy($criteria);

            foreach ($timetableRows as $timetableRow) {
                $provider = $timetableRow->getProvider();

                if (!$provider) {
                    continue;
                }

                if (!isset($provideData[$provider->getId()])) {
                    $provideData[$provider->getId()] = [
                        'name' => $provider->getName(),
                        'salary' => 0,
                        'balance' => $timetableHelper->contractorBalance($provider),
                    ];
                }

                $rowData = $timetableHelper->calculateRowData($timetableRow);

                $provideData[$provider->getId()]['salary'] += $rowData['provider_salary'];
            }

            $summaryData = [
                'salary' => array_sum(array_column($provideData, 'salary')),
                'balance' => array_sum(array_column($provideData, 'balance')),
            ];

        } else {
            $provideData = null;
            $summaryData = null;
        }

        return $this->render(
            '@App/report/provide.html.twig',
            [
                'timetable_filter' => $timetableFilter->createView(),
                'provide_data' => $provideData,
                'summary_data' => $summaryData,
            ]
        );

    }
}
