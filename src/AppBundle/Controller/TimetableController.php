<?php

namespace AppBundle\Controller;

use AppBundle\Entity\Timetable;
use AppBundle\Entity\TimetableRow;
use AppBundle\Entity\TimetableRowTimes;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class TimetableController extends Controller
{
    /**
     * @param Timetable $timetable
     * @Route("/timetable/{timetable}/data", name="timetable_data")
     * @return JsonResponse
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function getDataAction(Timetable $timetable)
    {
        $em = $this->getDoctrine()->getManager();
        $timetableHelper = $this->get('timetable.helper');

        $criteria = [
            'timetable' => $timetable,
        ];
        if ($this->isGranted('ROLE_CUSTOMER_MANAGER')) {
            $criteria['manager'] = $this->getUser();
        }

        $timetableRows = $em->getRepository('AppBundle:TimetableRow')->findBy($criteria, ['customer' => 'ASC']);

        $show = $timetableHelper->getShowMode();
        $columns = $timetableHelper->getColumnsByShow($show);
        if (!$this->isGranted('ROLE_CUSTOMER_MANAGER')) {
            $managersById = $em->getRepository('AppBundle:User')->getManagersById();
        } else {
            $managersById = [];
        }

        $rows = [];
        /** @var TimetableRow $timetableRow */
        foreach ($timetableRows as $timetableRow) {
            $manager = $timetableRow->getManager();
            $customer = $timetableRow->getCustomer();
            $provider = $timetableRow->getProvider();

            $row = [
                'id' => $timetableRow->getId(),
                'customer_id' => $customer->getId(),
            ];

            if ($provider) {
                $row['provider_id'] = $provider->getId();
            }

            $row['controls'] = [
                'update' => $this->generateUrl('timetable_row_update', ['timetableRow' => $timetableRow->getId()]),
                'delete' => $this->generateUrl('timetable_row_delete', ['timetableRow' => $timetableRow->getId()]),
            ];

            list(
                $timetableRowTimes,
                $sumTimes,
                $customerSalary,
                $providerSalary,
                $customerBalance,
                $providerBalance,
                $marginSum,
                $marginPercent,
                $customerPaid,
                $providerPaid,
                ) = array_values($this->get('timetable.helper')->calculateRowData($timetableRow));

            foreach ($columns as $column) {
                switch ($column) {
                    case 'manager':
                        $value = $managersById[$manager->getId()];
                        break;
                    case 'customer':
                        $value = [
                            'url' => $this->generateUrl('contractor_view', ['contractor' => $customer->getId()]),
                            'name' => $customer->getName(),
                        ];
                        break;
                    case 'provider':
                        if ($provider) {
                            $value = [
                                'url' => $this->generateUrl('contractor_view', ['contractor' => $provider->getId()]),
                                'name' => $provider->getName(),
                            ];
                        } else {
                            $value = null;
                        }
                        break;
                    case 'object':
                        $value = $timetableRow->getObject();
                        break;
                    case 'mechanism':
                        $value = $timetableRow->getMechanism();
                        break;
                    case 'comment':
                        $value = $timetableRow->getComment();
                        break;
                    case 'price_for_customer':
                        $value = number_format($timetableRow->getPriceForCustomer(), 0, '.', ' ');
                        break;
                    case 'price_for_provider':
                        $value = number_format($timetableRow->getPriceForProvider(), 0, '.', ' ');
                        break;
                    case 'sum_times':
                        $value = number_format($sumTimes, 0, '.', ' ');
                        break;
                    case 'times':
                        /** @var TimetableRowTimes $timetableRowTimes */
                        $colors = $timetableRowTimes->getColors();
                        $comments = $timetableRowTimes->getComments();
                        foreach ($timetableRowTimes->getTimes() as $day => $time) {
                            $row['times_'.$day] = [
                                'id' => $timetableRowTimes->getId(),
                                'day' => $day,
                                'comment' => $comments[$day],
                                'comment_url' => $this->generateUrl(
                                    'timetable_row_times_update_comment',
                                    [
                                        'timetableRowTimes' => $timetableRowTimes->getId(),
                                    ]
                                ),
                                'time' => $time,
                            ];
                            $row['_times_'.$day.'_class'] = 'times '.$colors[$day];
                            $row['_times_'.$day.'_data'] = [
                                'id' => $timetableRowTimes->getId(),
                                'day' => $day,
                            ];
                        }
                        $value = $timetableRowTimes;
                        break;
                    case 'customer_salary':
                        $value = number_format($customerSalary, 0, '.', ' ');
                        break;
                    case 'provider_salary':
                        $value = number_format($providerSalary, 0, '.', ' ');
                        break;
                    case 'customer_paid':
                        $value = number_format($customerPaid, 0, '.', ' ');
                        break;
                    case 'provider_paid':
                        $value = number_format($providerPaid, 0, '.', ' ');
                        break;
                    case 'customer_balance':
                        $value = number_format($customerBalance, 0, '.', ' ');

                        if ($customerBalance < 0) {
                            $row['_customer_balance_class'] = 'customer_balance bg-red text-white';
                        }
                        break;
                    case 'provider_balance':
                        $value = number_format($providerBalance, 0, '.', ' ');

                        if ($providerBalance < 0) {
                            $row['_provider_balance_class'] = 'provider_balance bg-pink';
                        }
                        break;
                    case 'margin_sum':
                        $value = number_format($marginSum, 0, '.', ' ');
                        break;
                    case 'margin_percent':
                        $value = number_format($marginPercent, 2, '.', ' ');
                        break;
                    default:
                        $value = '';
                }

                $row[$column] = $value;
            }

            $rows[] = $row;
        }

        return new JsonResponse($rows);
    }

    /**
     * @Route("/timetable/{timetable}/export", name="timetable_export")
     * @param Timetable $timetable
     * @return Response
     * @throws \Doctrine\ORM\OptimisticLockException
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     */
    public function exportAction(Timetable $timetable)
    {
        $em = $this->getDoctrine()->getManager();
        $timetableHelper = $this->get('timetable.helper');

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        $tableData = [];

        $show = $timetableHelper->getShowMode();
        $columns = $timetableHelper->getColumnsByShow($show);

        $row = [];
        foreach ($columns as $index => $column) {
            switch ($column) {
                case 'manager':
                    $row[] = 'Менеджер';
                    break;
                case 'customer':
                    $row[] = 'Заказчик';
                    break;
                case 'provider':
                    $row[] = 'Поставщик';
                    break;
                case 'object':
                    $row[] = 'Объект';
                    break;
                case 'mechanism':
                    $row[] = 'Механизм';
                    break;
                case 'comment':
                    $row[] = 'Комментарий';
                    break;
                case 'price_for_customer':
                    $row[] = 'Цена заказчику';
                    break;
                case 'price_for_provider':
                    $row[] = 'Цена поставщика';
                    break;
                case 'sum_times':
                    $row[] = 'Сумма часов';
                    break;
                case 'times':
                    for ($i = 1; $i <= 31; $i++) {
                        $sheet->getColumnDimensionByColumn($i + $index)->setWidth(3);
                        $row[] = $i;
                    }
                    break;
                case 'customer_salary':
                    $row[] = 'Наработка заказчика';
                    break;
                case 'provider_salary':
                    $row[] = 'Наработка поставщика';
                    break;
                case 'customer_paid':
                    $row[] = 'Оплачено заказчиком';
                    break;
                case 'customer_balance':
                    $row[] = 'Баланс заказчика';
                    break;
                case 'provider_paid':
                    $row[] = 'Оплачено поставщику';
                    break;
                case 'provider_balance':
                    $row[] = 'Баланс поставщика';
                    break;
                case 'margin_sum':
                    $row[] = 'Маржа, сумма';
                    break;
                case 'margin_percent':
                    $row[] = 'Маржа, %';
                    break;
            }
        }
        $tableData[] = $row;
        unset($row);

        $criteria = [
            'timetable' => $timetable,
        ];
        if ($this->isGranted('ROLE_CUSTOMER_MANAGER')) {
            $criteria['manager'] = $this->getUser();
        }
        $timetableRows = $em->getRepository('AppBundle:TimetableRow')->findBy($criteria, ['customer' => 'ASC']);

        if (!$this->isGranted('ROLE_CUSTOMER_MANAGER')) {
            $managersById = $em->getRepository('AppBundle:User')->getManagersById();
        } else {
            $managersById = [];
        }

        $rowIndex = 2;
        /** @var TimetableRow $timetableRow */
        foreach ($timetableRows as $timetableRow) {
            $manager = $timetableRow->getManager();
            $customer = $timetableRow->getCustomer();
            $provider = $timetableRow->getProvider();

            list(
                $timetableRowTimes,
                $sumTimes,
                $customerSalary,
                $providerSalary,
                $customerBalance,
                $providerBalance,
                $marginSum,
                $marginPercent,
                $customerPaid,
                $providerPaid,
                ) = array_values($timetableHelper->calculateRowData($timetableRow));

            $row = [];
            foreach ($columns as $index => $column) {
                switch ($column) {
                    case 'manager':
                        $value = $managersById[$manager->getId()];
                        break;
                    case 'customer':
                        $value = $customer->getName();
                        break;
                    case 'provider':
                        if ($provider) {
                            $value = $provider->getName();
                        } else {
                            $value = null;
                        }

                        $sheet
                            ->getStyleByColumnAndRow($index, $rowIndex)
                            ->getFill()
                            ->setFillType(Fill::FILL_SOLID)
                            ->getStartColor()->setRGB('EEEEEE')
                        ;
                        break;
                    case 'object':
                        $value = $timetableRow->getObject();
                        break;
                    case 'mechanism':
                        $value = $timetableRow->getMechanism();
                        break;
                    case 'comment':
                        $value = $timetableRow->getComment();
                        break;
                    case 'price_for_customer':
                        $value = $timetableRow->getPriceForCustomer();
                        break;
                    case 'price_for_provider':
                        $value = $timetableRow->getPriceForProvider();

                        $sheet
                            ->getStyleByColumnAndRow($index, $rowIndex)
                            ->getFill()
                            ->setFillType(Fill::FILL_SOLID)
                            ->getStartColor()->setRGB('EEEEEE')
                        ;
                        break;
                    case 'sum_times':
                        $value = $sumTimes;
                        break;
                    case 'times':
                        $value = '';
                        /** @var TimetableRowTimes $timetableRowTimes */
                        $colors = $timetableRowTimes->getColors();
                        foreach ($timetableRowTimes->getTimes() as $i => $time) {
                            $row[$column.'_'.$i] = $time;

                            switch ($colors[$i]) {
                                case 'yellow':
                                    $color = 'FFFF00';
                                    break;
                                case 'green':
                                    $color = '008000';
                                    break;
                                case 'blue':
                                    $color = 'ADD8E6';
                                    break;
                                case 'purple':
                                    $color = 'cb00cb';
                                    break;
                                default:
                                    $color = null;
                            }

                            if ($color) {
                                $sheet
                                    ->getStyleByColumnAndRow($index + $i, $rowIndex)
                                    ->getFill()
                                    ->setFillType(Fill::FILL_SOLID)
                                    ->getStartColor()->setRGB($color)
                                ;
                            }
                        }
                        break;
                    case 'customer_salary':
                        $value = $customerSalary;
                        break;
                    case 'provider_salary':
                        $value = $providerSalary;

                        $sheet
                            ->getStyleByColumnAndRow($index + 31, $rowIndex)
                            ->getFill()
                            ->setFillType(Fill::FILL_SOLID)
                            ->getStartColor()->setRGB('EEEEEE')
                        ;
                        break;
                    case 'customer_paid':
                        $value = $customerPaid;
                        break;
                    case 'provider_paid':
                        $value = $providerPaid;

                        $sheet
                            ->getStyleByColumnAndRow($index + 31, $rowIndex)
                            ->getFill()
                            ->setFillType(Fill::FILL_SOLID)
                            ->getStartColor()->setRGB('EEEEEE')
                        ;
                        break;
                    case 'customer_balance':
                        $value = (string) $customerBalance;

                        if ($customerBalance < 0) {
                            $sheet
                                ->getStyleByColumnAndRow($index + 31, $rowIndex)
                                ->getFill()
                                ->setFillType(Fill::FILL_SOLID)
                                ->getStartColor()->setRGB('ff0000')
                            ;
                        }
                        break;
                    case 'provider_balance':
                        $value = (string) $providerBalance;

                        if ($providerBalance < 0) {
                            $sheet
                                ->getStyleByColumnAndRow($index + 31, $rowIndex)
                                ->getFill()
                                ->setFillType(Fill::FILL_SOLID)
                                ->getStartColor()->setRGB('FFC0CB')
                            ;
                        } else {
                            $sheet
                                ->getStyleByColumnAndRow($index + 31, $rowIndex)
                                ->getFill()
                                ->setFillType(Fill::FILL_SOLID)
                                ->getStartColor()->setRGB('EEEEEE')
                            ;
                        }
                        break;
                    case 'margin_sum':
                        $value = (string) $marginSum;
                        break;
                    case 'margin_percent':
                        $value = (string) $marginPercent;
                        break;
                    default:
                        $value = '';
                }

                if ($column !== 'times') {
                    $row[$column] = $value;
                }
            }

            $tableData[$rowIndex++] = $row;
        }

        $sheet->fromArray($tableData);

        header('Content-type: application/vnd.ms-excel');
        header('Content-Disposition: attachment; filename="file.xls"');
        $writer = new Xlsx($spreadsheet);
        $writer->save('php://output');

        return new Response();
    }
}
