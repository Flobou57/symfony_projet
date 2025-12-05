<?php

namespace App\Tests\Unit;

use App\Entity\OrderItem;
use PHPUnit\Framework\TestCase;

class OrderItemTest extends TestCase
{
    public function testSubtotalIsPriceTimesQuantity(): void
    {
        $item = new OrderItem();
        $item->setQuantity(3);
        $item->setProductPrice(9.99);

        self::assertEquals(29.97, $item->getSubtotal());
    }

    public function testSubtotalWithZeroQuantity(): void
    {
        $item = new OrderItem();
        $item->setQuantity(0);
        $item->setProductPrice(15.0);

        self::assertEquals(0.0, $item->getSubtotal());
    }
}
