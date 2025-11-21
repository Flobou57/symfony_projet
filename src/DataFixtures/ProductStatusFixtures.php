<?php

namespace App\DataFixtures;

use App\Entity\ProductStatus;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class ProductStatusFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $statuses = [
            'Disponible',
            'En rupture de stock',
            'En précommande',
        ];

        foreach ($statuses as $label) {
            $status = new ProductStatus();
            $status->setLabel($label);
            $manager->persist($status);
        }

        $manager->flush();
    }
}
