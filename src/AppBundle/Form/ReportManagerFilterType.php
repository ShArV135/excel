<?php

namespace AppBundle\Form;

use AppBundle\Entity\Timetable;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ReportManagerFilterType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add(
                'timetable',
                EntityType::class,
                [
                    'class' => Timetable::class,
                    'choice_label' => 'name',
                    'label' => 'Табель',
                    'query_builder' => function(EntityRepository $repository) {
                        return $repository->createQueryBuilder('e')->orderBy('e.id', 'DESC');
                    },
                ]
            )
        ;

        if ($options['list_mode']) {
            $builder
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
    }

    public function getBlockPrefix()
    {
        return '';
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'list_mode' => true,
            'method' => 'GET',
            'csrf_protection' => false,
        ]);
    }
}