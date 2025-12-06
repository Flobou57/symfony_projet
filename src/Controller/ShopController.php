<?php

namespace App\Controller;

use App\Entity\Order;
use App\Entity\OrderItem;
use App\Enum\OrderStatus;
use App\Entity\Product;
use App\Repository\ProductRepository;
use App\Repository\CategoryRepository;
use App\Repository\ProductStatusRepository;
use App\Service\CartService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

#[Route('/shop')]
class ShopController extends AbstractController
{
    public function __construct(private TranslatorInterface $translator)
    {
    }

    #[Route('/', name: 'app_shop_index')]
    public function index(
        ProductRepository $productRepository,
        CategoryRepository $categoryRepository,
        ProductStatusRepository $statusRepository,
        Request $request,
        \Knp\Component\Pager\PaginatorInterface $paginator
    ): Response {
        $category = $request->query->get('category');
        $status = $request->query->get('status');
        $query = $request->query->get('q');

        $qb = $productRepository->searchQueryBuilder(
            $query ?: null,
            $category ? (int) $category : null,
            $status ? (int) $status : null
        );

        $products = $paginator->paginate(
            $qb,
            $request->query->getInt('page', 1),
            9
        );

        return $this->render('shop/index.html.twig', [
            'products' => $products,
            'categories' => $categoryRepository->findAll(),
            'statuses' => $statusRepository->findAll(),
            'selectedCategory' => $category,
            'selectedStatus' => $status,
            'selectedQuery' => $query,
        ]);
    }

    #[Route('/search/live', name: 'app_shop_live_search', methods: ['GET'])]
    public function liveSearch(
        Request $request,
        ProductRepository $productRepository,
        CategoryRepository $categoryRepository,
        ProductStatusRepository $statusRepository,
        \Knp\Component\Pager\PaginatorInterface $paginator
    ): Response {
        $category = $request->query->get('category');
        $status = $request->query->get('status');
        $query = $request->query->get('q');
        $page = $request->query->getInt('page', 1);

        $qb = $productRepository->searchQueryBuilder(
            $query ?: null,
            $category ? (int) $category : null,
            $status ? (int) $status : null
        );

        $products = $paginator->paginate(
            $qb,
            $page,
            9
        );

        return $this->render('shop/_results.html.twig', [
            'products' => $products,
            'categories' => $categoryRepository->findAll(),
            'statuses' => $statusRepository->findAll(),
            'selectedCategory' => $category,
            'selectedStatus' => $status,
            'selectedQuery' => $query,
        ]);
    }

    #[Route('/search/suggestions', name: 'app_shop_suggestions', methods: ['GET'])]
    public function suggestions(
        Request $request,
        ProductRepository $productRepository,
        UrlGeneratorInterface $urlGenerator
    ): JsonResponse {
        $q = trim((string) $request->query->get('q', ''));
        if ($q === '') {
            return new JsonResponse([]);
        }

        $qb = $productRepository->createQueryBuilder('p')
            ->where('p.name LIKE :q OR p.description LIKE :q')
            ->setParameter('q', '%' . $q . '%')
            ->setMaxResults(8);

        $results = [];
        foreach ($qb->getQuery()->getResult() as $product) {
            $imageUrl = null;
            $images = $product->getImages();
            if ($images->count() > 0 && $images->first()) {
                $imageUrl = $images->first()->getUrl();
            }

            $results[] = [
                'id' => $product->getId(),
                'name' => $product->getName(),
                'price' => $product->getPrice(),
                'url' => $urlGenerator->generate('app_shop_product_show', ['id' => $product->getId()]),
                'image' => $imageUrl ?? '/images/no-image.png',
            ];
        }

        return new JsonResponse($results);
    }

    #[Route('/product/{id}', name: 'app_shop_product_show', methods: ['GET'])]
    public function productShow(Product $product): Response
    {
        return $this->render('shop/show.html.twig', [
            'product' => $product,
        ]);
    }

