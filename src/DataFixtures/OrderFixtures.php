<?php

namespace App\DataFixtures;

use App\Entity\Order;
use App\Entity\OrderItem;
use App\Enum\OrderStatus;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

class OrderFixtures extends Fixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        $client = $this->getReference(UserFixtures::USER_CLIENT, \App\Entity\User::class);
        $admin = $this->getReference(UserFixtures::USER_ADMIN, \App\Entity\User::class);

        $ordersData = [
            [
                'user' => $client,
                'status' => OrderStatus::LIVREE,
                'created' => '-5 days',
                'updated' => '-4 days',
                'items' => [
                    ProductFixtures::PRODUCT_REFS[0] => 1, // AK-47
                    ProductFixtures::PRODUCT_REFS[1] => 2, // USP-S
                ],
            ],
            [
                'user' => $client,
                'status' => OrderStatus::EXPEDIEE,
                'created' => '-3 days',
                'updated' => '-2 days',
                'items' => [
                    ProductFixtures::PRODUCT_REFS[5] => 1, // AWP
                    ProductFixtures::PRODUCT_REFS[7] => 1, // Deagle
                ],
            ],
            [
                'user' => $admin,
                'status' => OrderStatus::EN_PREPARATION,
                'created' => '-1 day',
                'updated' => 'now',
                'items' => [
                    ProductFixtures::PRODUCT_REFS[2] => 1, // Karambit
                    ProductFixtures::PRODUCT_REFS[9] => 2, // Tec-9
                ],
            ],
        ];

        foreach ($ordersData as $data) {
            $order = new Order();
            $order->setUser($data['user']);
            $order->setReference('CMD-' . substr(uniqid(), -6));
            $order->setStatus($data['status']);
            $order->setCreatedAt(new \DateTimeImmutable($data['created']));
            $order->setUpdatedAt(new \DateTimeImmutable($data['updated']));

            $total = 0;
            foreach ($data['items'] as $productRef => $qty) {
                /** @var \App\Entity\Product $product */
                $product = $this->getReference($productRef, \App\Entity\Product::class);

                $item = new OrderItem();
                $item->setProduct($product);
                $item->setQuantity($qty);
                $item->setProductPrice($product->getPrice());
                $order->addItem($item);

                $manager->persist($item);
                $total += $product->getPrice() * $qty;
            }

            $order->setTotal($total);
            $manager->persist($order);
        }

        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [
            UserFixtures::class,
            ProductFixtures::class,
        ];
    }
}
