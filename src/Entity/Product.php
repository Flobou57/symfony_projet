<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use App\Repository\ProductRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ProductRepository::class)]
class Product
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 150)]
    private ?string $name = null;

    #[ORM\Column(type: 'float')]
    private ?float $price = null;

    #[ORM\Column(type: 'text')]
    private ?string $description = null;

    #[ORM\Column(type: 'integer')]
    private ?int $stock = null;

    #[ORM\ManyToOne(targetEntity: ProductStatus::class, inversedBy: 'products')]
    #[ORM\JoinColumn(nullable: false)]
    private ?ProductStatus $status = null;

    #[ORM\ManyToOne(targetEntity: Category::class, inversedBy: 'products')]
    private ?Category $category = null;

    #[ORM\OneToMany(mappedBy: 'product', targetEntity: Image::class, cascade: ['persist', 'remove'])]
    private Collection $images;

    #[ORM\OneToMany(mappedBy: 'product', targetEntity: OrderItem::class, cascade: ['persist', 'remove'])]
    private Collection $orderItems;

    public function __construct()
    {
        $this->images = new ArrayCollection();
        $this->orderItems = new ArrayCollection();
    }

    // === Getters & Setters ===

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;
        return $this;
    }

    public function getPrice(): ?float
    {
        return $this->price;
    }

    public function setPrice(float $price): static
    {
        $this->price = $price;
        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(string $description): static
    {
        $this->description = $description;
        return $this;
    }

    public function getStock(): ?int
    {
        return $this->stock;
    }

    public function setStock(int $stock): static
    {
        $this->stock = $stock;
        return $this;
    }

    public function getStatus(): ?ProductStatus
    {
        return $this->status;
    }

    public function setStatus(?ProductStatus $status): static
    {
        $this->status = $status;
        return $this;
    }

    public function getCategory(): ?Category
    {
        return $this->category;
    }

    public function setCategory(?Category $category): static
    {
        $this->category = $category;
        return $this;
    }

    /**
     * @return Collection<int, Image>
     */
    public function getImages(): Collection
    {
        return $this->images;
    }

    public function addImage(Image $image): static
    {
        if (!$this->images->contains($image)) {
            $this->images[] = $image;
            $image->setProduct($this);
        }
        return $this;
    }

    public function removeImage(Image $image): static
    {
        if ($this->images->removeElement($image)) {
            if ($image->getProduct() === $this) {
                $image->setProduct(null);
            }
        }
        return $this;
    }

    /**
     * @return Collection<int, OrderItem>
     */
    public function getOrderItems(): Collection
    {
        return $this->orderItems;
    }

    public function addOrderItem(OrderItem $orderItem): static
    {
        if (!$this->orderItems->contains($orderItem)) {
            $this->orderItems[] = $orderItem;
            $orderItem->setProduct($this);
        }
        return $this;
    }

    public function removeOrderItem(OrderItem $orderItem): static
    {
        if ($this->orderItems->removeElement($orderItem)) {
            if ($orderItem->getProduct() === $this) {
                $orderItem->setProduct(null);
            }
        }
        return $this;
    }
}
