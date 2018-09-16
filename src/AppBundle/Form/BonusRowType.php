<?php

namespace AppBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints as Assert;

class BonusRowType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add(
                'type',
                ChoiceType::class,
                [
                    'label' => 'Тип рассчета',
                    'choices' => [
                        'От наработки' => 'FROM_SALARY',
                        'От маржи' => 'FROM_MARGIN',
                    ],
                    'constraints' => [
                        new Assert\NotBlank(),
                    ],
                ]
            )
            ->add(
                'value',
                NumberType::class,
                [
                    'label' => 'Значение %',
                    'constraints' => [
                        new Assert\NotBlank(),
                        new Assert\Type(['type' => 'float', 'message' => 'Значение должно быть вещественным числом.']),
                    ],
                ]
            )
        ;
    }
}
