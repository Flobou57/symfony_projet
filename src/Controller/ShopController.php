<?php

namespace App\Controller;

use App\Entity\Order;
use App\Entity\OrderItem;
use App\Repository\ProductRepository;
use App\Repository\CategoryRepository;
use App\Repository\ProductStatusRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/shop')]
class ShopController extends AbstractController
{
    /**
     * 🛍️ Affichage de la boutique
     */
    #[Route('/', name: 'app_shop_index')]
    public function index(
        ProductRepository $productRepository,
        CategoryRepository $categoryRepository,
        ProductStatusRepository $statusRepository,
        Request $request
    ): Response {
        $category = $request->query->get('category');
        $status = $request->query->get('status');

        $criteria = [];
        if (!empty($category)) $criteria['category'] = $category;
        if (!empty($status)) $criteria['status'] = $status;

        $products = $productRepository->findBy($criteria);

        return $this->render('shop/index.html.twig', [
            'products' => $products,
            'categories' => $categoryRepository->findAll(),
            'statuses' => $statusRepository->findAll(),
            'selectedCategory' => $category,
            'selectedStatus' => $status,
        ]);
    }

    /**
     * ➕ Ajouter un produit au panier
     */
    #[Route('/add/{id}', name: 'app_shop_add')]
    public function addToCart(
        int $id,
        SessionInterface $session,
        Request $request,
        ProductRepository $productRepository
    ): Response {
        $product = $productRepository->find($id);

        if (!$product) {
            $this->addFlash('danger', 'Produit introuvable.');
            return $this->redirectToRoute('app_shop_index');
        }

        if ($product->getStock() <= 0) {
            $this->addFlash('danger', "❌ Le produit « {$product->getName()} » est en rupture de stock.");
            return $this->redirectToRoute('app_shop_index');
        }

        $cart = $session->get('cart', []);
        $cart[$id] = ($cart[$id] ?? 0) + 1;
        $session->set('cart', $cart);

        $total = 0;
        foreach ($cart as $productId => $quantity) {
            $p = $productRepository->find($productId);
            if ($p) {
                $total += $p->getPrice() * $quantity;
            }
        }

        if ($request->isXmlHttpRequest()) {
            return new JsonResponse(['total' => $total, 'count' => array_sum($cart)]);
        }

        $this->addFlash('success', '🛒 Produit ajouté au panier !');
        return $this->redirectToRoute('app_shop_index');
    }

    /**
     * 🧺 Afficher le panier
     */
    #[Route('/cart', name: 'app_shop_cart')]
    public function cart(SessionInterface $session, ProductRepository $productRepository): Response
    {
        $cart = $session->get('cart', []);
        $items = [];
        $total = 0;

        foreach ($cart as $id => $quantity) {
            $product = $productRepository->find($id);
            if ($product) {
                $items[] = [
                    'product' => $product,
                    'quantity' => $quantity,
                    'subtotal' => $product->getPrice() * $quantity,
                ];
                $total += $product->getPrice() * $quantity;
            }
        }

        return $this->render('shop/cart.html.twig', [
            'items' => $items,
            'total' => $total,
        ]);
    }

    /**
     * ❌ Supprimer un produit du panier
     */
    #[Route('/remove/{id}', name: 'app_shop_remove')]
    public function removeFromCart(int $id, SessionInterface $session): Response
    {
        $cart = $session->get('cart', []);
        unset($cart[$id]);
        $session->set('cart', $cart);

        $this->addFlash('info', '❌ Produit retiré du panier.');
        return $this->redirectToRoute('app_shop_cart');
    }

    /**
     * 🧹 Vider complètement le panier
     */
    #[Route('/clear', name: 'app_shop_clear')]
    public function clearCart(SessionInterface $session): Response
    {
        $session->remove('cart');
        $this->addFlash('warning', '🧹 Panier vidé avec succès.');
        return $this->redirectToRoute('app_shop_cart');
    }

    /**
     * 💳 Paiement et validation de commande numérique
     */
    #[Route('/checkout', name: 'app_shop_checkout', methods: ['GET', 'POST'])]
    public function checkout(
        Request $request,
        SessionInterface $session,
        ProductRepository $productRepository,
        EntityManagerInterface $em
    ): Response {
        $cart = $session->get('cart', []);
        $user = $this->getUser();

        if (!$user) {
            $this->addFlash('warning', 'Veuillez vous connecter pour valider votre commande.');
            return $this->redirectToRoute('app_login');
        }

        if (empty($cart)) {
            $this->addFlash('warning', 'Votre panier est vide.');
            return $this->redirectToRoute('app_shop_cart');
        }

        // 🧾 Recalcule le panier pour affichage
        $items = [];
        $total = 0;
        foreach ($cart as $id => $qty) {
            $product = $productRepository->find($id);
            if ($product) {
                $items[] = [
                    'product' => $product,
                    'quantity' => $qty,
                    'subtotal' => $product->getPrice() * $qty,
                ];
                $total += $product->getPrice() * $qty;
            }
        }

        // 🧠 Vérifie si formulaire soumis
        if ($request->isMethod('POST')) {
            $cardNumber = $request->request->get('card_number');
            $expiration = $request->request->get('expiration');
            $cvv = $request->request->get('cvv');

            if (empty($cardNumber) || empty($expiration) || empty($cvv)) {
                $this->addFlash('danger', 'Veuillez remplir toutes les informations de carte.');
                return $this->redirectToRoute('app_shop_checkout');
            }

            // 🧾 Vérifie les stocks avant validation
            foreach ($cart as $id => $qty) {
                $product = $productRepository->find($id);
                if (!$product) {
                    $this->addFlash('danger', "Le produit #$id n'existe plus.");
                    return $this->redirectToRoute('app_shop_cart');
                }
                if ($product->getStock() < $qty) {
                    $this->addFlash('danger', "❌ Le produit « {$product->getName()} » n'est plus en stock.");
                    return $this->redirectToRoute('app_shop_cart');
                }
            }

            // ✅ Création de la commande numérique livrée instantanément
            $order = new Order();
            $order->setUser($user);
            $order->setTotal($total);
            $order->setStatus('livrée'); // commande numérique immédiate
            $order->setCreatedAt(new \DateTimeImmutable());
            $order->setUpdatedAt(new \DateTimeImmutable());

            foreach ($cart as $id => $qty) {
                $product = $productRepository->find($id);
                if (!$product) continue;

                if ($product->getStock() < $qty) {
                    $this->addFlash('danger', "❌ Le produit « {$product->getName()} » n'est plus en stock.");
                    return $this->redirectToRoute('app_shop_cart');
                }

                $item = new OrderItem();
                $item->setProduct($product);
                $item->setQuantity($qty);
                $item->setPrice($product->getPrice());
                $order->addItem($item);

                // 🔻 Mise à jour du stock
                $product->setStock($product->getStock() - $qty);

                $em->persist($item);
            }

            $em->persist($order);
            $em->flush();

            // 🔄 Vide le panier
            $session->remove('cart');

            $this->addFlash('success', '✅ Paiement validé avec la carte **** ' . substr($cardNumber, -4));
            return $this->redirectToRoute('app_shop_index');
        }

        return $this->render('shop/checkout.html.twig', [
            'items' => $items,
            'total' => $total,
        ]);
    }
}
