<?php

namespace App\Tests\Unit;

use App\Service\CartService;
use App\Repository\ProductRepository;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;

class CartServiceTest extends TestCase
{
    private RequestStack $requestStack;
    private \PHPUnit\Framework\MockObject\MockObject|ProductRepository $productRepository;

    protected function setUp(): void
    {
        $this->requestStack = new RequestStack();
        $request = new Request();
        $request->setSession(new Session(new MockArraySessionStorage()));
        $this->requestStack->push($request);

        $this->productRepository = $this->createMock(ProductRepository::class);
    }

    public function testSummaryEmptyCartReturnsZeros(): void
    {
        $service = new CartService($this->requestStack, $this->productRepository);

        $summary = $service->getSummary();

        self::assertSame(0, $summary['count']);
        self::assertSame(0.0, $summary['total']);
    }

    public function testSummaryCalculatesTotalsWithExistingProducts(): void
    {
        $session = $this->requestStack->getSession();
        $session->set('cart', [1 => 2, 2 => 1]);

        $product1 = new class {
            public function getPrice(): float { return 10.0; }
        };
        $product2 = new class {
            public function getPrice(): float { return 5.5; }
        };

        $this->productRepository
            ->method('find')
            ->willReturnCallback(function ($id) use ($product1, $product2) {
                return match ((int) $id) {
                    1 => $product1,
                    2 => $product2,
                    default => null,
                };
            });

        $service = new CartService($this->requestStack, $this->productRepository);
        $summary = $service->getSummary();

        self::assertSame(3, $summary['count']);
        self::assertEquals(25.5, $summary['total']);
    }

    public function testSummarySkipsMissingProducts(): void
    {
        $session = $this->requestStack->getSession();
        $session->set('cart', [99 => 3]);

        $this->productRepository
            ->method('find')
            ->willReturn(null);

        $service = new CartService($this->requestStack, $this->productRepository);
        $summary = $service->getSummary();

        self::assertSame(3, $summary['count']); // quantité conservée
        self::assertSame(0.0, $summary['total']); // prix ignoré si produit absent
    }
}
