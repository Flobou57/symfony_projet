<?php

namespace App\Repository;

use App\Entity\Order;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Order>
 */
class OrderRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Order::class);
    }

    /**
     * Récupère les 5 dernières commandes
     */
    public function findLastOrders(int $limit = 5): array
    {
        return $this->createQueryBuilder('o')
            ->orderBy('o.id', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Calcule le total des ventes pour les commandes livrées
     */
    public function getTotalSales(): float
    {
        $result = $this->createQueryBuilder('o')
            ->select('SUM(o.total) as total')
            ->where('o.status = :status')
            ->setParameter('status', 'livrée')
            ->getQuery()
            ->getSingleScalarResult();

        return (float) $result;
    }

    /**
     * Calcule les ventes par mois (pour le graphique)
     */
    public function getMonthlySales(): array
    {
        return $this->createQueryBuilder('o')
            ->select("DATE_FORMAT(o.createdAt, '%Y-%m') as month, SUM(o.total) as total")
            ->where('o.status = :status')
            ->setParameter('status', 'livrée')
            ->groupBy('month')
            ->orderBy('month', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
