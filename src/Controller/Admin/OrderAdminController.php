<?php

namespace App\Controller\Admin;

use App\Repository\OrderRepository;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/admin/orders')]
class OrderAdminController extends AbstractController
{
    #[Route('/', name: 'app_admin_orders')]
    public function index(OrderRepository $orderRepository, PaginatorInterface $paginator, Request $request): Response
    {
        $query = $orderRepository->createQueryBuilder('o')
            ->leftJoin('o.user', 'u')
            ->addSelect('u')
            ->orderBy('o.createdAt', 'DESC')
            ->getQuery();

        $orders = $paginator->paginate(
            $query,
            $request->query->getInt('page', 1),
            10 // 🔹 nombre de résultats par page
        );

        return $this->render('admin/orders.html.twig', [
            'orders' => $orders,
        ]);
    }
}
