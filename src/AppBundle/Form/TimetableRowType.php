<?php

namespace AppBundle\Form;

use AppBundle\Entity\Contractor;
use AppBundle\Entity\User;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class TimetableRowType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('object', TextType::class, ['label' => 'Объект'])
            ->add('mechanism', TextType::class, ['label' => 'Механизм'])
            ->add(
                'price_for_customer',
                MoneyType::class,
                [
                    'label' => 'Цена заказчика',
                    'currency' => null,
                ]
            )
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
            ->add(
                'customer',
                EntityType::class,
                [
                    'label' => 'Заказчик',
                    'class' => Contractor::class,
                    'choice_label' => 'name',
                    'query_builder' => function(EntityRepository $repository) use ($options) {
                        $qb = $repository->createQueryBuilder('e');

                        $qb
                            ->where($qb->expr()->eq('e.type', ':type'))
                            ->setParameter('type', Contractor::CUSTOMER)
                        ;

                        if (!empty($options['customer_choice_criteria']['manager'])) {
                            $qb
                                ->where($qb->expr()->eq('e.manager', ':manager'))
                                ->setParameter('manager', $options['customer_choice_criteria']['manager'])
                            ;
                        }

                        return $qb;
                    },
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
                        'label' => 'Менеджер по продажам',
                        'class' => User::class,
                        'choice_label' => 'username',
                        'query_builder' => function(EntityRepository $repository) {
                            $qb = $repository->createQueryBuilder('e');
                            return $qb
                                ->where($qb->expr()->like('e.roles', ':roles'))
                                ->setParameter('roles', '%ROLE_CUSTOMER_MANAGER%')
                                ;
                        },
                    ]
                )
            ;
        }

        if ($options['choice_provider']) {
            $builder
                ->add(
                    'price_for_provider',
                    MoneyType::class,
                    [
                        'label' => 'Цена поставщика',
                        'currency' => null,
                    ]
                )
                ->add(
                    'provider',
                    EntityType::class,
                    [
                        'label' => 'Поставщик',
                        'class' => Contractor::class,
                        'choice_label' => 'name',
                        'query_builder' => function(EntityRepository $repository) use ($options) {
                            $qb = $repository->createQueryBuilder('e');

                            $qb
                                ->where($qb->expr()->eq('e.type', ':type'))
                                ->setParameter('type', Contractor::PROVIDER)
                            ;

                            return $qb;
                        },
                    ]
                )
            ;
        }
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'choice_manager' => false,
            'customer_choice_criteria' => [],
            'choice_provider' => false,
        ]);
    }
}
