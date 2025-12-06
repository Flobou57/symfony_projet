<?php

namespace App\Components;

use App\Repository\ProductRepository;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\DefaultActionTrait;

#[AsLiveComponent('product_search')]
class ProductSearchComponent
{
    use DefaultActionTrait;

    #[LiveProp(writable: true)]
    public string $query = '';

    public function __construct(
        private ProductRepository $productRepository,
        private UrlGeneratorInterface $router
    ) {
    }

    public function getResults(): array
    {
        $term = trim($this->query);
        if ($term === '') {
            return [];
        }

        $qb = $this->productRepository->searchQueryBuilder($term);
        $products = $qb->setMaxResults(8)->getQuery()->getResult();

        return array_map(function ($product) {
            return [
                'id' => $product->getId(),
                'name' => $product->getName(),
                'price' => $product->getPrice(),
                'url' => $this->router->generate('app_shop_product_show', ['id' => $product->getId()]),
            ];
        }, $products);
    }
}
