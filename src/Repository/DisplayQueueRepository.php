<?php

namespace ControleOnline\Repository;

use ControleOnline\Entity\DisplayQueue;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Query\ResultSetMapping;
use Doctrine\Persistence\ManagerRegistry;


/**
 * @method DisplayQueue|null find($id, $lockMode = null, $lockVersion = null)
 * @method DisplayQueue|null findOneBy(array $criteria, array $orderBy = null)
 * @method DisplayQueue[]    findAll()
 * @method DisplayQueue[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class DisplayQueueRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, DisplayQueue::class);
    }
}
