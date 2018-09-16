<?php

namespace AppBundle\Controller;

use AppBundle\Entity\Contractor;
use AppBundle\Entity\Timetable;
use AppBundle\Entity\User;
use AppBundle\Form\ReportManagerFilterType;
use AppBundle\Security\UserVoter;
use Doctrine\ORM\EntityNotFoundException;
use Doctrine\ORM\EntityRepository;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
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
    public function managerAction(Request $request)
    {
        $em = $this->getDoctrine()->getManager();
        $timetableFilter = $this->createForm(ReportManagerFilterType::class);
        $timetableFilter->handleRequest($request);

        $report = $reportByOrganisations = $timetable = null;
        if ($timetableFilter->isValid()) {
            $reportHelper = $this->get('report.helper');

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
     * @param Request $request
     * @param User    $user
     * @return Response
     * @throws EntityNotFoundException
     * @throws \Doctrine\ORM\NonUniqueResultException
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     * @Route("/report-manager/{user}", name="report_manager_detail")
     */
    public function managerDetailAction(Request $request, User $user)
    {
        $this->denyAccessUnlessGranted(UserVoter::VIEW, $user);

        $timetableFilter = $this->createForm(ReportManagerFilterType::class, null, ['list_mode' => false]);
        $timetableFilter->handleRequest($request);

        $report = $salesData = null;
        if ($timetableFilter->isValid()) {
            $reportHelper = $this->get('report.helper');

            /** @var Timetable $timetable */
            $timetable = $timetableFilter->get('timetable')->getData();

            if (!$timetable) {
                throw new EntityNotFoundException('Табель не найден');
            }

            $report = $reportHelper->getManagerData($timetable, null, $user);

            if (in_array('ROLE_CUSTOMER_MANAGER', $user->getRoles())) {
                $salesData = $reportHelper->getSalesData([
                    'manager' => $user,
                    'timetable' => $timetable,
                ]);
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
     * @param Request $request
     * @return Response
     * @throws \Doctrine\ORM\NonUniqueResultException
     * @throws \Doctrine\ORM\ORMException
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
                    'choices' => $em->getRepository('AppBundle:Timetable')->findBy([], ['id' => 'DESC']),
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
                        $qb = $repository
                            ->createQueryBuilder('e')
                            ->addOrderBy('e.name', 'ASC')
                        ;

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
            ->add(
                'by_organisations',
                CheckboxType::class,
                [
                    'label' => 'Группировать по организациям',
                    'required' => false,
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

        $report = $reportByOrganisations = null;
        if ($timetableFilter->isValid()) {
            $reportHelper = $this->get('report.helper');
            $byOrganisations = $timetableFilter->get('by_organisations')->getData();

            $filterData = $timetableFilter->getData();

            if ($byOrganisations) {
                $organisations = $em->getRepository('AppBundle:Organisation')->findBy([], ['name' => 'ASC']);
                $reportByOrganisations = [];
                foreach ($organisations as $organisation) {
                    $reportByOrganisations[] = [
                        'organisation' => $organisation,
                        'report' => $reportHelper->getSalesData($filterData, $organisation),
                    ];
                }
            } else {
                $report = $reportHelper->getSalesData($filterData);
            }
        }

        return $this->render(
            '@App/report/sale.html.twig',
            [
                'timetable_filter' => $timetableFilter->createView(),
                'report' => $report,
                'report_by_organisations' => $reportByOrganisations,
            ]
        );
    }

    /**
     * @Route("/report-provide", name="report_provide")
     * @param Request $request
     * @return Response
     * @throws \Doctrine\ORM\NonUniqueResultException
     * @throws \Doctrine\ORM\ORMException
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
                    'choices' => $em->getRepository('AppBundle:Timetable')->findBy([], ['id' => 'DESC']),
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
                        $qb = $repository
                            ->createQueryBuilder('e')
                            ->addOrderBy('e.name', 'ASC')
                        ;

                        $qb
                            ->where($qb->expr()->eq('e.type', ':type'))
                            ->setParameter('type', Contractor::PROVIDER)
                        ;

                        return $qb;
                    },
                ]
            )
            ->add(
                'by_organisations',
                CheckboxType::class,
                [
                    'label' => 'Группировать по организациям',
                    'required' => false,
                ]
            )
            ->getForm()
        ;
        $timetableFilter->handleRequest($request);

        $report = $reportByOrganisations = null;
        if ($timetableFilter->isValid()) {
            $reportHelper = $this->get('report.helper');
            $byOrganisations = $timetableFilter->get('by_organisations')->getData();
            $filterData = $timetableFilter->getData();

            if ($byOrganisations) {
                $organisations = $em->getRepository('AppBundle:Organisation')->findBy([], ['name' => 'ASC']);
                $reportByOrganisations = [];
                foreach ($organisations as $organisation) {
                    $reportByOrganisations[] = [
                        'organisation' => $organisation,
                        'report' => $reportHelper->getProvideData($filterData, $organisation),
                    ];
                }
            } else {
                $report = $reportHelper->getProvideData($filterData);
            }
        }

        return $this->render(
            '@App/report/provide.html.twig',
            [
                'timetable_filter' => $timetableFilter->createView(),
                'report' => $report,
                'report_by_organisations' => $reportByOrganisations,
            ]
        );

    }
}
