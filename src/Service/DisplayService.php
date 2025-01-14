<?php

namespace ControleOnline\Service;

use ControleOnline\Entity\Invoice;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Security\Core\Security;
use Doctrine\ORM\QueryBuilder;

class DisplayService
{
    public function __construct(
        private EntityManagerInterface $manager,
        private Security $security,
        private PeopleService $PeopleService
    ) {}

    public function secutiryFilter(QueryBuilder $queryBuilder, $resourceClass = null, $applyTo = null, $rootAlias = null): void
    {
        $this->PeopleService->checkCompany('company', $queryBuilder, $resourceClass, $applyTo, $rootAlias);
    }
}
