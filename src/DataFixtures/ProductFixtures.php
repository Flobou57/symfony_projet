<?php

namespace App\DataFixtures;

use App\Entity\Image;
use App\Entity\Product;
use App\Entity\ProductStatus;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

class ProductFixtures extends Fixture implements DependentFixtureInterface
{
    public const PRODUCT_REFS = [
        'product_ak47',
        'product_usp',
        'product_karambit',
        'product_gloves',
        'product_sticker',
        'product_awp',
        'product_m4a4',
        'product_deagle',
        'product_p90',
        'product_tec9',
    ];

    public function load(ObjectManager $manager): void
    {
        $statusRepo = $manager->getRepository(ProductStatus::class);
        $available = $statusRepo->findOneBy(['label' => 'Disponible']);
        $preorder = $statusRepo->findOneBy(['label' => 'En précommande']);
        $out = $statusRepo->findOneBy(['label' => 'En rupture de stock']);

        $products = [
            [
                'name' => 'AK-47 | Redline',
                'price' => 42.50,
                'stock' => 10,
                'description' => 'Skin mythique pour AK-47 avec motif Redline.',
                'status' => $available,
                'category_ref' => CategoryFixtures::CATEGORY_REFS[0],
                'image' => '/images/skins/ak47_redline.png',
            ],
            [
                'name' => 'USP-S | Kill Confirmed',
                'price' => 35.00,
                'stock' => 8,
                'description' => 'USP-S avec illustration inspirée du comic Kill Confirmed.',
                'status' => $available,
                'category_ref' => CategoryFixtures::CATEGORY_REFS[1],
                'image' => '/images/skins/usp_kill_confirmed.png',
            ],
            [
                'name' => 'Karambit | Fade',
                'price' => 520.00,
                'stock' => 0,
                'description' => 'Karambit avec dégradé emblématique.',
                'status' => $out ?? $available,
                'category_ref' => CategoryFixtures::CATEGORY_REFS[2],
                'image' => '/images/skins/karambit_fade.png',
            ],
            [
                'name' => 'Sport Gloves | Vice',
                'price' => 260.00,
                'stock' => 4,
                'description' => 'Gants flashy Vice pour compléter votre loadout.',
                'status' => $available,
                'category_ref' => CategoryFixtures::CATEGORY_REFS[3],
                'image' => '/images/skins/gloves_vice.png',
            ],
            [
                'name' => 'Sticker | Headshot',
                'price' => 5.00,
                'stock' => 20,
                'description' => 'Sticker Headshot pour vos armes préférées.',
                'status' => $preorder ?? $available,
                'category_ref' => CategoryFixtures::CATEGORY_REFS[4],
                'image' => '/images/skins/sticker_headshot.png',
            ],
            [
                'name' => 'AWP | Dragon Lore',
                'price' => 1500.00,
                'stock' => 2,
                'description' => 'L’AWP de légende, rare et convoitée.',
                'status' => $available,
                'category_ref' => CategoryFixtures::CATEGORY_REFS[0],
                'image' => '/images/skins/awp_dragon_lore.png',
            ],
            [
                'name' => 'M4A4 | Howl',
                'price' => 980.00,
                'stock' => 3,
                'description' => 'M4A4 Howl, édition limitée au visuel iconique.',
                'status' => $available,
                'category_ref' => CategoryFixtures::CATEGORY_REFS[0],
                'image' => '/images/skins/m4a4_howl.png',
            ],
            [
                'name' => 'Desert Eagle | Blaze',
                'price' => 115.00,
                'stock' => 6,
                'description' => 'Deagle Blaze pour des one taps flamboyants.',
                'status' => $available,
                'category_ref' => CategoryFixtures::CATEGORY_REFS[1],
                'image' => '/images/skins/deagle_blaze.png',
            ],
            [
                'name' => 'P90 | Asiimov',
                'price' => 45.00,
                'stock' => 9,
                'description' => 'P90 Asiimov, look futuriste pour les rush B.',
                'status' => $preorder ?? $available,
                'category_ref' => CategoryFixtures::CATEGORY_REFS[0],
                'image' => '/images/skins/p90_asiimov.png',
            ],
            [
                'name' => 'Tec-9 | Fuel Injector',
                'price' => 25.00,
                'stock' => 15,
                'description' => 'Tec-9 Fuel Injector pour des eco rounds stylés.',
                'status' => $available,
                'category_ref' => CategoryFixtures::CATEGORY_REFS[1],
                'image' => '/images/skins/tec9_fuel_injector.png',
            ],
        ];

        foreach ($products as $index => $data) {
            $product = new Product();
            $product->setName($data['name']);
            $product->setPrice($data['price']);
            $product->setStock($data['stock']);
            $product->setDescription($data['description']);
            $product->setStatus($data['status']);
            $product->setCategory($this->getReference($data['category_ref'], \App\Entity\Category::class));

            $image = new Image();
            $image->setUrl($data['image']);
            $image->setProduct($product);

            $manager->persist($product);
            $manager->persist($image);

            $this->addReference(self::PRODUCT_REFS[$index], $product);
        }

        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [
            CategoryFixtures::class,
            ProductStatusFixtures::class,
        ];
    }
}
