<?php

namespace AppBundle\Form;

use AppBundle\Entity\User;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Length;

class UserType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('username', TextType::class)
            ->add(
                'plainPassword',
                RepeatedType::class,
                [
                    'label' => 'Пароль',
                    'type' => PasswordType::class,
                    'required' => false,
                    'options' => [
                        'constraints' => [
                            new Length(['min' => 6]),
                        ],
                    ],
                    'first_options' => [
                        'label' => 'Пароль',
                    ],
                    'second_options' => [
                        'label' => 'Повтор пароля',
                    ],
                ]
            )
            ->add('firstname', TextType::class, ['label' => 'Имя'])
            ->add('lastname', TextType::class, ['label' => 'Фамилия'])
            ->add('surname', TextType::class, ['label' => 'Отчество'])
            ->add(
                'roles',
                ChoiceType::class,
                [
                    'label' => 'Роль',
                    'choices' => $options['roles'],
                ]
            )
            ->add('save', SubmitType::class, ['label' => 'Сохранить', 'attr' => ['class' => 'btn-primary']])
        ;

        $builder->get('roles')->addModelTransformer(new CallbackTransformer(
            function ($value) {
                return is_array($value) ? array_shift($value) : $value;

            },
            function ($value) {
                return [$value];
            }
        ));
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'roles' => [],
        ]);
    }
}
