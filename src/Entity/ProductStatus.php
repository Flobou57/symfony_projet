<?php

namespace App\Entity;

use App\Repository\ProductStatusRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ProductStatusRepository::class)]
class ProductStatus
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 50)]
    private ?string $label = null;

    // === Relations ===
    #[ORM\OneToMany(mappedBy: 'status', targetEntity: Product::class)]
    private $products;

    public function __construct()
    {
        $this->products = new \Doctrine\Common\Collections\ArrayCollection();
    }

    // === Getters / Setters ===
    public function getId(): ?int
    {
        return $this->id;
    }

    public function getLabel(): ?string
    {
        return $this->label;
    }

    public function setLabel(string $label): static
    {
        $this->label = $label;
        return $this;
    }

    public function getProducts(): \Doctrine\Common\Collections\Collection
    {
        return $this->products;
    }

    public function addProduct(Product $product): static
    {
        if (!$this->products->contains($product)) {
            $this->products[] = $product;
            $product->setStatus($this);
        }
        return $this;
    }

    public function removeProduct(Product $product): static
    {
        if ($this->products->removeElement($product)) {
            if ($product->getStatus() === $this) {
                $product->setStatus(null);
            }
        }
        return $this;
    }

    public function __toString(): string
    {
        return $this->label ?? '';
    }
}
