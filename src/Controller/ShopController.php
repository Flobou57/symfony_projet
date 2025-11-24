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
        if ($category) $criteria['category'] = $category;
        if ($status) $criteria['status'] = $status;

        $products = $productRepository->findBy($criteria);

        return $this->render('shop/index.html.twig', [
            'products' => $products,
            'categories' => $categoryRepository->findAll(),
            'statuses' => $statusRepository->findAll(),
            'selectedCategory' => $category,
            'selectedStatus' => $status,
        ]);
    }

    #[Route('/add/{id}', name: 'app_shop_add')]
    public function addToCart(int $id, SessionInterface $session, Request $request, ProductRepository $productRepository): Response
    {
        $cart = $session->get('cart', []);
        $cart[$id] = ($cart[$id] ?? 0) + 1;
        $session->set('cart', $cart);

        if ($request->isXmlHttpRequest()) {
            $total = 0; $count = 0;
            foreach ($cart as $productId => $qty) {
                $p = $productRepository->find($productId);
                if ($p) {
                    $total += $p->getPrice() * $qty;
                    $count += $qty;
                }
            }
            return new JsonResponse(['count' => $count, 'total' => $total]);
        }

        $this->addFlash('success', '🛒 Produit ajouté au panier !');
        return $this->redirectToRoute('app_shop_index');
    }

    #[Route('/cart', name: 'app_shop_cart')]
    public function cart(SessionInterface $session, ProductRepository $productRepository): Response
    {
        $cart = $session->get('cart', []);
        $items = [];
        $total = 0;

        foreach ($cart as $id => $qty) {
            $p = $productRepository->find($id);
            if ($p) {
                $items[] = [
                    'product' => $p,
                    'quantity' => $qty,
                    'subtotal' => $p->getPrice() * $qty,
                ];
                $total += $p->getPrice() * $qty;
            }
        }

        return $this->render('shop/cart.html.twig', [
            'items' => $items,
            'total' => $total,
        ]);
    }

    #[Route('/remove/{id}', name: 'app_shop_remove')]
    public function removeFromCart(int $id, SessionInterface $session): Response
    {
        $cart = $session->get('cart', []);
        unset($cart[$id]);
        $session->set('cart', $cart);
        $this->addFlash('info', '❌ Produit retiré du panier.');
        return $this->redirectToRoute('app_shop_cart');
    }

    #[Route('/clear', name: 'app_shop_clear')]
    public function clearCart(SessionInterface $session): Response
    {
        $session->remove('cart');
        $this->addFlash('warning', '🧹 Panier vidé.');
        return $this->redirectToRoute('app_shop_cart');
    }

    #[Route('/checkout', name: 'app_shop_checkout', methods: ['GET', 'POST'])]
    public function checkout(Request $request, SessionInterface $session, ProductRepository $productRepository, EntityManagerInterface $em): Response
    {
        $cart = $session->get('cart', []);
        $user = $this->getUser();

        if (!$user) {
            $this->addFlash('warning', 'Connectez-vous pour valider votre commande.');
            return $this->redirectToRoute('app_login');
        }

        if (empty($cart)) {
            $this->addFlash('warning', 'Votre panier est vide.');
            return $this->redirectToRoute('app_shop_cart');
        }

        $items = [];
        $total = 0;
        foreach ($cart as $id => $qty) {
            $p = $productRepository->find($id);
            if ($p) {
                $items[] = [
                    'product' => $p,
                    'quantity' => $qty,
                    'subtotal' => $p->getPrice() * $qty,
                ];
                $total += $p->getPrice() * $qty;
            }
        }

        // ✅ Validation du paiement (POST)
        if ($request->isMethod('POST')) {
            $cardNumber = trim($request->request->get('card_number'));
            $expiration = trim($request->request->get('expiration'));
            $cvv = trim($request->request->get('cvv'));

            if (!$cardNumber || !$expiration || !$cvv) {
                $this->addFlash('danger', 'Veuillez remplir toutes les informations de carte.');
                return $this->redirectToRoute('app_shop_checkout');
            }

            // Création de la commande factice
            $order = new Order();
            $order->setUser($user);
            $order->setTotal($total);

            foreach ($cart as $id => $qty) {
                $p = $productRepository->find($id);
                if (!$p) continue;
                $item = new OrderItem();
                $item->setProduct($p);
                $item->setQuantity($qty);
                $item->setPrice($p->getPrice());
                $order->addItem($item);
                $p->setStock(max(0, $p->getStock() - $qty));
                $em->persist($item);
            }

            $em->persist($order);
            $em->flush();

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
