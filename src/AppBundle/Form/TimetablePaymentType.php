<?php

namespace AppBundle\Form;

use AppBundle\Entity\Contractor;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class TimetablePaymentType extends PaymentType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        parent::buildForm($builder, $options);

        $builder->add('contractor', EntityType::class, [
            'label' => 'Контрактор',
            'class' => Contractor::class,
            'attr' => ['class' => 'select2me'],
            'required' => false,
            'choice_label' => 'name',
            'query_builder' => $options['contractors_qb'],
        ]);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'contractors_qb' => [],
        ]);
    }
}