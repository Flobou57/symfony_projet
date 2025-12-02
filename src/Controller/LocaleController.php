<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Annotation\Route;

class LocaleController extends AbstractController
{
    #[Route('/locale/{_locale}', name: 'app_locale_switch', requirements: ['_locale' => 'en|fr'])]
    public function switchLocale(string $_locale, Request $request, SessionInterface $session): RedirectResponse
    {
        $session->set('_locale', $_locale);

        $referer = $request->headers->get('referer');
        if ($referer) {
            return new RedirectResponse($referer);
        }

        return $this->redirectToRoute('app_home');
    }
}
