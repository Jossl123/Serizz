<?php

namespace App\Repository;

use Doctrine\ORM\EntityRepository;

class UserRepository extends EntityRepository
{
    public function findAllByUserFollowed($user)
    {
        $qb = $this->createQueryBuilder('u');
        $qb->select('u')
        

    }
}
