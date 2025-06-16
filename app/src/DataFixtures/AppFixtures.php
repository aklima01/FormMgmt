<?php

namespace App\DataFixtures;

use App\Entity\Topic;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class AppFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $names = [
            'Education',
            'Quiz',
            'Job Application',
            'Feedback',
            'Survey',
            'Research',
            'Event RSVP',
            'Registration',
            'Evaluation',
            'Other',
        ];

        foreach ($names as $name) {
            $topic = new Topic($name);
            $manager->persist($topic);
        }

        $manager->flush();
    }
}
