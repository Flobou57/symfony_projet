<?php

namespace App\Components;

use App\Repository\ProductRepository;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\ComponentToolsTrait;
use Symfony\UX\LiveComponent\DefaultActionTrait;

#[AsLiveComponent('product_search')]
class ProductSearchComponent
{
    use DefaultActionTrait;
    use ComponentToolsTrait;

    #[LiveProp(writable: true)]
    public ?string $query = '';

    #[LiveProp(writable: true)]
    public ?int $categoryId = null;

    public function __construct(private ProductRepository $productRepository)
    {
    }

    /**
     * @return array<int, \App\Entity\Product>
     */
    public function getProducts(): array
    {
        return $this->productRepository->search($this->query, $this->categoryId, 30);
    }
}
