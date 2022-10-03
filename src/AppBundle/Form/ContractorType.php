<?php

namespace AppBundle\Form;

use AppBundle\Entity\Organisation;
use AppBundle\Entity\User;
use AppBundle\Repository\UserRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ContractorType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('name', TextType::class, ['label' => 'Наименование'])
            ->add('inn', TextType::class, ['label' => 'ИНН'])
            ->add('brand', TextType::class, ['label' => 'Почтовый адрес', 'required' => false])
            ->add('businessAddress', TextType::class, ['label' => 'Юридический адрес', 'required' => false])
            ->add('physicalAddress', TextType::class, ['label' => 'Фактический адрес офиса', 'required' => false])
            ->add('site', TextType::class, ['label' => 'Сайт', 'required' => false])
            ->add('bitrix24Id', TextType::class, ['label' => 'ID Bitrix24', 'required' => false])
            ->add(
                'organisation',
                EntityType::class,
                [
                    'required' => false,
                    'class' => Organisation::class,
                    'choice_label' => 'name',
                    'label' => 'Организация',
                ]
            )
            ->add('save', SubmitType::class, ['label' => 'Сохранить', 'attr' => ['class' => 'btn-primary']])
        ;

        if ($options['choice_manager']) {
            $builder
                ->add(
                    'manager',
                    EntityType::class,
                    [
                        'required' => false,
                        'label' => 'Менеджер по продажам',
                        'attr' => ['class' => 'select2me'],
                        'class' => User::class,
                        'choice_label' => 'fullname',
                        'query_builder' => function(UserRepository $repository) {
                            return $repository->getManagerQueryBuilder(['ROLE_CUSTOMER_MANAGER', 'ROLE_RENT_MANAGER']);
                        },
                    ]
                )
            ;
        }
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'contractor_type' => null,
            'choice_manager' => false,
        ]);
    }
}
