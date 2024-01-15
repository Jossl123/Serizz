<?php

namespace App\Repository;

use Doctrine\ORM\EntityRepository;

class SeriesRepository extends EntityRepository
{
    public function findAllByMostFollowed($limit)
    {
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

    /**
     * Returns the series that a user has completed.
     */
    public function findAllByCompletedSeries($userId)
    {
        $dql = $this->getEntityManager()->createQuery('
            SELECT s 
            FROM App:Series s
            JOIN s.seasons se
            JOIN se.episodes e
            LEFT JOIN e.user u
            WHERE u.id = :userId
            GROUP BY s.id
            HAVING COUNT(DISTINCT e.id) = (
                SELECT COUNT(DISTINCT subE.id)
                FROM App:Series subS
                JOIN subS.seasons subSe
                JOIN subSe.episodes subE
                LEFT JOIN subE.user subU
                WHERE subS.id = s.id
            )
        ');
        $dql->setParameter('userId', $userId);
        return $dql->getResult();
    }
}
