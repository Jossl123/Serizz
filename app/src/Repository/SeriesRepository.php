<?php

namespace App\Repository;

use Doctrine\ORM\EntityRepository;

class SeriesRepository extends EntityRepository
{
    public function findAllByMostFollowed($limit){
        $qb = $this->createQueryBuilder('s');
        $hallOfFameSeries = $qb->select('s')
            ->join('s.user', 'u')
            ->groupBy('s.id')
            ->orderBy('COUNT(u.id)', 'DESC')
            ->setMaxResults($limit)
        ->getQuery()->getResult();
        dump($qb->getQuery());
        return $hallOfFameSeries;
    }
}
