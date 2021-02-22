<?php

namespace AppBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;

class BonusType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $types = [
            'customer' => 'Менеджеры по продажам',
            'top_customer' => 'Старший менеджеры по продажам',
            'provider' => 'Менеджеры по снабжению',
            'top_provider' => 'Старший менеджеры по снабжению'
        ];

        foreach ($types as $type => $label) {
            $builder->add($type, BonusRowType::class, ['label' => $label]);
        }

        $builder->add('save', SubmitType::class, ['label' => 'Сохранить', 'attr' => ['class' => 'btn-primary']]);
    }
}
