<?php

namespace App\Repository;

use App\Entity\Conversation;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Conversation>
 */
class ConversationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Conversation::class);
    }

    /**
     * Finds conversations for a specific user (either buyer or seller)
     */
    public function findByUser(User $user)
    {
        return $this->createQueryBuilder('c')
            ->where('c.buyer = :user OR c.seller = :user')
            ->setParameter('user', $user)
            ->orderBy('c.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find an existing conversation between buyer and seller for a product
     */
    public function findExisting(User $buyer, User $seller, $product = null)
    {
        $qb = $this->createQueryBuilder('c')
            ->where('c.buyer = :buyer')
            ->andWhere('c.seller = :seller')
            ->setParameter('buyer', $buyer)
            ->setParameter('seller', $seller);

        if ($product) {
            $qb->andWhere('c.product = :product')
               ->setParameter('product', $product);
        } else {
            $qb->andWhere('c.product IS NULL');
        }

        return $qb->getQuery()->getOneOrNullResult();
    }
}
