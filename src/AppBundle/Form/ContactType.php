<?php

namespace AppBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;

class ContactType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('fio', TextType::class, ['label' => 'ФИО'])
            ->add('post', TextType::class, ['label' => 'Должность', 'required' => false])
            ->add(
                'birthDate',
                DateType::class,
                [
                    'label' => 'Дата рождения',
                    'required' => false,
                    'years' => range(date('Y') - 80, date('Y') - 16),
                ]
            )
            ->add('phone', TextType::class, ['label' => 'Рабочий телефон', 'required' => false])
            ->add('mobilePhone', TextType::class, ['label' => 'Мобильный телефон', 'required' => false])
            ->add('email', TextType::class, ['label' => 'E-mail', 'required' => false])
            ->add(
                'comment',
                TextareaType::class,
                [
                    'label' => 'Примечания',
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
}