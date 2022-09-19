<?php

namespace AppBundle\Form;

use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class PlanType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add(
                'user',
                EntityType::class,
                [
                    'attr' => ['class' => 'select2me'],
                    'class' => 'AppBundle\Entity\User',
                    'choice_label' => 'fullname',
                    'label' => 'Менеджер',
                    'choices' => $options['users'],
                ]
            )
            ->add('amount', MoneyType::class, ['label' => 'План', 'currency' => null])
            ->add('save', SubmitType::class, ['label' => 'Сохранить', 'attr' => ['class' => 'btn-primary']])
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'users' => [],
        ]);
    }
}