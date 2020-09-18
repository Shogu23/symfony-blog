<?php

namespace App\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;

use App\Entity\User;

class UserFixtures extends Fixture
{
    private $passwordEncoder;

    public function __construct(UserPasswordEncoderInterface $passwordEncoder)
    {
        $this->passwordEncoder = $passwordEncoder;
    }

    public function load(ObjectManager $manager)
    {

        $faker = \Faker\Factory::create('fr_FR'); // create a French faker
        for ($i = 0; $i < 10; $i++) {
            $user = new User();
            $user->setFirstname($faker->firstName);
            $user->setLastname($faker->lastName);
            $user->setEmail($faker->email);
            $user->setRoles(['ROLE_USER']);
            
            $user->setPassword($this->passwordEncoder->encodePassword($user,'123456'));

            $manager->persist($user);
            $manager->flush();
        }



        for ($i = 0; $i < 3; $i++) {
            $user = new User();
            $user->setFirstname($faker->firstName);
            $user->setLastname($faker->lastName);
            $user->setEmail($faker->email);
            $user->setRoles(['ROLE_USER', 'ROLE_ADMIN']);
            
            $user->setPassword($this->passwordEncoder->encodePassword($user,'123456'));

            $manager->persist($user);
            $manager->flush();
        }



    }
}
