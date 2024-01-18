<?php

namespace App\Repository;

use Doctrine\ORM\EntityRepository;

class UserRepository extends EntityRepository
{
    /**
     * Finds the users that the user follows, sorted by registered date.
     * 
     * @param App:User $user The user that we want to get the followed users of
     * @return DoctrineCollection The user collection
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
