<?php

namespace AppBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class PaymentType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('amount', MoneyType::class, ['label' => 'Сумма', 'currency' => null])
            ->add('date', DateType::class, ['label' => 'Дата'])
            ->add(
                'comment',
                TextareaType::class,
                [
                    'label' => 'Комментарий',
                    'required' => false,
                    'attr' => [
                        'class' => 'noresize',
                        'rows' => 5,
                    ],
                ]
            )
            ->add('save', SubmitType::class, ['label' => 'Сохранить', 'attr' => ['class' => 'btn-primary']])
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([]);
    }
}
