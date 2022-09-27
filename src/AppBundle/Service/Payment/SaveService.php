<?php

namespace AppBundle\Service\Payment;

use AppBundle\Entity\Payment;
use AppBundle\Event\PaymentEvent;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class SaveService
{
    private $entityManager;
    private $dispatcher;

    public function __construct(EntityManagerInterface $entityManager, EventDispatcherInterface $dispatcher)
    {
        $this->entityManager = $entityManager;
        $this->dispatcher = $dispatcher;
    }

    public function save(Payment $payment): void
    {
        $this->entityManager->persist($payment);
        $this->entityManager->flush();

        $this->dispatcher->dispatch(PaymentEvent::UPDATE, new PaymentEvent($payment));
        $this->entityManager->flush();
    }
}
