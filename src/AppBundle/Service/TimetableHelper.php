<?php

namespace AppBundle\Service;

use AppBundle\Entity\Payment;
use AppBundle\Entity\Timetable;
use AppBundle\Entity\TimetableRow;
use Doctrine\ORM\EntityManager;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Intl\Exception\NotImplementedException;
use Symfony\Component\Security\Core\Authorization\AuthorizationChecker;

class TimetableHelper
{
    private $entityManager;
    private $authorizationChecker;
    private $request;

    /**
     * TimetableHelper constructor.
     * @param EntityManager        $entityManager
     * @param AuthorizationChecker $authorizationChecker
     * @param RequestStack         $requestStack
     */
    public function __construct(EntityManager $entityManager, AuthorizationChecker $authorizationChecker, RequestStack $requestStack)
    {
        $this->entityManager = $entityManager;
        $this->authorizationChecker = $authorizationChecker;
        $this->request = $requestStack->getCurrentRequest();
    }

    /**
     * @param Timetable    $timetable
     * @param TimetableRow $timetableRow
     * @return array
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function calculateRowData(Timetable $timetable, TimetableRow $timetableRow)
    {
        $timetableRowTimesRepository = $this->entityManager->getRepository('AppBundle:TimetableRowTimes');

        $customer = $timetableRow->getCustomer();
        $provider = $timetableRow->getProvider();

        $qb = $this
            ->entityManager
            ->getRepository('AppBundle:Payment')
            ->createQueryBuilder('payment')
        ;
        $customerPayments = $qb
            ->andWhere($qb->expr()->lte('payment.date', ':date'))
            ->andWhere($qb->expr()->eq('payment.contractor', ':contractor'))
            ->setParameters([
                'date' => clone $timetable->getCreated()->modify('last day of'),
                'contractor' => $customer
            ])
            ->getQuery()
            ->getResult()
        ;
        $customerPaid = 0;
        /** @var Payment $payment */
        foreach ($customerPayments as $payment) {
            $customerPaid += $payment->getAmount();
        }

        $qb = $this
            ->entityManager
            ->getRepository('AppBundle:Payment')
            ->createQueryBuilder('payment')
        ;
        $providerPayments = $qb
            ->andWhere($qb->expr()->lte('payment.date', ':date'))
            ->andWhere($qb->expr()->eq('payment.contractor', ':contractor'))
            ->setParameters([
                'date' => clone $timetable->getCreated()->modify('last day of'),
                'contractor' => $provider
            ])
            ->getQuery()
            ->getResult()
        ;

        $providerPaid = 0;
        /** @var Payment $payment */
        foreach ($providerPayments as $payment) {
            $providerPaid += $payment->getAmount();
        }

        $timetableRowTimes = $timetableRowTimesRepository->getTimesOrCreate($timetable, $timetableRow);
        $times = $timetableRowTimes->getTimes();
        $sumTimes = $timetableRowTimesRepository->sumTimes($times);
        $customerSalary = $timetableRow->getPriceForCustomer() * $sumTimes;
        $providerSalary = $timetableRow->getPriceForProvider() * $sumTimes;
        $marginSum = $customerSalary - $providerSalary;
        $customerBalance = $customerPaid - $timetableRowTimesRepository->calculateContractorBalance($timetable, $customer);

        if ($provider) {
            $providerBalance = $providerPaid - $timetableRowTimesRepository->calculateContractorBalance($timetable, $provider);
        } else {
            $providerBalance = 0;
        }

        if ($providerSalary) {
            $marginPercent = round(100-($providerSalary/$customerSalary*100), 2);
        } else {
            $marginPercent = 0;
        }

        return [
            'timetable_row_times' => $timetableRowTimes,
            'sum_times' => $sumTimes,
            'customer_salary' => $customerSalary,
            'provider_salary' => $providerSalary,
            'customer_balance' => $customerBalance,
            'provider_balance' => $providerBalance,
            'margin_sum' => $marginSum,
            'margin_percent' => $marginPercent,
            'customer_paid' => $customerPaid,
            'provider_paid' => $providerPaid,
            'customer_id' => $customer->getId(),
            'provider_id' => $provider ? $provider->getId() : null,
        ];
    }

    /**
     * @return mixed|string
     */
    public function getShowMode()
    {
        switch (true) {
            case $this->authorizationChecker->isGranted('ROLE_CUSTOMER_MANAGER'):
                $show = 'customer_manager';
                break;
            case $this->authorizationChecker->isGranted('ROLE_DISPATCHER'):
                $show = 'dispatcher';
                break;
            case $this->authorizationChecker->isGranted('ROLE_PROVIDER_MANAGER'):
                $show = 'provider_manager';
                break;
            case $this->authorizationChecker->isGranted('ROLE_GENERAL_MANAGER'):
                $show = $this->request->get('show', 'general_manager');
                break;
            default:
                throw new NotImplementedException('Not implemented.');
                break;
        }

        return $show;
    }

    /**
     * @param $show
     * @return array
     */
    public function getColumnsByShow($show)
    {
        switch ($show) {
            case 'customer_manager':
                $columns = [
                    'manager',
                    'customer',
                    'object',
                    'mechanism',
                    'comment',
                    'price_for_customer',
                    'sum_times',
                    'times',
                    'customer_salary',
                    'customer_paid',
                    'customer_balance',
                ];
                break;
            case 'dispatcher':
                $columns = [
                    'manager',
                    'customer',
                    'provider',
                    'object',
                    'mechanism',
                    'comment',
                    'price_for_customer',
                    'sum_times',
                    'times',
                    'customer_salary',
                ];
                break;
            case 'provider_manager':
                $columns = [
                    'manager',
                    'object',
                    'provider',
                    'mechanism',
                    'customer',
                    'comment',
                    'price_for_provider',
                    'sum_times',
                    'times',
                    'provider_salary',
                    'provider_paid',
                    'provider_balance',
                    'customer_balance',
                ];
                break;
            case 'general_manager':
                $columns = [
                    'manager',
                    'customer',
                    'object',
                    'mechanism',
                    'comment',
                    'price_for_customer',
                    'sum_times',
                    'times',
                    'customer_salary',
                    'customer_paid',
                    'customer_balance',
                    'margin_sum',
                    'margin_percent',
                ];
                break;
            default:
                $columns = [
                    'manager',
                    'customer',
                    'provider',
                    'object',
                    'mechanism',
                    'comment',
                    'price_for_customer',
                    'price_for_provider',
                    'sum_times',
                    'times',
                    'customer_salary',
                    'provider_salary',
                    'customer_paid',
                    'customer_balance',
                    'provider_paid',
                    'provider_balance',
                    'margin_sum',
                    'margin_percent',
                ];
                break;
        }

        return $columns;
    }
}
