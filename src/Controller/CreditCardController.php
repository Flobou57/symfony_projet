<?php

namespace App\Controller;

use App\Entity\CreditCard;
use App\Form\CreditCardType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/credit-card')]
#[IsGranted('ROLE_USER')]
class CreditCardController extends AbstractController
{
    #[Route('/', name: 'app_credit_card_index', methods: ['GET', 'POST'])]
    public function index(Request $request, EntityManagerInterface $em): Response
    {
        $user = $this->getUser();

        $creditCard = new CreditCard();
        $form = $this->createForm(CreditCardType::class, $creditCard);
        $form->handleRequest($request);

        // Validation automatique sans rechargement
        if ($form->isSubmitted() && $form->isValid()) {
            $creditCard->setUser($user);
            $em->persist($creditCard);
            $em->flush();

            if ($request->isXmlHttpRequest()) {
                return $this->json([
                    'success' => true,
                    'message' => '💳 Carte ajoutée avec succès !',
                    'card' => [
                        'number' => substr($creditCard->getNumber(), -4),
                        'expirationDate' => $creditCard->getExpirationDate(),
                    ]
                ]);
            }

            $this->addFlash('success', '💳 Carte ajoutée avec succès !');
            return $this->redirectToRoute('app_credit_card_index');
        }

        return $this->render('credit_card/index.html.twig', [
            'cards' => $user->getCreditCards(),
            'form' => $form,
        ]);
    }

    #[Route('/delete/{id}', name: 'app_credit_card_delete', methods: ['POST'])]
    public function delete(CreditCard $creditCard, EntityManagerInterface $em): Response
    {
        if ($creditCard->getUser() !== $this->getUser()) {
            throw $this->createAccessDeniedException();
        }

        $em->remove($creditCard);
        $em->flush();

        $this->addFlash('warning', '💳 Carte supprimée.');
        return $this->redirectToRoute('app_credit_card_index');
    }
}
