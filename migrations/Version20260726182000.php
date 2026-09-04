<?php

declare(strict_types=1);

namespace DoctrineMigrations\Queue;

use ControleOnline\Migration\TenantAwareMigration;
use Doctrine\DBAL\Schema\Schema;

final class Version20260726182000 extends TenantAwareMigration
{
    public function getDescription(): string
    {
        return 'Restrict display_type values to production, conference and tracking.';
    }

    public function up(Schema $schema): void
    {
        return;
    }

    public function down(Schema $schema): void
    {
        return;
    }
}
