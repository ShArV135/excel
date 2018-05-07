<?php

namespace AppBundle\Service;

use AppBundle\Entity\Contractor;
use AppBundle\Entity\Organisation;
use AppBundle\Entity\Timetable;
use AppBundle\Entity\User;
use Doctrine\ORM\EntityManager;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorage;
use Symfony\Component\Security\Core\Authorization\AuthorizationChecker;

class ReportHelper
{
    private $entityManager;
    private $timetableHelper;
    private $authorizationChecker;
    private $tokenStorage;

    /**
     * ReportHelper constructor.
     * @param EntityManager        $entityManager
     * @param TimetableHelper      $timetableHelper
     * @param AuthorizationChecker $authorizationChecker
     * @param TokenStorage         $tokenStorage
     */
    public function __construct(EntityManager $entityManager, TimetableHelper $timetableHelper, AuthorizationChecker $authorizationChecker, TokenStorage $tokenStorage)
    {
        $this->entityManager = $entityManager;
        $this->timetableHelper = $timetableHelper;
        $this->authorizationChecker = $authorizationChecker;
        $this->tokenStorage = $tokenStorage;
    }

    /**
     * @param Timetable         $timetable
     * @param Organisation|null $organisation
     * @return array
     * @throws \Doctrine\ORM\ORMException
     */
    public function getManagerData(Timetable $timetable, Organisation $organisation = null)
    {
        $customerManagers = $this->entityManager->getRepository('AppBundle:User')->getManagers();
        $customerManagerData = [];
        $providerData = [
            'salary' => 0,
            'balance_positive' => 0,
            'balance_negative' => 0,
        ];
        /** @var User $customerManager */
        foreach ($customerManagers as $customerManager) {
            $timetableRows = $this->entityManager->getRepository('AppBundle:TimetableRow')->findBy([
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
                $customer = $timetableRow->getCustomer();

                if ($organisation && $customer->getOrganisation() !== $organisation) {
                    continue;
                }

                $customers[] = $customer->getId();

                $rowData = $this->timetableHelper->calculateRowData($timetableRow);

                $row['salary'] += $rowData['customer_salary'];
                $row['margin_sum'] += $rowData['margin_sum'];
                $row['margin_percent'] += $rowData['margin_percent'];

                $providerData['salary'] += $rowData['provider_salary'];
            }

            if ($timetableRows) {
                $row['margin_percent'] = $row['margin_percent'] / count($timetableRows);
            }
            $row['customers'] = count(array_unique($customers));

            $criteria = [
                'manager' => $customerManager,
            ];

            if ($organisation) {
                $criteria['organisation'] = $organisation;
            }

            $customers = $this->entityManager->getRepository('AppBundle:Contractor')->findBy($criteria, [
                'name' => 'ASC',
            ]);

            foreach ($customers as $customer) {
                $customerBalance = $this->timetableHelper->contractorBalance($customer);

                if ($customerBalance > 0) {
                    $row['balance_positive'] += $customerBalance;
                } else {
                    $row['balance_negative'] += $customerBalance;
                }
            }

            $planData = $this->timetableHelper->planData($timetable, $customerManager);
            $row['plan_completed_percent'] = $planData['plan_completed_percent'];

            $customerManagerData[] = $row;
        }

        $providerManagers = $this->entityManager->getRepository('AppBundle:User')->getManagers('ROLE_PROVIDER_MANAGER');
        $providerManagerData = [];
        /** @var User $providerManager */
        foreach ($providerManagers as $providerManager) {
            $timetableRows = $this->entityManager->getRepository('AppBundle:TimetableRow')->findBy([
                'providerManager' => $providerManager,
                'timetable' => $timetable,
            ]);

            $row = [
                'fio' => $providerManager->getFullName(),
                'salary' => 0,
            ];

            foreach ($timetableRows as $timetableRow) {
                if ($organisation) {
                    $provider = $timetableRow->getProvider();
                    if (!$provider || $provider->getOrganisation() !== $organisation) {
                        continue;
                    }
                }

                $rowData = $this->timetableHelper->calculateRowData($timetableRow);

                $row['salary'] += $rowData['provider_salary'];
            }

            $providerManagerData[] = $row;
        }

        $criteria = [
            'type' => Contractor::PROVIDER,
        ];

        if ($organisation) {
            $criteria['organisation'] = $organisation;
        }

        $providers = $this->entityManager->getRepository('AppBundle:Contractor')->findBy($criteria, [
            'name' => 'ASC',
        ]);
        foreach ($providers as $provider) {
            $providerBalance = $this->timetableHelper->contractorBalance($provider);

            if ($providerBalance > 0) {
                $providerData['balance_positive'] += $providerBalance;
            } else {
                $providerData['balance_negative'] += $providerBalance;
            }
        }

        $planData = $this->timetableHelper->planData($timetable);

        $summaryData = [
            'salary' => array_sum(array_column($customerManagerData, 'salary')),
            'margin_sum' => array_sum(array_column($customerManagerData, 'margin_sum')),
            'margin_percent' => 0,
            'plan_completed_percent' => $planData['plan_completed_percent'],
        ];

        if ($count = count(array_filter(array_column($customerManagerData, 'margin_percent')))) {
            $summaryData['margin_percent'] = array_sum(array_column($customerManagerData, 'margin_percent')) / $count;
        }

        return [
            'summary_data' => $summaryData,
            'customer_manager_data' => $customerManagerData,
            'provider_manager_data' => $providerManagerData,
            'provider_data' => $providerData,
        ];
    }

    /**
     * @param array             $filter
     * @param Organisation|null $organisation
     * @return array
     * @throws \Doctrine\ORM\NonUniqueResultException
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function getSalesData(array $filter, Organisation $organisation = null)
    {
        $user = $this->tokenStorage->getToken()->getUser();
        $salesData = [];
        if (!empty($filter['timetable'])) {
            $timetables = $filter['timetable'];
        } else {
            $timetables = $this->entityManager->getRepository('AppBundle:Timetable')->findAll();

            if (empty($filter['customer'])) {
                $criteria = [
                    'type' => Contractor::CUSTOMER,
                ];

                if ($this->authorizationChecker->isGranted('ROLE_CUSTOMER_MANAGER')) {
                    $criteria['manager'] = $user;
                } else {
                    if (!empty($filter['manager'])) {
                        $criteria['manager'] = $filter['manager'];
                    }
                }

                if ($organisation) {
                    $criteria['organisation'] = $organisation;
                }

                $customers = $this->entityManager->getRepository('AppBundle:Contractor')->findBy($criteria, ['name' => 'ASC']);
                foreach ($customers as $customer) {
                    $salesData[$customer->getId()] = [
                        'name' => $customer->getName(),
                        'balance' => $this->timetableHelper->contractorBalance($customer),
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
        if ($this->authorizationChecker->isGranted('ROLE_CUSTOMER_MANAGER')) {
            $criteria['manager'] = $user;
        } else {
            if (!empty($filter['manager'])) {
                $criteria['manager'] = $filter['manager'];
            }
        }

        if (!empty($filter['customer'])) {
            $criteria['customer'] = $filter['customer'];
        }
        $timetableRows = $this->entityManager->getRepository('AppBundle:TimetableRow')->findBy($criteria);

        foreach ($timetableRows as $timetableRow) {
            $customer = $timetableRow->getCustomer();

            if ($organisation && $customer->getOrganisation() !== $organisation) {
                continue;
            }

            if (!isset($salesData[$customer->getId()])) {
                $salesData[$customer->getId()] = [
                    'manager' => $timetableRow->getManager()->getFullName(),
                    'name' => $customer->getName(),
                    'salary' => 0,
                    'balance' => $this->timetableHelper->contractorBalance($customer),
                    'margin_sum' => 0,
                    'margin_percent' => 0,
                    'counter' => 0,
                ];
            }

            $rowData = $this->timetableHelper->calculateRowData($timetableRow);

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

        return [
            'sales_data' => $salesData,
            'summary_data' => $summaryData,
        ];
    }

    /**
     * @param array        $filter
     * @param Organisation $organisation
     * @return array
     * @throws \Doctrine\ORM\NonUniqueResultException
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function getProvideData(array $filter, Organisation $organisation = null)
    {
        $provideData = [];
        if (!empty($filter['timetable'])) {
            $timetables = $filter['timetable'];
        } else {
            $timetables = $this->entityManager->getRepository('AppBundle:Timetable')->findAll();

            if (empty($filter['provider'])) {
                $providers = $this->entityManager->getRepository('AppBundle:Contractor')->findBy(['type' => Contractor::PROVIDER], ['name' => 'ASC']);
                foreach ($providers as $provider) {
                    if ($organisation && $provider->getOrganisation() !== $organisation) {
                        continue;
                    }

                    $provideData[$provider->getId()] = [
                        'name' => $provider->getName(),
                        'balance' => $this->timetableHelper->contractorBalance($provider),
                        'manager' => '',
                        'salary' => 0,
                    ];
                }
            }
        }

        $criteria = [
            'timetable' => $timetables,
        ];
        if (!empty($filter['provider'])) {
            $criteria['provider'] = $filter['provider'];
        }
        $timetableRows = $this->entityManager->getRepository('AppBundle:TimetableRow')->findBy($criteria);

        foreach ($timetableRows as $timetableRow) {
            $provider = $timetableRow->getProvider();

            if (!$provider) {
                continue;
            }

            if ($organisation && $provider->getOrganisation() !== $organisation) {
                continue;
            }

            if (!isset($provideData[$provider->getId()])) {
                $provideData[$provider->getId()] = [
                    'name' => $provider->getName(),
                    'salary' => 0,
                    'balance' => $this->timetableHelper->contractorBalance($provider),
                ];
            }

            $rowData = $this->timetableHelper->calculateRowData($timetableRow);

            $provideData[$provider->getId()]['salary'] += $rowData['provider_salary'];
        }

        $summaryData = [
            'salary' => array_sum(array_column($provideData, 'salary')),
            'balance' => array_sum(array_column($provideData, 'balance')),
        ];

        return [
            'provide_data' => $provideData,
            'summary_data' => $summaryData,
        ];
    }
}
