<?php

namespace App\Repository;

use App\Entity\User;
use App\Entity\UserSkillRating;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<UserSkillRating>
 */
class UserSkillRatingRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, UserSkillRating::class);
    }

    public function findByUser(User $user): ?UserSkillRating
    {
        return $this->findOneBy(['user' => $user]);
    }
}

