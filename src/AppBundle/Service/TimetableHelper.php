<?php

namespace AppBundle\Service;

use AppBundle\Entity\Contractor;
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
     * @param TimetableRow $timetableRow
     * @return array
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function calculateRowData(TimetableRow $timetableRow)
    {
        $timetable = $timetableRow->getTimetable();
        $timetableRowTimesRepository = $this->entityManager->getRepository('AppBundle:TimetableRowTimes');
        $paymentRepository = $this->entityManager->getRepository('AppBundle:Payment');

        $customer = $timetableRow->getCustomer();
        $provider = $timetableRow->getProvider();

        $customerPayments = $paymentRepository->getByContractorAndTimetable($customer, $timetable);
        $customerPaid = 0;
        /** @var Payment $payment */
        foreach ($customerPayments as $payment) {
            $customerPaid += $payment->getAmount();
        }

        $providerPayments = $paymentRepository->getByContractorAndTimetable($customer, $timetable);
        $providerPaid = 0;
        /** @var Payment $payment */
        foreach ($providerPayments as $payment) {
            $providerPaid += $payment->getAmount();
        }

        $timetableRowTimes = $timetableRowTimesRepository->getTimesOrCreate($timetableRow);
        $times = $timetableRowTimes->getTimes();
        $sumTimes = $timetableRowTimesRepository->sumTimes($times);
        $customerSalary = $timetableRow->getPriceForCustomer() * $sumTimes;
        $providerSalary = $timetableRow->getPriceForProvider() * $sumTimes;
        $marginSum = $customerSalary - $providerSalary;
        $customerBalance = $this->contractorBalance($customer, $timetable);

        if ($provider) {
            $providerBalance = $this->contractorBalance($provider, $timetable);
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
     * @param Contractor $contractor
     * @param Timetable  $timetable
     * @return float|int
     */
    public function contractorBalance(Contractor $contractor, Timetable $timetable)
    {
        $timetableRowTimesRepository = $this->entityManager->getRepository('AppBundle:TimetableRowTimes');
        $paymentRepository = $this->entityManager->getRepository('AppBundle:Payment');

        $payments = $paymentRepository->getByContractorAndDate($contractor, clone $timetable->getCreated()->modify('last day of'));
        $paid = 0;
        /** @var Payment $payment */
        foreach ($payments as $payment) {
            $paid += $payment->getAmount();
        }

        $balance = $paid - $timetableRowTimesRepository->calculateContractorBalance($timetable, $contractor);

        return $balance;
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
