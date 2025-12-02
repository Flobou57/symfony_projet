<?php

namespace App\EventListener;

use App\Entity\Product;
use App\Entity\ProductStatus;
use App\Repository\ProductStatusRepository;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsEntityListener;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Event\PrePersistEventArgs;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Doctrine\ORM\Events;

#[AsEntityListener(event: Events::prePersist, method: 'prePersist', entity: Product::class)]
#[AsEntityListener(event: Events::preUpdate, method: 'preUpdate', entity: Product::class)]
class ProductStockStatusListener
{
    private array $statusCache = [];

    public function __construct(
        private ProductStatusRepository $statusRepository
    ) {
    }

    public function prePersist(Product $product, PrePersistEventArgs $event): void
    {
        $this->applyStatusFromStock($product);
    }

    public function preUpdate(Product $product, PreUpdateEventArgs $event): void
    {
        $hasChanged = $this->applyStatusFromStock($product);

        if ($hasChanged) {
            $this->recomputeChanges($event->getObjectManager(), $product);
        }
    }

    private function applyStatusFromStock(Product $product): bool
    {
        $stock = $product->getStock();

        // Pas de stock défini → on ne change rien
        if ($stock === null) {
            return false;
        }

        $targetLabel = $stock <= 0 ? 'En rupture de stock' : 'Disponible';
        $targetStatus = $this->getStatusByLabel($targetLabel);

        // Aucun statut correspondant en base → ne rien faire
        if (!$targetStatus) {
            return false;
        }

        // Déjà au bon statut → rien à changer
        $currentStatus = $product->getStatus();
        if ($currentStatus && $currentStatus->getId() === $targetStatus->getId()) {
            return false;
        }

        $product->setStatus($targetStatus);
        return true;
    }

    private function getStatusByLabel(string $label): ?ProductStatus
    {
        if (!array_key_exists($label, $this->statusCache)) {
            $this->statusCache[$label] = $this->statusRepository->findOneByLabel($label);
        }

        return $this->statusCache[$label];
    }

    private function recomputeChanges(EntityManagerInterface $em, Product $product): void
    {
        $uow = $em->getUnitOfWork();
        $uow->recomputeSingleEntityChangeSet(
            $em->getClassMetadata(Product::class),
            $product
        );
    }
}
