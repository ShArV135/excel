<?php

namespace AppBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

class SecurityController extends Controller
{
    /**
     * @Route("/login", name="login")
     * @Template
     * @param AuthenticationUtils $authUtils
     * @return array
     */
    public function loginAction(AuthenticationUtils $authUtils)
    {
        $error = $authUtils->getLastAuthenticationError();
        $lastUsername = $authUtils->getLastUsername();

        return [
            'last_username' => $lastUsername,
            'error'         => $error,
        ];
    }

    /**
     * @Route("/logout", name="logout")
     */
    public function logoutAction()
    {
        return new Response();
    }
}
