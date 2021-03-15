<?php

namespace AppBundle\Form;

use AppBundle\Entity\Contractor;
use AppBundle\Entity\Timetable;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ReportProvideFilterType extends AbstractType
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
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'method' => 'GET',
            'csrf_protection' => false,
        ]);
    }
}
