<?php

namespace AppBundle\Form;

use AppBundle\Entity\Contractor;
use AppBundle\Entity\Timetable;
use AppBundle\Entity\User;
use AppBundle\Repository\UserRepository;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ReportSaleFilterType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add(
                'timetable_from',
                EntityType::class,
                [
                    'class' => Timetable::class,
                    'choice_label' => 'name',
                    'label' => 'Табель от',
                    'query_builder' => function(EntityRepository $repository) {
                        return $repository->createQueryBuilder('e')->orderBy('e.id', 'DESC');
                    },
                ]
            )
            ->add(
                'timetable_to',
                EntityType::class,
                [
                    'class' => Timetable::class,
                    'choice_label' => 'name',
                    'label' => 'Табель до',
                    'query_builder' => function(EntityRepository $repository) {
                        return $repository->createQueryBuilder('e')->orderBy('e.id', 'DESC');
                    },
                ]
            )
            ->add(
                'contractor',
                EntityType::class,
                [
                    'required' => false,
                    'attr' => ['class' => 'select2me'],
                    'class' => Contractor::class,
                    'choice_label' => 'name',
                    'label' => 'Заказчик',
                    'query_builder' => function(EntityRepository $repository) use ($options) {
                        $qb = $repository
                            ->createQueryBuilder('e')
                            ->addOrderBy('e.name', 'ASC')
                        ;

                        $qb
                            ->where($qb->expr()->eq('e.type', ':type'))
                            ->setParameter('type', Contractor::CUSTOMER)
                        ;

                        if ($options['manager']) {
                            $qb
                                ->andWhere($qb->expr()->eq('e.manager', ':manager'))
                                ->setParameter('manager', $options['manager'])
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
        ;

        if (!$options['manager']) {
            $builder
                ->add(
                    'manager',
                    EntityType::class,
                    [
                        'required' => false,
                        'attr' => ['class' => 'select2me'],
                        'class' => User::class,
                        'choice_label' => 'fullname',
                        'label' => 'Менеджер',
                        'query_builder' => function(UserRepository $repository) {
                            return $repository->getManagerQueryBuilder();
                        },
                    ]
                )
            ;
        }
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'method' => 'GET',
            'csrf_protection' => false,
            'manager' => null,
        ]);
    }
}
