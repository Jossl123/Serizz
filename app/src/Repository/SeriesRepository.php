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
        return $hallOfFameSeries;
    }

    /**
     * Returns all the series a user has posted a rating on
     */
    public function findAllByRating($user){
        $qb = $this->createQueryBuilder('s');
        $qb->select('s')
        ->join('s.ratings', 'r')
        ->join('r.user', 'u')
        ->where('u = :user')
        ->setParameter('user', $user)
        ->orderBy('r.date', 'DESC');
        return $qb->getQuery()->getResult();
    }

    public function findAllByFilters($filters, $user)
    {
        $search = $this->createQueryBuilder('s');

        $search->select('s');
        if (isset($filters['init'])) {
            $search->andwhere('s.title LIKE :init')
                ->setParameter('init', '%' . $filters['init'] . '%');
        }
        if (isset($filters['syno'])) {
            $search->andwhere('s.plot LIKE :syn')
                ->setParameter('syn', '%' . $filters['syno'] . '%');
        }
        if (isset($filters['Syear']) and !isset($filters['yearE'])) {
            $search->andwhere('s.yearStart >= :ys')
                ->setParameter('ys', $filters['Syear'])
                ->orderBy('s.yearStart', 'ASC');
        }

        if (isset($filters['yearE']) and !isset($filters['Syear'])) {
            $search->andwhere('s.yearEnd = :ye')
                ->setParameter('ye', $filters['yearE']);
        }

        if (isset($filters['yearE']) and isset($filters['Syear'])) {
            $search->andwhere('s.yearStart >= :ys')
                ->andwhere('s.yearStart <= :ye')
                ->setParameter('ys', $filters['Syear'])
                ->setParameter('ye', $filters['yearE']);
        }

        if (isset($filters['genres']) && $filters['genres'] != "") {
            $tousGenres = explode("_", $filters['genres']);
            $subsearch = $this->createQueryBuilder('sub_s');
            $subsearch->select('sub_s')
                ->join('sub_s.genre', 'g')
                ->andWhere('g.name IN (:genres)');
            $search->setParameter('genres', $tousGenres);
            $search->andWhere($search->expr()->in('s.id', $subsearch->getDQL()));
        }

        if (isset($filters['Srate']) or isset($filters['rateE'])) {
            $minRate = isset($filters['Srate']) ? $filters['Srate'] : 0;
            $maxRate = isset($filters['rateE']) ? $filters['rateE'] : 10;
            if ($minRate != 0 || $maxRate != 10){
                $search->join('s.ratings', 'r')
                    ->andwhere('r.checkrate = 1')
                    ->andwhere('r.value BETWEEN :min AND :max')
                    ->setParameter('min', $minRate)
                    ->setParameter('max', $maxRate);
            }
        }

        if (isset($filters['followed-users']) && $user != null) {
            $search->join('s.user', 'u')
                ->join('u.followers', 'follower')
                ->andWhere('follower = :user')
                ->setParameter('user', $user);
        }

        return $search->getQuery()->getResult();
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

    public function findAllByRatingBetween($min, $max)
    {
        $dql = $this->getEntityManager()->createQuery('
            SELECT s 
            FROM App:Series s
            JOIN s.ratings r
            WHERE r.value BETWEEN :min AND :max
        ');
        $dql->setParameter('min', $min);
        $dql->setParameter('max', $max);
        return $dql->getResult();
    }
}
