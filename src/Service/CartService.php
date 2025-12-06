<?php

namespace App\Service;

use App\Repository\ProductRepository;
use Symfony\Component\HttpFoundation\RequestStack;

class CartService
{
    public function __construct(
        private RequestStack $requestStack,
        private ProductRepository $productRepository
    ) {
    }

    /**
        * Retourne le résumé du panier : total et quantité totale.
        */
    public function getSummary(): array
    {
        $session = $this->requestStack->getSession();

        // Si aucune session (CLI ou contexte spécial), renvoyer 0
        if (!$session) {
            return ['count' => 0, 'total' => 0.0];
        }

        $cart = $session->get('cart', []);
        $total = 0.0;
        $count = 0;

        foreach ($cart as $productId => $quantity) {
            $count += $quantity;

            $product = $this->productRepository->find($productId);
            if (!$product) {
                // Nettoie les entrées obsolètes (produit supprimé/changé)
                unset($cart[$productId]);
                continue;
            }

            $total += $product->getPrice() * $quantity;
        }

        // Met à jour la session avec un panier épuré des produits absents
        $session->set('cart', $cart);

        return [
            'count' => $count,
            'total' => $total,
        ];
    }
}
