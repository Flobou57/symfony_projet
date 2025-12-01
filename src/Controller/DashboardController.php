<?php

namespace App\Controller;

use App\Repository\ProductRepository;
use App\Repository\CategoryRepository;
use App\Repository\OrderRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/admin')]
class DashboardController extends AbstractController
{
    #[Route('/dashboard', name: 'app_admin_dashboard')]
    public function index(
        ProductRepository $productRepository,
        CategoryRepository $categoryRepository,
        OrderRepository $orderRepository,
        EntityManagerInterface $em
    ): Response {
        // 1️⃣ Nombre de produits par catégorie
        $categories = $categoryRepository->findAll();
        $productsByCategory = [];
        foreach ($categories as $cat) {
            $count = $productRepository->count(['category' => $cat]);
            $productsByCategory[$cat->getName()] = $count;
        }

        // 2️⃣ Les 5 dernières commandes
        $lastOrders = $orderRepository->findBy([], ['id' => 'DESC'], 5);

        // 3️⃣ Ratio des statuts produits
        $totalProducts = $productRepository->count([]);
        $statusCounts = [
            'En stock' => $productRepository->count(['status' => 1]),
            'Rupture' => $productRepository->count(['status' => 2]),
            'Précommande' => $productRepository->count(['status' => 3]),
        ];

        $ratios = [];
        foreach ($statusCounts as $status => $count) {
            $ratios[$status] = $totalProducts > 0 ? round(($count / $totalProducts) * 100, 2) : 0;
        }

        // 4️⃣ Montant total des ventes (par mois, commandes livrées)
        $connection = $em->getConnection();
        $salesData = $connection->executeQuery("
            SELECT DATE_FORMAT(created_at, '%Y-%m') AS month, SUM(total) AS total
            FROM orders
            WHERE status = 'livrée'
            GROUP BY month
            ORDER BY month DESC
            LIMIT 6
        ")->fetchAllAssociative();

        return $this->render('admin/dashboard.html.twig', [
            'productsByCategory' => $productsByCategory,
            'lastOrders' => $lastOrders,
            'ratios' => $ratios,
            'salesData' => $salesData,
        ]);
    }
}
