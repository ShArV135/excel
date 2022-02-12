<?php

namespace AppBundle\Controller;

use AppBundle\Entity\Timetable;
use AppBundle\Entity\User;
use AppBundle\Form\ReportManagerFilterType;
use AppBundle\Form\ReportProvideFilterType;
use AppBundle\Form\ReportSaleFilterType;
use AppBundle\Security\UserVoter;
use AppBundle\Service\Report\ProvideService;
use AppBundle\Service\Report\ReportConfig;
use AppBundle\Service\Report\ReportSaleService;
use AppBundle\Service\Report\SaleConfig;
use AppBundle\Service\Report\SaleExportConfig;
use AppBundle\Service\ReportHelper;
use AppBundle\Service\Utils;
use Doctrine\ORM\EntityNotFoundException;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class ReportController extends Controller
{
    /**
     * @param Request $request
     * @return Response
     * @throws EntityNotFoundException
     * @throws \Doctrine\ORM\ORMException
     * @Route("/report-manager", name="report_manager")
     */
    public function managerAction(Request $request, ReportHelper $reportHelper)
    {
        if (!$this->isGranted('ROLE_MANAGER')) {
            return $this->redirectToRoute('report_manager_detail', ['user' => $this->getUser()->getId()]);
        }

        $em = $this->getDoctrine()->getManager();
        $timetableFilter = $this->createForm(ReportManagerFilterType::class);
        $timetableFilter->handleRequest($request);

        $report = $reportByOrganisations = $timetable = null;
        if ($timetableFilter->isValid()) {

            /** @var Timetable $timetable */
            $timetable = $timetableFilter->get('timetable')->getData();
            $byOrganisations = $timetableFilter->get('by_organisations')->getData();

            if (!$timetable) {
                throw new EntityNotFoundException('Табель не найден');
            }

            if ($byOrganisations) {
                $organisations = $em->getRepository('AppBundle:Organisation')->findBy([], ['name' => 'ASC']);
                $reportByOrganisations = [];
                foreach ($organisations as $organisation) {
                    $reportByOrganisations[] = [
                        'organisation' => $organisation,
                        'report' => $reportHelper->getManagerData($timetable, $organisation),
                    ];
                }
            } else {
                $report = $reportHelper->getManagerData($timetable);
            }
        }

        return $this->render(
            '@App/report/manager.html.twig',
            [
                'timetable_filter' => $timetableFilter->createView(),
                'report' => $report,
                'report_by_organisations' => $reportByOrganisations,
            ]
        );
    }

    /**
     * @param Request           $request
     * @param User              $user
     * @param ReportSaleService $reportSaleService
     * @return Response
     * @throws EntityNotFoundException
     * @throws \Doctrine\ORM\NonUniqueResultException
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     * @Route("/report-manager/{user}", name="report_manager_detail")
     */
    public function managerDetailAction(Request $request, User $user, ReportSaleService $reportSaleService, ReportHelper $reportHelper): Response
    {
        $this->denyAccessUnlessGranted(UserVoter::VIEW_REPORT, $user);

        $timetableFilter = $this->createForm(ReportManagerFilterType::class, null, ['list_mode' => false]);
        $timetableFilter->handleRequest($request);

        $report = $salesData = null;
        if ($timetableFilter->isValid()) {

            /** @var Timetable $timetable */
            $timetable = $timetableFilter->get('timetable')->getData();

            if (!$timetable) {
                throw new EntityNotFoundException('Табель не найден');
            }

            $report = $reportHelper->getManagerData($timetable, null, $user);

            if (Utils::isCustomerManager($user)) {
                $config = new SaleConfig();
                $config->setTimetableFrom($timetable);
                $config->setTimetableTo($timetable);
                $config->setManager($user);
                $salesData = $reportSaleService->getReport($config);
            }
        }

        return $this->render(
            '@App/report/manager_detail.html.twig',
            [
                'timetable_filter' => $timetableFilter->createView(),
                'report' => $report,
                'user' => $user,
                'sales_data' => $salesData,
            ]
        );
    }

    /**
     * @Route("/report-sale", name="report_sale")
     * @param Request           $request
     * @param ReportSaleService $reportSaleService
     * @return Response
     */
    public function saleAction(Request $request, ReportSaleService $reportSaleService): Response
    {
        $timetableFilter = $this->createForm(
            ReportSaleFilterType::class,
            null,
            [
                'manager' => $this->isGranted('ROLE_CUSTOMER_MANAGER') ? $this->getUser() : null,
            ]
        );
        $timetableFilter->handleRequest($request);

        $reports = [];
        if ($timetableFilter->isValid()) {
            $config = SaleConfig::fromArray($timetableFilter->getData());

            if ($this->isGranted('ROLE_CUSTOMER_MANAGER')) {
                $config->setManager($this->getUser());
            }

            $reports = $reportSaleService->getReports($config);

            if ($request->get('_format') === 'xls') {
                $saleExportConfig = SaleExportConfig::fromRequest($request);

                if ($this->isGranted('ROLE_CUSTOMER_MANAGER')) {
                    $saleExportConfig->setMode(SaleExportConfig::MODE_MANAGER);
                }

                if ($this->isGranted('ROLE_GENERAL_MANAGER')) {
                    $saleExportConfig->setMode(SaleExportConfig::MODE_GENERAL_MANAGER);
                }

                $exportService = $reportSaleService->getExportService();
                $exportService->setConfig($saleExportConfig);
                $exportService->export($reports);

                return new Response();
            }
        }

        return $this->render(
            '@App/report/sale.html.twig',
            [
                'timetable_filter' => $timetableFilter->createView(),
                'reports' => $reports,
            ]
        );
    }

    /**
     * @Route("/report-provide", name="report_provide")
     * @param Request        $request
     * @param ProvideService $provideService
     * @return Response
     */
    public function provideAction(Request $request, ProvideService $provideService): Response
    {
        $timetableFilter = $this->createForm(ReportProvideFilterType::class);
        $timetableFilter->handleRequest($request);

        $reports = [];
        if ($timetableFilter->isValid()) {
            $config = ReportConfig::fromArray($timetableFilter->getData());

            $reports = $provideService->getReports($config);

            if ($request->get('_format') === 'xls') {
                $provideService->export($reports);
                return new Response();
            }
        }

        return $this->render(
            '@App/report/provide.html.twig',
            [
                'timetable_filter' => $timetableFilter->createView(),
                'reports' => $reports,
            ]
        );

    }
}
