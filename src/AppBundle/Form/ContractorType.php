<?php

namespace AppBundle\Form;

use AppBundle\Entity\User;
use Doctrine\ORM\EntityRepository;
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
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'contractor_type' => null,
            'choice_manager' => false,
        ]);
    }
}