    #[Route('/add/{id}', name: 'app_shop_add')]
    public function addToCart(
        int $id,
        SessionInterface $session,
        Request $request,
        ProductRepository $productRepository
    ): Response {
        $product = $productRepository->find($id);

        if (!$product) {
            $this->addFlash('danger', $this->translator->trans('flash.product_not_found'));
            return $this->redirectToRoute('app_shop_index');
        }

        if ($product->getStock() <= 0) {
            $this->addFlash('danger', $this->translator->trans('flash.out_of_stock', [
                '%name%' => $product->getName(),
            ]));
            return $this->redirectToRoute('app_shop_index');
        }

        $cart = $session->get('cart', []);
        $currentQty = $cart[$id] ?? 0;

        // Empêche de dépasser le stock disponible
        if ($currentQty >= $product->getStock()) {
            $message = $this->translator->trans('flash.cart_limit', [
                '%name%' => $product->getName(),
                '%stock%' => $product->getStock(),
            ]);

            if ($request->isXmlHttpRequest()) {
                return new JsonResponse(['error' => $message], Response::HTTP_BAD_REQUEST);
            }

            $this->addFlash('danger', $message);
            return $this->redirectToRoute('app_shop_index');
        }

        $cart[$id] = $currentQty + 1;
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

        $this->addFlash('success', $this->translator->trans('flash.cart_added'));
        return $this->redirectToRoute('app_shop_index');
    }

    #[Route('/cart/summary', name: 'app_shop_cart_summary', methods: ['GET'])]
    public function cartSummary(CartService $cartService): JsonResponse
    {
        return new JsonResponse($cartService->getSummary());
    }

    #[Route('/cart', name: 'app_shop_cart')]
    public function cart(SessionInterface $session, ProductRepository $productRepository): Response
    {
        $cart = $session->get('cart', []);
        $items = [];
        $total = 0;
        $cleanCart = $cart;

        foreach ($cart as $id => $quantity) {
            $product = $productRepository->find($id);
            if ($product) {
                $items[] = [
                    'product' => $product,
                    'quantity' => $quantity,
                    'subtotal' => $product->getPrice() * $quantity,
                ];
                $total += $product->getPrice() * $quantity;
            } else {
                unset($cleanCart[$id]); // produit supprimé -> on nettoie
            }
        }

        // Enregistre le panier nettoyé si besoin
        if ($cleanCart !== $cart) {
            $session->set('cart', $cleanCart);
        }

        return $this->render('shop/cart.html.twig', [
            'items' => $items,
            'total' => $total,
        ]);
    }

    #[Route('/remove/{id}', name: 'app_shop_remove')]
    public function removeFromCart(
        int $id,
        SessionInterface $session,
        Request $request,
        ProductRepository $productRepository
    ): Response {
        $cart = $session->get('cart', []);
        $removed = array_key_exists($id, $cart);
        unset($cart[$id]);
        $session->set('cart', $cart);

        $total = 0;
        $count = 0;
        foreach ($cart as $productId => $qty) {
            $p = $productRepository->find($productId);
            if ($p) {
                $total += $p->getPrice() * $qty;
                $count += $qty;
            }
        }

        if ($request->isXmlHttpRequest()) {
            return new JsonResponse([
                'success' => $removed,
                'total' => $total,
                'count' => $count,
            ]);
        }

        $this->addFlash('info', $this->translator->trans('flash.product_removed'));
        return $this->redirectToRoute('app_shop_cart');
    }

    #[Route('/clear', name: 'app_shop_clear')]
    public function clearCart(SessionInterface $session): Response
    {
        $session->remove('cart');
        $this->addFlash('warning', $this->translator->trans('flash.cart_cleared'));
        return $this->redirectToRoute('app_shop_cart');
    }

