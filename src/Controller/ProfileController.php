<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use Doctrine\ORM\EntityManagerInterface;
use App\Form\AddressType;
use App\Entity\Address;

class ProfileController extends AbstractController
{
    #[Route('/profile', name: 'app_profile', methods: ['GET', 'POST'])]
    public function show(Request $request, EntityManagerInterface $em): Response
    {
        $user = $this->getUser();

        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        $address = new Address();
        $addressForm = $this->createForm(AddressType::class, $address);
        $addressForm->handleRequest($request);

        if ($addressForm->isSubmitted() && $addressForm->isValid()) {
            $address->setUser($user);
            $em->persist($address);
            $em->flush();

            $this->addFlash('success', 'Adresse ajoutée.');
            return $this->redirectToRoute('app_profile');
        }

        return $this->render('profile/show.html.twig', [
            'user' => $user,
            'addressForm' => $addressForm->createView(),
        ]);
    }

    #[Route('/profile/address/{id}/delete', name: 'app_profile_address_delete', methods: ['POST'])]
    public function deleteAddress(Address $address, Request $request, EntityManagerInterface $em): Response
    {
        $user = $this->getUser();
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        if ($address->getUser() !== $user) {
            throw $this->createAccessDeniedException();
        }

        if ($this->isCsrfTokenValid('delete_address_' . $address->getId(), $request->request->get('_token'))) {
            $em->remove($address);
            $em->flush();
            $this->addFlash('info', 'Adresse supprimée.');
        }

        return $this->redirectToRoute('app_profile');
    }
}
