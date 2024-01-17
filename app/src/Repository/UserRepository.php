<?php

namespace App\Repository;

use Doctrine\ORM\EntityRepository;

class UserRepository extends EntityRepository
{
    /**
     * Returns the users that the user follows, sorted by registered date
     */
    public function findAllByFollowed($user){
        $qb = $this->createQueryBuilder('followed');
        $qb->select('followed')
        ->join('followed.followers', 'user')
        ->where('user = :user')
        ->setParameter('user', $user)
        ->orderBy('followed.registerDate', 'DESC');
        return $qb->getQuery()->getResult();
    }
}
