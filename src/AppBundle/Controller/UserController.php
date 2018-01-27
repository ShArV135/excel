<?php

namespace AppBundle\Controller;

use AppBundle\Entity\User;
use AppBundle\Form\UserType;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;

/**
 * Class ContractorController
 */
class UserController extends Controller
{
    /**
     * @Route("/users/{page}", name="user_list", requirements={"page"="\d+"})
     * @param int $page
     * @return Response
     */
    public function listAction($page = 1)
    {
        /** @var User $user */
        $user = $this->getUser();
        $em = $this->getDoctrine()->getManager();

        $qb = $em->getRepository('AppBundle:User')->createQueryBuilder('user');
        $qb->andWhere($qb->expr()->neq('user.id', $user->getId()));

        $paginator  = $this->get('knp_paginator');
        $pagination = $paginator->paginate($qb, $page,20);

        return $this->render(
            '@App/users/list.html.twig',
            [
                'pagination' => $pagination,
            ]
        );
    }

    /**
     * @Route("/users/create", name="user_create")
     * @param Request                      $request
     * @param UserPasswordEncoderInterface $passwordEncoder
     * @return RedirectResponse|Response
     */
    public function createAction(Request $request, UserPasswordEncoderInterface $passwordEncoder)
    {
        $user = new User();

        $form = $this->createForm(
            UserType::class,
            $user,
            [
                'validation_groups' => ['create'],
            ]
        );
        $form->handleRequest($request);

        if ($form->isValid()) {
            $em = $this->getDoctrine()->getManager();

            if ($plainPassowrd = $user->getPlainPassword()) {
                $password = $passwordEncoder->encodePassword($user, $plainPassowrd);
                $user->setPassword($password);
            }

            try {
                $em->persist($user);
                $em->flush();

                $this->addFlash('success', 'Пользователь успешно добавлен');

                return $this->redirectToRoute('user_list');
            } catch (\Exception $e) {
                $this->addFlash('warning', 'При сохранении возникла ошибка.');
            }
        }

        return $this->render(
            '@App/users/save.html.twig',
            [
                'page_header' => 'Создание пользователя',
                'form' => $form->createView(),
            ]
        );
    }

    /**
     * @Route("/users/{user}/update", name="user_update")
     * @param Request                      $request
     * @param User                         $user
     * @param UserPasswordEncoderInterface $passwordEncoder
     * @return RedirectResponse|Response
     */
    public function updateAction(Request $request, User $user, UserPasswordEncoderInterface $passwordEncoder)
    {
        $form = $this->createForm(
            UserType::class,
            $user
        );
        $form->handleRequest($request);

        if ($form->isValid()) {
            $em = $this->getDoctrine()->getManager();

            if ($plainPassowrd = $user->getPlainPassword()) {
                $password = $passwordEncoder->encodePassword($user, $plainPassowrd);
                $user->setPassword($password);
            }

            try {
                $em->flush();
                $this->addFlash('success', 'Пользователь успешно сохранен');
                return $this->redirectToRoute('user_list');
            } catch (\Exception $e) {
                $this->addFlash('warning', 'При сохранении возникла ошибка.');
            }
        }

        return $this->render(
            '@App/users/save.html.twig',
            [
                'page_header' => 'Редактирование пользователя',
                'form' => $form->createView(),
            ]
        );
    }

    /**
     * @Route("/users/{user}/delete", name="user_delete")
     * @param User $user
     * @return RedirectResponse
     */
    public function deleteAction(User $user)
    {
        try {
            $em = $this->getDoctrine()->getManager();
            $em->remove($user);
            $em->flush();
            $this->addFlash('success', 'Пользователь удален.');
        } catch (\Exception $e) {
            $this->addFlash('warning', 'При удалении возникла ошибка.');
        }

        return $this->redirectToRoute('user_list');
    }
}
