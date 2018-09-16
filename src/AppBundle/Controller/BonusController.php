<?php

namespace AppBundle\Controller;

use AppBundle\Entity\Bonus;
use AppBundle\Form\BonusType;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class BonusController
 */
class BonusController extends Controller
{
    /**
     * @Route("/bonuses", name="bonus_index")
     * @param Request $request
     * @return Response
     */
    public function indexAction(Request $request)
    {
        $em = $this->getDoctrine()->getManager();
        $bonuses = $em->getRepository('AppBundle:Bonus')->findAll();
        $bonusesByManager = [
            Bonus::MANAGER_TYPE_CUSTOMER => new Bonus(),
            Bonus::MANAGER_TYPE_PROVIDER => new Bonus(),
        ];
        $data = [];
        foreach ($bonuses as $bonus) {
            $bonusesByManager[$bonus->getManagerType()] = $bonus;

            $data[$bonus->getManagerType()] = [
                'type' => $bonus->getType(),
                'value' => $bonus->getValue(),
            ];
        }

        $form = $this->createForm(BonusType::class, $data);
        $form->handleRequest($request);

        if ($form->isValid()) {
            $data = $form->getData();

            foreach ($data as $type => $datum) {
                /** @var Bonus $bonus */
                $bonus = $bonusesByManager[$type];

                $bonus
                    ->setManagerType($type)
                    ->setType($datum['type'])
                    ->setValue($datum['value'])
                ;

                $em->persist($bonus);
            }

            $em->flush();
            $this->addFlash('success', 'Бонусы обновлены');
        }

        return $this->render(
            '@App/bonus/index.html.twig',
            [
                'form' => $form->createView(),
            ]
        );

    }
}