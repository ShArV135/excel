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
            'provider' => 'Менеджеры по снабжению'
        ];

        foreach ($types as $type => $label) {
            $builder->add($type, BonusRowType::class, ['label' => $label]);
        }

        $builder->add('save', SubmitType::class, ['label' => 'Сохранить', 'attr' => ['class' => 'btn-primary']]);
    }
}
