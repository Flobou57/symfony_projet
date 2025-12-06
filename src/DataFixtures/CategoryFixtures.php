<?php

namespace App\DataFixtures;

use App\Entity\Category;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class CategoryFixtures extends Fixture
{
    public const CATEGORY_REFS = [
        'category_rifles',
        'category_pistols',
        'category_knives',
        'category_gloves',
        'category_stickers',
        'category_agents',
    ];

    public function load(ObjectManager $manager): void
    {
        $names = [
            'Fusils d\'assaut',
            'Pistolets',
            'Couteaux',
            'Gants',
            'Stickers',
            'Agents',
        ];

        foreach ($names as $index => $name) {
            $category = new Category();
            $category->setName($name);
            $manager->persist($category);
            $this->addReference(self::CATEGORY_REFS[$index], $category);
        }

        $manager->flush();
    }
}
