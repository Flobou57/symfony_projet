<?php

namespace App\DataFixtures;

use App\Entity\Address;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class UserFixtures extends Fixture implements DependentFixtureInterface
{
    public const USER_ADMIN = 'user_admin';
    public const USER_CLIENT = 'user_client';

    public function __construct(private UserPasswordHasherInterface $hasher)
    {
    }

    public function load(ObjectManager $manager): void
    {
        // Admin
        $admin = new User();
        $admin->setEmail('admin@skinmarket.test');
        $admin->setFirstName('Gabe');
        $admin->setLastName('Newell');
        $admin->setRoles(['ROLE_ADMIN']);
        $admin->setPassword($this->hasher->hashPassword($admin, 'Admin123!'));

        $adminAddress = (new Address())
            ->setStreet('1 Valve Plaza')
            ->setPostalCode('98052')
            ->setCity('Bellevue')
            ->setCountry('USA')
            ->setUser($admin);

        $manager->persist($admin);
        $manager->persist($adminAddress);
        $this->addReference(self::USER_ADMIN, $admin);

        // Client
        $client = new User();
        $client->setEmail('player@skinmarket.test');
        $client->setFirstName('Alyx');
        $client->setLastName('Vance');
        $client->setRoles(['ROLE_USER']);
        $client->setPassword($this->hasher->hashPassword($client, 'Player123!'));

        $clientAddress = (new Address())
            ->setStreet('42 Dust II Street')
            ->setPostalCode('75000')
            ->setCity('Paris')
            ->setCountry('France')
            ->setUser($client);

        $manager->persist($client);
        $manager->persist($clientAddress);
        $this->addReference(self::USER_CLIENT, $client);

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
