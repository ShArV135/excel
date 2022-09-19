<?php

namespace AppBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;

class ObjectType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('address', TextType::class, ['label' => '* Поле 1'])
            ->add(
                'description',
                TextareaType::class,
                [
                    'label' => '* Поле 2',
                    
                    'attr' => [
                        'class' => 'noresize',
                        'rows' => 5,
                    ],
                ]
            )
            ->add(
                'workDescription',
                TextareaType::class,
                [
                    'label' => '* Поле 3',
                    
                    'attr' => [
                        'class' => 'noresize',
                        'rows' => 5,
                    ],
                ]
            )
            ->add('save', SubmitType::class, ['label' => 'Сохранить', 'attr' => ['class' => 'btn-primary']])
        ;
    }
}