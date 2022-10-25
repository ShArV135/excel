<?php

namespace AppBundle\Controller;

use AppBundle\Entity\Timetable;
use AppBundle\Entity\TimetableRow;
use AppBundle\Entity\TimetableRowTimes;
use AppBundle\Entity\User;
use AppBundle\Service\Timetable\RowTimeStorage;
use AppBundle\Service\TimetableHelper;
use AppBundle\Service\Utils;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use PhpOffice\PhpSpreadsheet\Exception;
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
     * @return JsonResponse
     * @throws NonUniqueResultException
     * @throws ORMException
     * @throws OptimisticLockException
     * @Route("/timetable/{timetable}/data", name="timetable_data")
     */
    public function getDataAction(Timetable $timetable, TimetableHelper $timetableHelper, RowTimeStorage $timeStorage): Response
    {
        $timeStorage->init($timetable);
        $em = $this->getDoctrine()->getManager();

        $criteria = [
            'timetable' => $timetable,
        ];
        if ($this->isGranted('ROLE_CUSTOMER_MANAGER')) {
            $criteria['manager'] = $this->getUser();
        }

        $timetableRows = $em->getRepository('AppBundle:TimetableRow')->findBy($criteria, ['customer' => 'ASC', 'object' => 'ASC']);

        $show = $timetableHelper->getShowMode();
        $columns = $timetableHelper->getColumnsByShow($show);

        $rows = [];
        /** @var TimetableRow $timetableRow */
        foreach ($timetableRows as $timetableRow) {
            $rows[] = $timetableHelper->timetableRowFormat($timetableRow, $columns);
        }

        return new JsonResponse($rows);
    }

    /**
     * @Route("/timetable/{timetable}/export", name="timetable_export")
     * @throws NonUniqueResultException
     * @throws ORMException
     * @throws OptimisticLockException
     * @throws Exception
     * @throws \PhpOffice\PhpSpreadsheet\Writer\Exception
     */
    public function exportAction(Timetable $timetable, TimetableHelper $timetableHelper, RowTimeStorage $timeStorage): Response
    {
        $em = $this->getDoctrine()->getManager();
        $timeStorage->init($timetable);

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        $tableData = [];

        $show = $timetableHelper->getShowMode();
        $columns = $timetableHelper->getColumnsByShow($show);

        $row = [];
        foreach ($columns as $index => $column) {
            switch ($column) {
                case 'year':
                    $row[] = 'Год';
                    break;
                case 'month':
                    $row[] = 'Месяц';
                    break;
                case 'manager':
                    $row[] = 'Менеджер';
                    break;
                case 'provider_manager':
                    $row[] = 'МС';
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
                case 'customer_balance':
                    $row[] = 'Баланс заказчика';
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
                default:
                    $row[] = $column;
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
        $timetableRows = $em->getRepository('AppBundle:TimetableRow')->findBy($criteria, ['customer' => 'ASC', 'object' => 'ASC']);

        if (in_array('manager', $columns, true)) {
            $managersById = $em->getRepository(User::class)->getManagersById();
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
                ) = array_values($timetableHelper->calculateRowData($timetableRow));

            $row = [];
            foreach ($columns as $index => $column) {
                switch ($column) {
                    case 'year':
                        $value = $timetableRow->getTimetable()->getCreated()->format('Y');
                        break;
                    case 'month':
                        $value = Utils::getMonth($timetableRow->getTimetable()->getCreated());
                        break;
                    case 'manager':
                        $value = $manager ? $managersById[$manager->getId()] : '';
                        break;
                    case 'customer':
                        $value = $customer ? $customer->getName() : '';
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

    /**
     * @param TimetableHelper $timetableHelper
     * @param Timetable $timetable
     * @param RowTimeStorage $timeStorage
     * @param           $time
     * @return JsonResponse
     * @throws NonUniqueResultException
     * @throws ORMException
     * @throws OptimisticLockException
     * @Route("/timetable/{timetable}/check-update/{time}", name="timetable_check_update", options={"expose"=true}, defaults={"time": 0})
     */
    public function checkUpdateAction(TimetableHelper $timetableHelper, Timetable $timetable, RowTimeStorage $timeStorage, $time): Response
    {
        $timeStorage->init($timetable);
        $show = $timetableHelper->getShowMode();
        $columns = $timetableHelper->getColumnsByShow($show);

        $data = [];
        $newTimestamp = $time;

        /** @var TimetableRow $row */
        foreach ($timetable->getRows() as $row) {
            if ($updated = $row->getUpdated()) {
                $rowTimestamp = $updated->getTimestamp();
                if ($rowTimestamp > $time) {
                    $data[] = $timetableHelper->timetableRowFormat($row, $columns);
                    $newTimestamp = max($rowTimestamp, $newTimestamp);
                }
            }
        }

        return new JsonResponse([
            'timestamp' => $newTimestamp,
            'data' => $data,
        ]);
    }
}
