<?php

namespace AppBundle\Form;

use AppBundle\Entity\Contractor;
use AppBundle\Entity\Organisation;
use AppBundle\Entity\User;
use AppBundle\Service\Contractor\GetListService;
use AppBundle\Service\ManagerChoiceService;
use AppBundle\Service\TimetableRow\UpdateFormAccessService;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;

class TimetableRowType extends AbstractType
{
    private $managerChoiceService;
    private $accessService;
    private $getListService;

    public function __construct(ManagerChoiceService $managerChoiceService, UpdateFormAccessService $accessService, GetListService $getListService)
    {
        $this->managerChoiceService = $managerChoiceService;
        $this->accessService = $accessService;
        $this->getListService = $getListService;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('object', TextType::class, ['label' => 'Объект'])
            ->add('mechanism', TextType::class, ['label' => 'Механизм'])
            ->add('bitrix24Id', TextType::class, ['label' => 'ID Bitrix24', 'required' => false])
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
                'organization',
                EntityType::class,
                [
                    'label' => 'Работаем с зак-м от...',
                    'required' => false,
                    'class' => Organisation::class,
                    'choice_label' => 'name',
                    'attr' => ['class' => 'select2me'],
                ]
            )
            ->add(
                'customer',
                EntityType::class,
                [
                    'required' => false,
                    'label' => 'Заказчик',
                    'attr' => ['class' => 'timetable-row-contractor customer'],
                    'choices' => $this->getListService->getCustomers(),
                    'class' => Contractor::class,
                    'choice_label' => 'name',
                ]
            )
            ->add('save', SubmitType::class, ['label' => 'Сохранить', 'attr' => ['class' => 'btn-primary']])
        ;

        if ($this->accessService->customerManager()) {
            $builder
                ->add(
                    'manager',
                    EntityType::class,
                    [
                        'label' => 'Менеджер по продажам',
                        'class' => User::class,
                        'attr' => ['class' => 'select2me'],
                        'choice_label' => 'fullname',
                        'query_builder' => $this->managerChoiceService->getCustomerManagerBuilder(),
                    ]
                )
            ;
        }

        if ($this->accessService->providerManager()) {
            $builder
                ->add(
                    'providerManager',
                    EntityType::class,
                    [
                        'label' => 'Менеджер по снабжению',
                        'class' => User::class,
                        'required' => false,
                        'attr' => ['class' => 'select2me'],
                        'choice_label' => 'fullname',
                        'query_builder' => $this->managerChoiceService->getProviderManagerBuilder(),
                    ]
                )
            ;
        }

        if ($this->accessService->provider()) {
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
                        'required' => false,
                        'label' => 'Поставщик',
                        'attr' => ['class' => 'timetable-row-contractor provider'],
                        'choices' => $this->getListService->getProviders(),
                        'class' => Contractor::class,
                        'choice_label' => 'name',
                    ]
                )
                ->add(
                    'showAllProviders',
                    CheckboxType::class,
                    [
                        'required' => false,
                        'label' => 'Показать всех поставщиков',
                    ]
                )
            ;
        }
    }

    /**
     * @return string|null
     */
    public function getBlockPrefix()
    {
        return null;
    }
}