    #[Route('/update/{id}', name: 'app_shop_update_quantity', methods: ['POST'])]
    public function updateQuantity(
        int $id,
        Request $request,
        SessionInterface $session,
        ProductRepository $productRepository
    ): Response {
        $quantity = max(1, (int) $request->request->get('quantity', 1));
        $product = $productRepository->find($id);

        if (!$product) {
            $message = $this->translator->trans('flash.product_not_found');
            if ($request->isXmlHttpRequest()) {
                return new JsonResponse(['error' => $message], Response::HTTP_BAD_REQUEST);
            }
            $this->addFlash('danger', $message);
            return $this->redirectToRoute('app_shop_cart');
        }

        if ($product->getStock() < $quantity) {
            $message = $this->translator->trans('flash.stock_unavailable', [
                '%name%' => $product->getName(),
            ]);
            if ($request->isXmlHttpRequest()) {
                return new JsonResponse(['error' => $message], Response::HTTP_BAD_REQUEST);
            }
            $this->addFlash('danger', $message);
            return $this->redirectToRoute('app_shop_cart');
        }

        $cart = $session->get('cart', []);
        $cart[$id] = $quantity;
        $session->set('cart', $cart);

        // Recalcule total + count
        $total = 0;
        $count = 0;
        foreach ($cart as $productId => $qty) {
            $p = $productRepository->find($productId);
            if ($p) {
                $total += $p->getPrice() * $qty;
                $count += $qty;
            }
        }
        $lineSubtotal = $product->getPrice() * $quantity;

        if ($request->isXmlHttpRequest()) {
            return new JsonResponse([
                'success' => true,
                'total' => $total,
                'count' => $count,
                'productId' => $id,
                'lineSubtotal' => $lineSubtotal,
            ]);
        }

        $this->addFlash('success', 'Quantité mise à jour.');
        return $this->redirectToRoute('app_shop_cart');
    }

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
            $this->addFlash('warning', $this->translator->trans('flash.login_required'));
            return $this->redirectToRoute('app_login');
        }

        if (empty($cart)) {
            $this->addFlash('warning', $this->translator->trans('flash.cart_empty'));
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
                $this->addFlash('danger', $this->translator->trans('flash.card_info_missing'));
                return $this->redirectToRoute('app_shop_checkout');
            }

            // 🧾 Vérifie les stocks avant validation
            foreach ($cart as $id => $qty) {
                $product = $productRepository->find($id);
                if (!$product) {
                    $this->addFlash('danger', $this->translator->trans('flash.product_missing', [
                        '%id%' => $id,
                    ]));
                    return $this->redirectToRoute('app_shop_cart');
                }
                if ($product->getStock() < $qty) {
                    $this->addFlash('danger', $this->translator->trans('flash.stock_unavailable', [
                        '%name%' => $product->getName(),
                    ]));
                    return $this->redirectToRoute('app_shop_cart');
                }
            }

            // ✅ Création de la commande numérique livrée instantanément
            $order = new Order();
            $order->setUser($user);
            $order->setReference(uniqid('CMD-'));
            $order->setTotal($total);
            $order->setStatus(OrderStatus::LIVREE); // commande numérique immédiate
            $order->setCreatedAt(new \DateTimeImmutable());
            $order->setUpdatedAt(new \DateTimeImmutable());

            foreach ($cart as $id => $qty) {
                $product = $productRepository->find($id);
                if (!$product)
                    continue;

                if ($product->getStock() < $qty) {
                    $this->addFlash('danger', $this->translator->trans('flash.stock_unavailable', [
                        '%name%' => $product->getName(),
                    ]));
                    return $this->redirectToRoute('app_shop_cart');
                }

                $item = new OrderItem();
                $item->setProduct($product);
                $item->setQuantity($qty);
                $item->setProductPrice($product->getPrice());
                $order->addItem($item);

                // 🔻 Mise à jour du stock
                $product->setStock($product->getStock() - $qty);

                $em->persist($item);
            }

            $em->persist($order);
            $em->flush();

            // 🔄 Vide le panier
            $session->remove('cart');

            $this->addFlash('success', $this->translator->trans('flash.payment_success', [
                '%last4%' => substr($cardNumber, -4),
            ]));
            return $this->redirectToRoute('app_shop_index');
        }

        return $this->render('shop/checkout.html.twig', [
            'items' => $items,
            'total' => $total,
        ]);
    }
}
