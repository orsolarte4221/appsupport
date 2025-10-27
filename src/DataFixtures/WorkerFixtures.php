<?php

declare(strict_types=1);

namespace App\DataFixtures;

use App\Entity\Worker;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class WorkerFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $names = ['Camila', 'Carolina', 'Felipe'];

        foreach ($names as $name) {
            $worker = new Worker();
            $worker->setName($name);
            $manager->persist($worker);
        }

        $manager->flush();
    }
}