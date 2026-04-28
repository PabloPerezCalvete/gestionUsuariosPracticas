<?php

namespace App\DataFixtures;

use App\Entity\Usuario1;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AppFixtures extends Fixture
{
    public function __construct(private UserPasswordHasherInterface $userPasswordHasher)
    {
    }

    public function load(ObjectManager $manager): void
    {
        $user = new Usuario1();
        $user->setEmail("a@a.a");
        $user->setRoles(['ROLE_ADMIN']);

        $hashedPassword = $this->userPasswordHasher->hashPassword($user, "123");
        $user->setPassword($hashedPassword);

        $manager->persist($user);
        $manager->flush();
    }
}