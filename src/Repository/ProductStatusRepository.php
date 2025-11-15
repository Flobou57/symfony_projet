<?php

namespace App\Repository;

use App\Entity\ProductStatus;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ProductStatus>
 *
 * @method ProductStatus|null find($id, $lockMode = null, $lockVersion = null)
 * @method ProductStatus|null findOneBy(array $criteria, array $orderBy = null)
 * @method ProductStatus[]    findAll()
 * @method ProductStatus[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ProductStatusRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ProductStatus::class);
    }

    /**
     * Sauvegarde ou met à jour un statut
     */
    public function save(ProductStatus $status, bool $flush = false): void
    {
        $this->getEntityManager()->persist($status);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * Supprime un statut
     */
    public function remove(ProductStatus $status, bool $flush = false): void
    {
        $this->getEntityManager()->remove($status);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    // Exemple de méthode personnalisée : chercher un statut par son libellé
    public function findOneByLabel(string $label): ?ProductStatus
    {
        return $this->createQueryBuilder('ps')
            ->andWhere('ps.label = :label')
            ->setParameter('label', $label)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
