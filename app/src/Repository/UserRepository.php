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

    public function findAllUpdatesFromFriends($userId){
        // $qb = $this->createQueryBuilder('u');
        // $qb->select('series.id as series_id, u2.id as user_id')
        //     ->join('u.followed', 'u2')
        //     ->join('u2.series', 'series');

        $qb = $this->createQueryBuilder('u');
        $qb->select('series.title, series.id as serie_id, rating.value, rating.comment, rating.date, u2.name as user_name, u2.id as user_id')
            ->join('u.followed', 'u2')
            ->join('u2.ratings', 'rating')
            ->join('rating.series', 'series')
            ->where('u.id = :userId')
            ->andWhere('rating.checkrate = 1')
            ->setParameter('userId', $userId)
            ->orderBy('rating.date', 'DESC')
            ->setMaxResults(15);
        
        return $qb->getQuery()->getResult();
    }
}
